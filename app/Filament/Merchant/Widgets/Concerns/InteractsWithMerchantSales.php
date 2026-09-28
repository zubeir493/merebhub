<?php

namespace App\Filament\Merchant\Widgets\Concerns;

use App\Models\Merchant;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Lunar\Core\Models\OrderLine;
use Lunar\Core\Models\ProductVariant;

trait InteractsWithMerchantSales
{
    protected function merchantIdsQuery(): Builder
    {
        $merchant = new Merchant;
        $user = Auth::user();

        if (! $user instanceof User) {
            return $merchant->newQuery()->whereKey(0)->select($merchant->qualifyColumn('id'));
        }

        return $merchant->newQuery()
            ->whereIn(
                $merchant->qualifyColumn('id'),
                $user->approvedMerchants()->select($merchant->qualifyColumn('id')),
            )
            ->select($merchant->qualifyColumn('id'));
    }

    protected function merchantProductsQuery(): Builder
    {
        $product = new Product;

        return $product->newQuery()
            ->whereIn($product->qualifyColumn('merchant_id'), $this->merchantIdsQuery());
    }

    protected function merchantSalesLineQuery(): Builder
    {
        $line = new OrderLine;
        $variant = new ProductVariant;
        $product = new Product;

        return $line->newQuery()
            ->with('order')
            ->where($line->qualifyColumn('purchasable_type'), $variant->getMorphClass())
            ->whereIn(
                $line->qualifyColumn('purchasable_id'),
                $variant->newQuery()
                    ->whereIn(
                        $variant->qualifyColumn('product_id'),
                        $this->merchantProductsQuery()->select($product->qualifyColumn('id')),
                    )
                    ->select($variant->qualifyColumn('id')),
            )
            ->whereHas('order', fn (Builder $query): Builder => $query
                ->whereNotNull('placed_at')
                ->whereNull('cancelled_at'));
    }

    protected function merchantSalesJoinQuery(): Builder
    {
        $line = new OrderLine;
        $variant = new ProductVariant;
        $product = new Product;
        $lineTable = $line->getTable();
        $orderTable = $line->order()->getRelated()->getTable();
        $variantTable = $variant->getTable();
        $productTable = $product->getTable();

        return $line->newQuery()
            ->from($lineTable.' as merchant_sales_lines')
            ->join($orderTable.' as merchant_sales_orders', 'merchant_sales_orders.id', '=', 'merchant_sales_lines.order_id')
            ->join($variantTable.' as merchant_sales_variants', 'merchant_sales_variants.id', '=', 'merchant_sales_lines.purchasable_id')
            ->join($productTable.' as merchant_sales_products', 'merchant_sales_products.id', '=', 'merchant_sales_variants.product_id')
            ->where('merchant_sales_lines.purchasable_type', $variant->getMorphClass())
            ->whereIn('merchant_sales_products.merchant_id', $this->merchantIdsQuery())
            ->whereNotNull('merchant_sales_orders.placed_at')
            ->whereNull('merchant_sales_orders.cancelled_at');
    }

    /**
     * @return array{sales: int, orders: int, items: int}
     */
    protected function summarizeSales(Carbon $start, Carbon $end): array
    {
        $summary = $this->merchantSalesJoinQuery()
            ->whereBetween('merchant_sales_orders.placed_at', [$start, $end])
            ->selectRaw('
                COALESCE(SUM(merchant_sales_lines.total), 0) as sales,
                COUNT(DISTINCT merchant_sales_lines.order_id) as orders,
                COALESCE(SUM(merchant_sales_lines.quantity), 0) as items
            ')
            ->first();

        return [
            'sales' => (int) ($summary?->sales ?? 0),
            'orders' => (int) ($summary?->orders ?? 0),
            'items' => (int) ($summary?->items ?? 0),
        ];
    }

    protected function merchantCurrencyCode(): string
    {
        $currency = $this->merchantSalesJoinQuery()
            ->orderByDesc('merchant_sales_orders.placed_at')
            ->value('merchant_sales_orders.currency_code');

        return is_string($currency) && $currency !== '' ? $currency : 'ETB';
    }

    protected function formatMinorAmount(int|float $amount, ?string $currency = null): string
    {
        return sprintf(
            '%s %s',
            str($currency ?? $this->merchantCurrencyCode())->replace('ETB', 'Br'),
            number_format($amount / 100, 2),
        );
    }
}
