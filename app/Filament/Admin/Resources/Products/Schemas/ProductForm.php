<?php

namespace App\Filament\Admin\Resources\Products\Schemas;

use App\Filament\Admin\Resources\Products\ProductResource;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Collection;

class ProductForm
{
    public static function configure(Schema $schema): Schema
    {
        $toEnglish = static function (mixed $state): ?string {
            if ($state instanceof Collection) {
                $state = $state->all();
            }

            if (is_array($state)) {
                $state = $state['en'] ?? collect($state)->first();
            }

            return $state === null ? null : (string) $state;
        };

        return $schema
            ->components([
                Section::make('Product details')
                    ->columns(2)
                    ->schema([
                        TextInput::make('name')
                            ->label('Product name')
                            ->required()
                            ->maxLength(255)
                            ->formatStateUsing($toEnglish)
                            ->dehydrateStateUsing(fn (mixed $state): array => ['en' => $toEnglish($state)]),
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
                            ->relationship('merchant', 'display_name')
                            ->searchable()
                            ->preload(),
                        Textarea::make('short_description')
                            ->label('Short description')
                            ->rows(3)
                            ->maxLength(500)
                            ->formatStateUsing($toEnglish)
                            ->dehydrateStateUsing(function (mixed $state) use ($toEnglish): ?array {
                                $state = $toEnglish($state);

                                return $state === null || $state === '' ? null : ['en' => $state];
                            }),
                        Textarea::make('description')
                            ->label('Description')
                            ->rows(7)
                            ->required()
                            ->formatStateUsing($toEnglish)
                            ->dehydrateStateUsing(fn (mixed $state): array => ['en' => $toEnglish($state)]),
                    ]),
                Section::make('Review and publication')
                    ->columns(2)
                    ->schema([
                        Select::make('status')
                            ->options([
                                'draft' => 'Draft',
                                'published' => 'Published',
                            ])
                            ->required()
                            ->default('draft'),
                        Select::make('publication_state')
                            ->label('Publication state')
                            ->options(ProductResource::publicationStateOptions())
                            ->required()
                            ->default('draft'),
                        Select::make('source_type')
                            ->label('Source')
                            ->options([
                                'local_developer' => 'Local developer',
                                'global_partner' => 'Global partner',
                            ])
                            ->required()
                            ->default('local_developer'),
                        Toggle::make('official_partner')
                            ->label('Official partner product'),
                        TextInput::make('support_owner')
                            ->required()
                            ->default('merebhub'),
                        DateTimePicker::make('published_at'),
                        DateTimePicker::make('archived_at'),
                    ]),
            ]);
    }
}
