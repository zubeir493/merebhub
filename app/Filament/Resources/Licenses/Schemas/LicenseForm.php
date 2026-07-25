<?php

namespace App\Filament\Resources\Licenses\Schemas;

use App\Enums\LicenseStatus;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class LicenseForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('order_id')
                    ->relationship('order', 'id')
                    ->required(),
                Select::make('product_id')
                    ->relationship('product', 'name')
                    ->required(),
                TextInput::make('buyer_email')
                    ->email()
                    ->required(),
                TextInput::make('keygen_license_id')
                    ->required(),
                TextInput::make('license_key')
                    ->required(),
                Select::make('status')
                    ->options(LicenseStatus::class)
                    ->default('active')
                    ->required(),
                TextInput::make('activation_limit')
                    ->required()
                    ->numeric()
                    ->default(1),
                DateTimePicker::make('expires_at'),
                DateTimePicker::make('revoked_at'),
            ]);
    }
}
