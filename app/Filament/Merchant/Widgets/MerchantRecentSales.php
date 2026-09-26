<?php

namespace App\Filament\Merchant\Widgets;

use App\Filament\Merchant\Widgets\Concerns\InteractsWithMerchantSales;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Lunar\Core\Models\OrderLine;

class MerchantRecentSales extends TableWidget
{
    use InteractsWithMerchantSales;

    protected static ?int $sort = 3;

    protected int|string|array $columnSpan = ['lg' => 2, 'xl' => 2];

    public function table(Table $table): Table
    {
        return $table
            ->query($this->merchantSalesLineQuery())
            ->heading('Recent sales')
            ->description('The latest placed-order line items from your catalog.')
            ->columns([
                TextColumn::make('order.reference')
                    ->label('Order')
                    ->placeholder(''),
                TextColumn::make('description')
                    ->label('Product')
                    ->limit(36),
                TextColumn::make('quantity')
                    ->numeric(),
                TextColumn::make('total')
                    ->label('Sales')
                    ->formatStateUsing(fn (mixed $state, OrderLine $record): string => $this->formatMinorAmount(
                        (float) $state,
                        $record->order?->currency_code,
                    )),
                TextColumn::make('order.placed_at')
                    ->label('Date')
                    ->dateTime(),
            ])
            ->defaultSort('created_at', 'desc')
            ->paginated(false)
            ->emptyStateHeading('No sales yet')
            ->emptyStateDescription('Your latest customer orders will appear here.');
    }
}
