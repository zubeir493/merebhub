<?php

namespace App\Filament\Resources\Orders\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class OrderInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('public_id')->label('Order'),
                TextEntry::make('transaction_reference')->label('Chapa reference'),
                TextEntry::make('buyer_email'),
                TextEntry::make('buyer_user_id')
                    ->numeric()
                    ->placeholder('-'),
                TextEntry::make('items_count')->state(fn ($record): int => $record->items()->count())->label('Items'),
                TextEntry::make('total_minor')
                    ->label('Total')
                    ->formatStateUsing(fn (int $state, $record): string => number_format($state / 100, 2).' '.$record->currency),
                TextEntry::make('currency'),
                TextEntry::make('status')
                    ->badge(),
                TextEntry::make('paid_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('created_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('updated_at')
                    ->dateTime()
                    ->placeholder('-'),
            ]);
    }
}
