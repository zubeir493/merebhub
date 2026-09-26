<?php

namespace App\Filament\Merchant\Widgets;

use App\Filament\Merchant\Widgets\Concerns\InteractsWithMerchantSales;
use App\Models\Product;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Lunar\Core\Models\OrderLine;
use Lunar\Core\Models\ProductVariant;

class MerchantTopProducts extends TableWidget
{
    use InteractsWithMerchantSales;

    protected static ?int $sort = 2;

    protected int|string|array $columnSpan = ['lg' => 1, 'xl' => 1];

    public function table(Table $table): Table
    {
        $product = new Product;
        $variant = new ProductVariant;
        $line = new OrderLine;
        $orderTable = $line->order()->getRelated()->getTable();
        $productTable = $product->getTable();
        $variantTable = $variant->getTable();
        $lineTable = $line->getTable();

        $itemsSold = $line->newQuery()
            ->selectRaw('COALESCE(SUM(merchant_top_lines.quantity), 0)')
            ->from($lineTable.' as merchant_top_lines')
            ->join($orderTable.' as merchant_top_orders', 'merchant_top_orders.id', '=', 'merchant_top_lines.order_id')
            ->join($variantTable.' as merchant_top_variants', 'merchant_top_variants.id', '=', 'merchant_top_lines.purchasable_id')
            ->whereColumn('merchant_top_variants.product_id', $productTable.'.id')
            ->where('merchant_top_lines.purchasable_type', $variant->getMorphClass())
            ->whereNotNull('merchant_top_orders.placed_at')
            ->whereNull('merchant_top_orders.cancelled_at');

        $salesTotal = $line->newQuery()
            ->selectRaw('COALESCE(SUM(merchant_top_lines.total), 0)')
            ->from($lineTable.' as merchant_top_lines')
            ->join($orderTable.' as merchant_top_orders', 'merchant_top_orders.id', '=', 'merchant_top_lines.order_id')
            ->join($variantTable.' as merchant_top_variants', 'merchant_top_variants.id', '=', 'merchant_top_lines.purchasable_id')
            ->whereColumn('merchant_top_variants.product_id', $productTable.'.id')
            ->where('merchant_top_lines.purchasable_type', $variant->getMorphClass())
            ->whereNotNull('merchant_top_orders.placed_at')
            ->whereNull('merchant_top_orders.cancelled_at');

        return $table
            ->query(
                $product->newQuery()
                    ->whereIn($product->qualifyColumn('merchant_id'), $this->merchantIdsQuery())
                    ->select($productTable.'.*')
                    ->selectSub($itemsSold, 'sold_items')
                    ->selectSub($salesTotal, 'sales_total')
                    ->orderByDesc('sold_items')
                    ->limit(5),
            )
            ->heading('Top products')
            ->description('Best-performing products by lifetime items sold.')
            ->columns([
                TextColumn::make('name')
                    ->label('Product')
                    ->limit(32),
                TextColumn::make('sold_items')
                    ->label('Items sold')
                    ->numeric(),
                TextColumn::make('sales_total')
                    ->label('Sales')
                    ->formatStateUsing(fn (mixed $state): string => $this->formatMinorAmount((float) $state)),
            ])
            ->paginated(false)
            ->emptyStateHeading('No products yet')
            ->emptyStateDescription('Publish a product to start tracking sales.');
    }
}
