<?php

namespace App\Filament\Resources\Licenses\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class LicenseInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('order.id')
                    ->label('Order'),
                TextEntry::make('product.name')
                    ->label('Product'),
                TextEntry::make('buyer_email'),
                TextEntry::make('keygen_license_id'),
                TextEntry::make('license_key'),
                TextEntry::make('status')
                    ->badge(),
                TextEntry::make('activation_limit')
                    ->numeric(),
                TextEntry::make('expires_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('revoked_at')
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
