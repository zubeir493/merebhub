<?php

namespace App\Actions;

use App\Enums\BillingInterval;
use App\Enums\BillingModel;
use App\Enums\FulfillmentType;
use App\Enums\ProductStatus;
use App\Models\Author;
use App\Models\Platform;
use App\Models\Product;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class ImportWooCommerceProduct
{
    public function handle(array $wooProduct): Product
    {
        $wooProductId = (int) Arr::get($wooProduct, 'id');
        $merebHub = $this->merebHubData($wooProduct);
        $authorData = Arr::get($merebHub, 'author', []);
        $authorName = (string) (Arr::get($authorData, 'name') ?: 'Independent developer');
        $authorSlug = Str::slug((string) (Arr::get($authorData, 'slug') ?: $authorName));

        $author = Author::updateOrCreate(
            ['slug' => $authorSlug],
            [
                'name' => $authorName,
                'bio' => Arr::get($authorData, 'bio'),
                'avatar_path' => Arr::get($authorData, 'avatar_url'),
                'website_url' => Arr::get($authorData, 'website_url'),
                'is_public' => true,
            ],
        );

        $slug = $this->availableSlug((string) (Arr::get($wooProduct, 'slug') ?: Arr::get($wooProduct, 'name')), $wooProductId);
        $images = Arr::get($wooProduct, 'images', []);
        $billingModel = BillingModel::tryFrom((string) Arr::get($merebHub, 'billing_model')) ?? BillingModel::OneTime;
        $billingInterval = BillingInterval::tryFrom((string) Arr::get($merebHub, 'billing_interval'));
        $fulfillmentType = FulfillmentType::tryFrom((string) Arr::get($merebHub, 'fulfillment_type')) ?? FulfillmentType::LicenseKey;

        $product = Product::updateOrCreate(
            ['wc_product_id' => $wooProductId],
            [
                'author_id' => $author->id,
                'category' => (string) (Arr::get($wooProduct, 'categories.0.name') ?: 'Software'),
                'name' => (string) Arr::get($wooProduct, 'name'),
                'slug' => $slug,
                'tagline' => trim(strip_tags((string) Arr::get($wooProduct, 'short_description'))) ?: null,
                'description' => (string) Arr::get($wooProduct, 'description', ''),
                'price' => (string) (Arr::get($wooProduct, 'price') ?: 0),
                'compare_at_price' => Arr::get($wooProduct, 'regular_price') !== Arr::get($wooProduct, 'price')
                    ? Arr::get($wooProduct, 'regular_price')
                    : null,
                'icon_path' => Arr::get($images, '0.src'),
                'cover_path' => Arr::get($images, '1.src', Arr::get($images, '0.src')),
                'rating' => (string) Arr::get($wooProduct, 'average_rating', 0),
                'ratings_count' => (int) Arr::get($wooProduct, 'rating_count', 0),
                'weekly_sales' => (int) Arr::get($merebHub, 'weekly_sales', Arr::get($wooProduct, 'total_sales', 0)),
                'is_featured' => (bool) Arr::get($wooProduct, 'featured', false),
                'keygen_policy_id' => Arr::get($merebHub, 'keygen_policy_id'),
                'fulfillment_type' => $fulfillmentType,
                'billing_model' => $billingModel,
                'billing_interval' => $billingModel === BillingModel::ManualSubscription ? $billingInterval : null,
                'trial_days' => Arr::get($merebHub, 'trial_days'),
                'app_url' => Arr::get($merebHub, 'app_url'),
                'wc_metadata' => $merebHub,
                'last_synced_at' => now(),
                'status' => Arr::get($wooProduct, 'status') === 'publish'
                    ? ProductStatus::Published
                    : ProductStatus::Draft,
            ],
        );

        $platformIds = collect(Arr::get($merebHub, 'platforms', $this->platformsFromAttributes($wooProduct)))
            ->filter()
            ->map(function (string $name): int {
                return Platform::firstOrCreate(
                    ['slug' => Str::slug($name)],
                    ['name' => $name],
                )->id;
            });

        $product->platforms()->sync($platformIds);

        return $product->refresh();
    }

    private function merebHubData(array $wooProduct): array
    {
        $data = Arr::get($wooProduct, 'merebhub', Arr::get($wooProduct, 'acf', []));

        foreach (Arr::get($wooProduct, 'meta_data', []) as $meta) {
            $key = (string) Arr::get($meta, 'key');

            if (Str::startsWith($key, 'merebhub_')) {
                data_set($data, Str::after($key, 'merebhub_'), Arr::get($meta, 'value'));
            }
        }

        return is_array($data) ? $data : [];
    }

    private function platformsFromAttributes(array $wooProduct): array
    {
        $platform = collect(Arr::get($wooProduct, 'attributes', []))
            ->first(fn (array $attribute): bool => in_array(
                Str::lower((string) Arr::get($attribute, 'name')),
                ['platform', 'platforms'],
                true,
            ));

        return Arr::get($platform, 'options', []);
    }

    private function availableSlug(string $value, int $wooProductId): string
    {
        $slug = Str::slug($value) ?: "app-{$wooProductId}";
        $conflict = Product::query()
            ->where('slug', $slug)
            ->where('wc_product_id', '!=', $wooProductId)
            ->exists();

        return $conflict ? "{$slug}-{$wooProductId}" : $slug;
    }
}
