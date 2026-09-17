<?php

namespace App\Filament\Admin\Resources\Products\Pages;

use App\Domain\Catalog\Actions\SyncProductConfigurationAction;
use App\Filament\Admin\Resources\Products\ProductResource;
use App\Models\LicenseMapping;
use App\Models\Product;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditProduct extends EditRecord
{
    protected static string $resource = ProductResource::class;

    /** @var array<string, mixed> */
    protected array $productConfiguration = [];

    protected function mutateFormDataBeforeFill(array $data): array
    {
        /** @var Product $product */
        $product = $this->record;
        $mapping = LicenseMapping::query()
            ->where('product_id', $product->getKey())
            ->whereNull('product_variant_id')
            ->first();

        $variants = $product->variants()
            ->with(['prices' => fn ($query) => $query
                ->whereNull('customer_group_id')
                ->where('min_quantity', 1)
                ->orderBy('id')])
            ->get()
            ->map(function ($variant): array {
                $price = $variant->prices->first();
                $variantMapping = LicenseMapping::query()
                    ->where('product_variant_id', $variant->getKey())
                    ->first();

                return [
                    'id' => $variant->getKey(),
                    'name' => $variant->variant_name ?: ($variantMapping?->label ?: ($variant->getOption() ?: 'Standard license')),
                    'sku' => $variant->sku,
                    'price' => $price ? $price->decimal('price', rounding: false) : null,
                    'compare_at_price' => $price?->list_price === null ? null : $price->decimal('list_price', rounding: false),
                    'tax_class_id' => $variant->tax_class_id,
                    'selling_policy' => $variant->selling_policy?->value ?? (string) $variant->selling_policy,
                    'shippable' => (bool) $variant->shippable,
                    'presentation_icon' => $variant->presentation_icon,
                    'presentation_image' => $variant->presentation_image,
                    'keygen_policy_id' => $variantMapping?->keygen_policy_id,
                ];
            })
            ->all();
        $firstVariant = $variants[0] ?? [];

        return [
            ...$data,
            'catalog_category' => $product->category,
            'catalog_platform' => (string) $product->attr('platform'),
            'default_price' => $firstVariant['price'] ?? null,
            'default_compare_at_price' => $firstVariant['compare_at_price'] ?? null,
            'variants_data' => $variants,
            'keygen_product_id' => $mapping?->keygen_product_id,
            'keygen_policy_id' => $mapping?->keygen_policy_id,
            'keygen_mapping_label' => $mapping?->label,
            'keygen_mapping_active' => $mapping?->active ?? true,
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $this->productConfiguration = $data;

        unset(
            $data['catalog_category'],
            $data['catalog_platform'],
            $data['default_price'],
            $data['default_compare_at_price'],
            $data['variants_data'],
            $data['keygen_product_id'],
            $data['keygen_policy_id'],
            $data['keygen_mapping_label'],
            $data['keygen_mapping_active'],
        );

        return $data;
    }

    protected function afterSave(): void
    {
        app(SyncProductConfigurationAction::class)->handle($this->record, $this->productConfiguration);
    }

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
