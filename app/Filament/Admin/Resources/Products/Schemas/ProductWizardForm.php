<?php

namespace App\Filament\Admin\Resources\Products\Schemas;

use App\Filament\ProductDetailsSection;
use App\Models\Category;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Collection;

class ProductWizardForm
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
                        Select::make('catalog_category')
                            ->label('Category')
                            ->options(fn (): array => self::categoryOptions())
                            ->searchable()
                            ->preload(),
                        TextInput::make('default_price')
                            ->label('Starting price')
                            ->numeric()
                            ->minValue(0)
                            ->prefix('Br')
                            ->helperText('Used when a variant price is left blank.'),
                        TextInput::make('default_compare_at_price')
                            ->label('Compare-at price')
                            ->numeric()
                            ->minValue(0)
                            ->prefix('Br'),
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
                        TextInput::make('catalog_platform')
                            ->label('Platforms')
                            ->placeholder('Web, Windows, Linux')
                            ->helperText('Comma-separated values used by the storefront.'),
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
                            ->dehydrateStateUsing(fn (mixed $state): array => ['en' => $toEnglish($state)])
                            ->columnSpanFull(),
                    ]),
                ProductDetailsSection::make(true),
            ]);
    }

    /** @return array<string, string> */
    private static function categoryOptions(): array
    {
        return Category::query()->orderBy('name')->pluck('name', 'name')->all();
    }
}
