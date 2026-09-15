<?php

namespace App\Filament\Admin\Resources\LicenseMappings\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class LicenseMappingForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('MerebHub catalog mapping')
                    ->schema([
                        Select::make('product_id')
                            ->label('MerebHub product')
                            ->relationship('product', 'name')
                            ->searchable()
                            ->preload()
                            ->required(),
                        Select::make('product_variant_id')
                            ->label('Variant override')
                            ->relationship('productVariant', 'sku')
                            ->searchable()
                            ->preload()
                            ->helperText('Leave empty to use this policy for every variant of the product.'),
                        TextInput::make('label')
                            ->maxLength(255)
                            ->helperText('Optional internal name for this license tier.'),
                    ])
                    ->columns(2),
                Section::make('Keygen policy')
                    ->description('A product or variant is fulfilled by creating a Keygen license under this policy.')
                    ->schema([
                        TextInput::make('keygen_product_id')
                            ->label('Keygen product ID')
                            ->maxLength(255)
                            ->helperText('Optional; useful for validating the policy relationship.'),
                        TextInput::make('keygen_policy_id')
                            ->label('Keygen policy ID')
                            ->required()
                            ->maxLength(255),
                        Toggle::make('active')
                            ->label('Active mapping')
                            ->default(true),
                    ])
                    ->columns(2),
            ]);
    }
}
