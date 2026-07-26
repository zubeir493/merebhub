<?php

namespace App\Console\Commands;

use App\Actions\ImportWooCommerceProduct;
use App\Enums\ProductStatus;
use App\Models\Product;
use App\Services\WooCommerceService;
use Illuminate\Console\Command;

class SyncWooCommerceProducts extends Command
{
    protected $signature = 'merebhub:sync-woocommerce';

    protected $description = 'Import the WooCommerce catalog into the Laravel read cache';

    public function handle(WooCommerceService $woocommerce, ImportWooCommerceProduct $importProduct): int
    {
        if (! $woocommerce->isConfigured()) {
            $this->error('WooCommerce credentials and webhook secret are not configured.');

            return self::FAILURE;
        }

        $syncStartedAt = now();
        $page = 1;
        $imported = 0;

        do {
            $wooProducts = $woocommerce->fetchProducts(['page' => $page, 'per_page' => 100]);

            foreach ($wooProducts as $wooProduct) {
                $importProduct->handle($wooProduct);
                $imported++;
            }

            $page++;
        } while (count($wooProducts) === 100);

        $unpublished = Product::query()
            ->whereNotNull('wc_product_id')
            ->where(fn ($query) => $query
                ->whereNull('last_synced_at')
                ->orWhere('last_synced_at', '<', $syncStartedAt))
            ->update(['status' => ProductStatus::Draft]);

        $this->info("Imported {$imported} products and unpublished {$unpublished} missing products.");

        return self::SUCCESS;
    }
}
