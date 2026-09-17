<?php

namespace App\Domain\Catalog\Actions;

use App\Models\LicenseMapping;
use App\Models\Product;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Lunar\Core\Facades\PriceCalculator;
use Lunar\Core\FieldTypes\TranslatedText;
use Lunar\Core\Models\Currency;
use Lunar\Core\Models\OrderLine;
use Lunar\Core\Models\ProductVariant;
use Lunar\Core\Models\TaxClass;

class SyncProductConfigurationAction
{
    /**
     * Save the product's inline variants, prices, and Keygen policy mappings.
     *
     * @param  array<string, mixed>  $data
     */
    public function handle(Product $product, array $data): void
    {
        DB::transaction(function () use ($product, $data): void {
            $this->syncCatalogAttributes($product, $data);

            $variantRows = collect($data['variants_data'] ?? [])
                ->filter(fn (mixed $row): bool => is_array($row))
                ->values();

            if ($variantRows->isEmpty()) {
                $variantRows = collect([[
                    'name' => 'Standard license',
                    'sku' => Str::upper(Str::slug($product->name, '-')).'-STANDARD',
                ]]);
            }

            $this->guardHistoricalVariantRemoval($product, $variantRows);

            $currency = Currency::getDefault();
            $taxClassId = $this->defaultTaxClassId();
            $variantIds = [];

            foreach ($variantRows as $row) {
                $variant = $this->resolveVariant($product, $row);
                $variant->fill([
                    'sku' => filled($row['sku'] ?? null) ? (string) $row['sku'] : null,
                    'tax_class_id' => (int) (($row['tax_class_id'] ?? null) ?: $taxClassId),
                    'shippable' => (bool) ($row['shippable'] ?? false),
                    'selling_policy' => (string) ($row['selling_policy'] ?? 'always'),
                    'presentation_icon' => filled($row['presentation_icon'] ?? null) ? (string) $row['presentation_icon'] : null,
                    'presentation_image' => filled($row['presentation_image'] ?? null) ? (string) $row['presentation_image'] : null,
                ]);
                $variant->product()->associate($product);
                $variant->save();
                $variantIds[] = $variant->getKey();

                $this->syncPrice($variant, $row, $data, $currency);
                $this->syncVariantMapping($product, $variant, $row, $data);
            }

            $product->variants()
                ->whereNotIn('id', $variantIds)
                ->get()
                ->each(function (ProductVariant $variant): void {
                    LicenseMapping::query()
                        ->where('product_variant_id', $variant->getKey())
                        ->delete();
                    $variant->delete();
                });

            $this->syncProductMapping($product, $data);
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function syncCatalogAttributes(Product $product, array $data): void
    {
        $attributes = $product->getAttribute('attribute_data');
        $attributes = $attributes instanceof Collection ? $attributes : collect($attributes ?: []);

        foreach ([
            'category' => 'catalog_category',
            'platform' => 'catalog_platform',
        ] as $attribute => $field) {
            if (! array_key_exists($field, $data)) {
                continue;
            }

            $value = trim((string) ($data[$field] ?? ''));

            if ($value === '') {
                $attributes->forget($attribute);
            } else {
                $attributes->put($attribute, new TranslatedText(collect(['en' => $value])));
            }
        }

        $product->setAttribute('attribute_data', $attributes);
        $product->save();
    }

    /**
     * @param  Collection<int, mixed>  $variantRows
     */
    private function guardHistoricalVariantRemoval(Product $product, Collection $variantRows): void
    {
        $keptIds = $variantRows
            ->pluck('id')
            ->filter()
            ->map(fn (mixed $id): int => (int) $id)
            ->all();

        $removed = $product->variants()
            ->when($keptIds !== [], fn (Builder $query): Builder => $query->whereNotIn('id', $keptIds))
            ->when($keptIds === [], fn (Builder $query): Builder => $query)
            ->get();

        foreach ($removed as $variant) {
            $hasOrderHistory = OrderLine::query()
                ->where('purchasable_type', $variant->getMorphClass())
                ->where('purchasable_id', $variant->getKey())
                ->exists();

            if ($hasOrderHistory) {
                throw ValidationException::withMessages([
                    'variants_data' => "Variant [{$variant->sku}] cannot be removed because it has order history.",
                ]);
            }
        }
    }

    /**
     * @param  array<string, mixed>  $row
     */
    private function resolveVariant(Product $product, array $row): ProductVariant
    {
        $id = (int) ($row['id'] ?? 0);

        if ($id > 0) {
            $variant = $product->variants()->whereKey($id)->first();

            if ($variant === null) {
                throw ValidationException::withMessages([
                    'variants_data' => 'One of the selected variants no longer belongs to this product.',
                ]);
            }

            return $variant;
        }

        return new ProductVariant;
    }

    /**
     * @param  array<string, mixed>  $row
     */
    private function syncPrice(ProductVariant $variant, array $row, array $data, Currency $currency): void
    {
        $price = $variant->prices()->firstOrNew([
            'currency_id' => $currency->getKey(),
            'customer_group_id' => null,
            'min_quantity' => 1,
        ]);
        $basePrice = $row['price'] ?? $data['default_price'] ?? 0;
        $compareAtPrice = $row['compare_at_price'] ?? $data['default_compare_at_price'] ?? null;
        $price->price = PriceCalculator::toMinor((float) $basePrice, $currency);
        $price->list_price = filled($compareAtPrice)
            ? PriceCalculator::toMinor((float) $compareAtPrice, $currency)
            : null;
        $price->min_quantity = 1;
        $price->save();
    }

    /**
     * @param  array<string, mixed>  $row
     * @param  array<string, mixed>  $data
     */
    private function syncVariantMapping(Product $product, ProductVariant $variant, array $row, array $data): void
    {
        $policyId = trim((string) ($row['keygen_policy_id'] ?? ''));

        if ($policyId === '') {
            LicenseMapping::query()
                ->where('product_id', $product->getKey())
                ->where('product_variant_id', $variant->getKey())
                ->delete();

            return;
        }

        LicenseMapping::query()->updateOrCreate(
            [
                'product_id' => $product->getKey(),
                'product_variant_id' => $variant->getKey(),
            ],
            [
                'keygen_product_id' => filled($data['keygen_product_id'] ?? null) ? (string) $data['keygen_product_id'] : null,
                'keygen_policy_id' => $policyId,
                'label' => filled($row['name'] ?? null) ? (string) $row['name'] : null,
                'active' => true,
            ],
        );
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function syncProductMapping(Product $product, array $data): void
    {
        $policyId = trim((string) ($data['keygen_policy_id'] ?? ''));
        $licenseEnabled = data_get($data, 'fulfillment_summary.type') === 'license';

        if (! $licenseEnabled || $policyId === '') {
            LicenseMapping::query()
                ->where('product_id', $product->getKey())
                ->whereNull('product_variant_id')
                ->delete();

            return;
        }

        LicenseMapping::query()->updateOrCreate(
            [
                'product_id' => $product->getKey(),
                'product_variant_id' => null,
            ],
            [
                'keygen_product_id' => filled($data['keygen_product_id'] ?? null) ? (string) $data['keygen_product_id'] : null,
                'keygen_policy_id' => $policyId,
                'label' => filled($data['keygen_mapping_label'] ?? null) ? (string) $data['keygen_mapping_label'] : null,
                'active' => (bool) ($data['keygen_mapping_active'] ?? true),
            ],
        );
    }

    private function defaultTaxClassId(): int
    {
        return (int) (TaxClass::query()->where('default', true)->value('id')
            ?? TaxClass::query()->value('id'));
    }
}
