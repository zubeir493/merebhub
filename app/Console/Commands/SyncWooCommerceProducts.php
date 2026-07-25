<?php

namespace App\Console\Commands;

use App\Enums\ProductStatus;
use App\Models\Product;
use App\Services\WooCommerceService;
use Illuminate\Console\Command;
use Illuminate\Support\Arr;

class SyncWooCommerceProducts extends Command
{
    protected $signature = 'merebhub:sync-woocommerce {--push : Push local products to WooCommerce instead of pulling changes}';

    protected $description = 'Synchronize the local product catalog with WooCommerce';

    public function handle(WooCommerceService $woocommerce): int
    {
        if (! $woocommerce->isConfigured()) {
            $this->error('WooCommerce credentials and webhook secret are not configured.');

            return self::FAILURE;
        }

        if ($this->option('push')) {
            Product::query()->each(function (Product $product) use ($woocommerce): void {
                $response = $woocommerce->syncProduct($product);
                $product->update(['wc_product_id' => $response['id']]);
                $this->line("Synced {$product->name}");
            });

            return self::SUCCESS;
        }

        $page = 1;
        $updated = 0;

        do {
            $wooProducts = $woocommerce->fetchProducts(['page' => $page, 'per_page' => 100]);

            foreach ($wooProducts as $wooProduct) {
                $product = Product::where('wc_product_id', Arr::get($wooProduct, 'id'))->first();

                if (! $product) {
                    continue;
                }

                $product->update([
                    'name' => Arr::get($wooProduct, 'name', $product->name),
                    'description' => Arr::get($wooProduct, 'description', $product->description),
                    'price' => Arr::get($wooProduct, 'price', $product->price),
                    'status' => Arr::get($wooProduct, 'status') === 'publish'
                        ? ProductStatus::Published
                        : ProductStatus::Draft,
                ]);
                $updated++;
            }

            $page++;
        } while (count($wooProducts) === 100);

        $this->info("Updated {$updated} local products.");

        return self::SUCCESS;
    }
}
