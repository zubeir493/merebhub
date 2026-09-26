<?php

namespace App\Filament\Merchant\Resources\Customers\Tables;

use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class CustomersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('full_name')
                    ->label('Customer')
                    ->searchable(['first_name', 'last_name'])
                    ->sortable(['first_name', 'last_name']),
                TextColumn::make('email')
                    ->label('Email')
                    ->placeholder('Guest checkout')
                    ->searchable(),
                TextColumn::make('company_name')
                    ->label('Company')
                    ->placeholder('')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('merchant_orders_count')
                    ->label('Orders')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('merchant_last_order_at')
                    ->label('Last order')
                    ->dateTime()
                    ->sortable(),
            ])
            ->defaultSort('merchant_last_order_at', 'desc')
            ->emptyStateHeading('No customers yet')
            ->emptyStateDescription('Customers will appear after their first purchase.');
    }
}
