<?php

namespace App\Filament\Merchant\Concerns;

use App\Models\Merchant;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Lunar\Core\Models\Order;
use Lunar\Core\Models\OrderLine;
use Lunar\Core\Models\ProductVariant;

trait InteractsWithMerchantResources
{
    protected static function merchantIdsQuery(): Builder
    {
        $merchant = new Merchant;
        $user = Auth::user();

        if (! $user instanceof User) {
            return $merchant->newQuery()
                ->whereKey(0)
                ->select($merchant->qualifyColumn('id'));
        }

        return $merchant->newQuery()
            ->whereIn(
                $merchant->qualifyColumn('id'),
                $user->approvedMerchants()->select($merchant->qualifyColumn('id')),
            )
            ->select($merchant->qualifyColumn('id'));
    }

    protected static function merchantOrderLineQuery(): Builder
    {
        $line = new OrderLine;
        $order = new Order;
        $variant = new ProductVariant;
        $product = new Product;
        $lineTable = $line->getTable();
        $orderTable = $order->getTable();
        $variantTable = $variant->getTable();
        $productTable = $product->getTable();

        return $line->newQuery()
            ->from($lineTable.' as merchant_resource_lines')
            ->join($orderTable.' as merchant_resource_orders', 'merchant_resource_orders.id', '=', 'merchant_resource_lines.order_id')
            ->join($variantTable.' as merchant_resource_variants', 'merchant_resource_variants.id', '=', 'merchant_resource_lines.purchasable_id')
            ->join($productTable.' as merchant_resource_products', 'merchant_resource_products.id', '=', 'merchant_resource_variants.product_id')
            ->where('merchant_resource_lines.purchasable_type', $variant->getMorphClass())
            ->whereIn('merchant_resource_products.merchant_id', static::merchantIdsQuery())
            ->whereNotNull('merchant_resource_orders.placed_at')
            ->whereNull('merchant_resource_orders.cancelled_at');
    }

    protected static function merchantOrderIdsQuery(): Builder
    {
        return static::merchantOrderLineQuery()
            ->select('merchant_resource_lines.order_id');
    }
}
