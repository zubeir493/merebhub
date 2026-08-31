<?php

namespace App\Filament\Merchant\Resources\Products\Schemas;

use App\Filament\Merchant\Resources\Products\ProductResource;
use App\Models\Merchant;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ProductForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Product details')
                    ->description('Create a draft product for staff review.')
                    ->columns(2)
                    ->schema([
                        TextInput::make('name')
                            ->label('Product name')
                            ->required()
                            ->maxLength(255)
                            ->dehydrateStateUsing(fn (?string $state): array => ['en' => $state]),
                        Select::make('product_type_id')
                            ->label('Product type')
                            ->relationship('productType', 'name')
                            ->required()
                            ->searchable()
                            ->preload(),
                        Select::make('brand_id')
                            ->label('Brand / author')
                            ->relationship('brand', 'name')
                            ->searchable()
                            ->preload(),
                        Select::make('merchant_id')
                            ->label('Merchant')
                            ->options(function (): array {
                                $merchant = new Merchant;

                                return auth()->user()?->approvedMerchants()
                                    ->orderBy($merchant->qualifyColumn('display_name'))
                                    ->pluck($merchant->qualifyColumn('display_name'), $merchant->qualifyColumn('id'))
                                    ->all() ?? [];
                            })
                            ->required()
                            ->searchable()
                            ->preload()
                            ->disabled(fn (string $operation): bool => $operation === 'edit')
                            ->dehydrated(),
                        Textarea::make('short_description')
                            ->label('Short description')
                            ->rows(3)
                            ->maxLength(500)
                            ->dehydrateStateUsing(fn (?string $state): ?array => $state === null || $state === '' ? null : ['en' => $state]),
                        Textarea::make('description')
                            ->label('Description')
                            ->rows(7)
                            ->required()
                            ->dehydrateStateUsing(fn (?string $state): array => ['en' => $state]),
                    ]),
                Section::make('Submission')
                    ->columns(2)
                    ->schema([
                        Select::make('source_type')
                            ->label('Source')
                            ->options([
                                'local_developer' => 'Local developer',
                                'global_partner' => 'Global partner',
                            ])
                            ->required()
                            ->default('local_developer'),
                        Select::make('publication_state')
                            ->label('Publication state')
                            ->options(ProductResource::publicationStateOptions())
                            ->disabled()
                            ->dehydrated()
                            ->default('draft'),
                    ]),
            ]);
    }
}
