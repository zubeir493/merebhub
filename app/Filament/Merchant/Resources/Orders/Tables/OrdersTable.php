<?php

namespace App\Filament\Merchant\Resources\Orders\Tables;

use BackedEnum;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Lunar\Core\Models\Order;

class OrdersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('reference')
                    ->label('Order')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('customer.full_name')
                    ->label('Customer')
                    ->placeholder('Guest checkout')
                    ->searchable(),
                TextColumn::make('merchant_sales_total')
                    ->label('Your sales')
                    ->formatStateUsing(fn (mixed $state, Order $record): string => sprintf(
                        '%s %s',
                        str($record->currency_code)->replace('ETB', 'Br'),
                        number_format((float) $state / 100, 2),
                    ))
                    ->sortable(),
                TextColumn::make('payment_status')
                    ->label('Payment')
                    ->badge()
                    ->formatStateUsing(fn (mixed $state): string => str($state instanceof BackedEnum ? $state->value : $state)
                        ->replace('_', ' ')
                        ->title()),
                TextColumn::make('fulfilment_status')
                    ->label('Fulfilment')
                    ->badge()
                    ->formatStateUsing(fn (mixed $state): string => str($state instanceof BackedEnum ? $state->value : $state)
                        ->replace('_', ' ')
                        ->title()),
                TextColumn::make('placed_at')
                    ->label('Placed')
                    ->dateTime()
                    ->sortable(),
            ])
            ->defaultSort('placed_at', 'desc')
            ->emptyStateHeading('No sales yet')
            ->emptyStateDescription('Placed customer orders will appear here.');
    }
}
