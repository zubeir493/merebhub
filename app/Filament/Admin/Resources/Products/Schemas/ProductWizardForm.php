<?php

namespace App\Filament\Admin\Resources\Products\Schemas;

use App\Filament\Admin\Resources\Products\ProductResource;
use App\Integrations\Keygen\KeygenClient;
use App\Models\Category;
use Filament\Actions\Action;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Lunar\Core\Models\TaxClass;
use Throwable;

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
                            ->prefix('ETB')
                            ->helperText('Used when a variant price is left blank.'),
                        TextInput::make('default_compare_at_price')
                            ->label('Compare-at price')
                            ->numeric()
                            ->minValue(0)
                            ->prefix('ETB'),
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
                Section::make('Product gallery')
                    ->description('Upload screenshots or product artwork. Drag to reorder; the first image is the cover.')
                    ->schema([
                        SpatieMediaLibraryFileUpload::make('product_gallery')
                            ->label('Gallery images')
                            ->collection(config('lunar.media.collection'))
                            ->multiple()
                            ->reorderable()
                            ->image()
                            ->imageEditor()
                            ->imageEditorAspectRatios([
                                null,
                                '16:10',
                                '4:3',
                                '1:1',
                            ])
                            ->maxFiles(8)
                            ->maxSize(10240)
                            ->columnSpanFull(),
                    ]),
                Tabs::make('Product setup')
                    ->tabs([
                        Tab::make('Variants & pricing')
                            ->icon(Heroicon::OutlinedSquares2x2)
                            ->schema([
                                Repeater::make('variants_data')
                                    ->columns(2)
                                    ->label('Product variants')
                                    ->collapsible()
                                    ->addable(false)
                                    ->reorderable(false)
                                    ->defaultItems(1)
                                    ->extraItemActions([
                                        Action::make('add_variant')
                                            ->label('Add variant')
                                            ->icon(Heroicon::Plus)
                                            ->action(function (Repeater $component): void {
                                                $state = $component->getState() ?? [];
                                                $state[(string) Str::uuid()] = [
                                                    'name' => '',
                                                    'sku' => '',
                                                    'price' => null,
                                                    'compare_at_price' => null,
                                                    'tax_class_id' => self::defaultTaxClassId(),
                                                    'selling_policy' => 'always',
                                                    'shippable' => false,
                                                    'presentation_icon' => null,
                                                    'presentation_image' => null,
                                                    'keygen_policy_id' => null,
                                                ];
                                                $component->state($state);
                                            }),
                                    ])
                                    ->default([
                                        [
                                            'name' => 'Standard license',
                                            'sku' => 'STANDARD',
                                        ],
                                    ])
                                    ->itemLabel(fn (array $state): string => (string) ($state['name'] ?? $state['sku'] ?? 'Variant'))
                                    ->schema([
                                        Hidden::make('id'),
                                        TextInput::make('name')
                                            ->label('Variant name')
                                            ->required()
                                            ->maxLength(255),
                                        TextInput::make('sku')
                                            ->label('SKU')
                                            ->required()
                                            ->maxLength(255),
                                        TextInput::make('price')
                                            ->label('Price')
                                            ->numeric()
                                            ->minValue(0)
                                            ->suffix('ETB'),
                                        TextInput::make('compare_at_price')
                                            ->label('Compare-at price')
                                            ->numeric()
                                            ->minValue(0)
                                            ->suffix('ETB'),
                                        Select::make('tax_class_id')
                                            ->label('Tax class')
                                            ->options(fn (): array => self::taxClassOptions())
                                            ->default(fn (): ?int => self::defaultTaxClassId())
                                            ->required(),
                                        Select::make('selling_policy')
                                            ->label('Selling policy')
                                            ->options([
                                                'always' => 'Always sell',
                                                'in_stock' => 'Only when in stock',
                                                'in_stock_or_on_backorder' => 'Stock or backorder',
                                            ])
                                            ->default('always')
                                            ->required(),
                                        Toggle::make('shippable')
                                            ->label('Physical / shippable')
                                            ->default(false),
                                        Select::make('presentation_icon')
                                            ->label('Storefront icon')
                                            ->options([
                                                'academic-cap' => 'Academic cap',
                                                'bolt' => 'Bolt',
                                                'briefcase' => 'Briefcase',
                                                'chart' => 'Chart',
                                                'code' => 'Code',
                                                'cloud' => 'Cloud',
                                                'puzzle-piece' => 'Puzzle piece',
                                                'shield' => 'Shield',
                                                'sparkles' => 'Sparkles',
                                                'cube' => 'Cube',
                                            ])
                                            ->searchable(),
                                        TextInput::make('presentation_image')
                                            ->label('Storefront image path or URL')
                                            ->url()
                                            ->maxLength(255),
                                        Select::make('keygen_policy_id')
                                            ->label('Variant policy override')
                                            ->options(fn (): array => self::keygenPolicyOptions())
                                            ->searchable()
                                            ->helperText('Optional. Leave empty to inherit the product policy.'),
                                    ]),
                            ]),
                        Tab::make('Licensing & fulfillment')
                            ->icon(Heroicon::OutlinedKey)
                            ->schema([
                                Group::make()
                                    ->columns(2)
                                    ->schema([
                                        Select::make('fulfillment_summary.type')
                                            ->label('Fulfillment')
                                            ->options([
                                                'none' => 'No automatic fulfillment',
                                                'license' => 'Generate a Keygen license',
                                            ])
                                            ->default('none')
                                            ->live()
                                            ->required(),
                                        Select::make('fulfillment_summary.provider')
                                            ->label('License provider')
                                            ->options([
                                                'keygen' => 'Keygen CE server',
                                                'fake-keygen' => 'Fake provider (testing only)',
                                            ])
                                            ->default('keygen')
                                            ->visible(fn (Get $get): bool => $get('fulfillment_summary.type') === 'license')
                                            ->required(),
                                        Select::make('keygen_product_id')
                                            ->label('Keygen product')
                                            ->options(fn (): array => self::keygenProductOptions())
                                            ->searchable()
                                            ->preload()
                                            ->live()
                                            ->visible(fn (Get $get): bool => $get('fulfillment_summary.type') === 'license')
                                            ->afterStateUpdated(fn (Set $set): mixed => $set('keygen_policy_id', null))
                                            ->createOptionForm([
                                                TextInput::make('name')->required()->maxLength(255),
                                                TextInput::make('code')
                                                    ->required()
                                                    ->maxLength(255)
                                                    ->default(fn (Get $get): string => Str::slug((string) $get('name'))),
                                                TextInput::make('url')->url()->nullable(),
                                                Select::make('distributionStrategy')->label('Distribution strategy')->options([
                                                    'LICENSED' => 'Licensed',
                                                    'OPEN' => 'Open',
                                                ])->default('LICENSED')->required(),
                                            ])
                                            ->createOptionUsing(function (array $data): string {
                                                $payload = app(KeygenClient::class)->createProduct(
                                                    name: (string) $data['name'],
                                                    code: (string) $data['code'],
                                                    url: filled($data['url'] ?? null) ? (string) $data['url'] : null,
                                                    distributionStrategy: (string) $data['distributionStrategy'],
                                                );

                                                return (string) data_get($payload, 'data.id');
                                            }),
                                        Select::make('keygen_policy_id')
                                            ->label('Default Keygen policy')
                                            ->options(fn (Get $get): array => self::keygenPolicyOptions($get('keygen_product_id')))
                                            ->searchable()
                                            ->visible(fn (Get $get): bool => $get('fulfillment_summary.type') === 'license')
                                            ->disabled(fn (Get $get): bool => blank($get('keygen_product_id')))
                                            ->createOptionForm([
                                                TextInput::make('name')->required()->maxLength(255),
                                                TextInput::make('duration')->numeric()->minValue(1)->nullable()
                                                    ->helperText('Days; leave empty for a perpetual policy.'),
                                                Select::make('scheme')->options([
                                                    'ED25519_SIGN' => 'Ed25519 signed',
                                                    'RSA_2048_PKCS1_SIGN' => 'RSA 2048 signed',
                                                    'RSA_4096_PKCS1_SIGN' => 'RSA 4096 signed',
                                                ])->default('ED25519_SIGN')->required(),
                                                Select::make('floating')->options([false => 'Node-locked', true => 'Floating'])
                                                    ->default(false)->required(),
                                                Select::make('protected')->options([false => 'Unprotected', true => 'Protected'])
                                                    ->default(false)->required(),
                                                Select::make('requireProductScope')->label('Require product scope')
                                                    ->options([false => 'No', true => 'Yes'])->default(false)->required(),
                                            ])
                                            ->createOptionUsing(function (array $data, Get $get): string {
                                                $productId = (string) $get('keygen_product_id');

                                                if ($productId === '') {
                                                    throw new \LogicException('Choose a Keygen product before creating a policy.');
                                                }

                                                $payload = app(KeygenClient::class)->createPolicy(
                                                    name: (string) $data['name'],
                                                    productId: $productId,
                                                    attributes: [
                                                        'duration' => filled($data['duration'] ?? null) ? (int) $data['duration'] : null,
                                                        'scheme' => (string) $data['scheme'],
                                                        'floating' => filter_var($data['floating'] ?? false, FILTER_VALIDATE_BOOLEAN),
                                                        'protected' => filter_var($data['protected'] ?? false, FILTER_VALIDATE_BOOLEAN),
                                                        'requireProductScope' => filter_var($data['requireProductScope'] ?? false, FILTER_VALIDATE_BOOLEAN),
                                                    ],
                                                );

                                                return (string) data_get($payload, 'data.id');
                                            }),
                                        TextInput::make('keygen_mapping_label')
                                            ->label('Mapping label')
                                            ->maxLength(255)
                                            ->visible(fn (Get $get): bool => $get('fulfillment_summary.type') === 'license'),
                                        Toggle::make('keygen_mapping_active')
                                            ->label('Active mapping')
                                            ->default(true)
                                            ->visible(fn (Get $get): bool => $get('fulfillment_summary.type') === 'license'),
                                    ]),
                            ]),
                        Tab::make('Review & publish')
                            ->icon(Heroicon::OutlinedClipboardDocumentCheck)
                            ->schema([
                                Group::make()
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
                            ]),
                    ])
                    ->extraAttributes(['class' => 'fi-product-tabs'])
                    ->columnSpanFull(),
            ]);
    }

    /** @return array<string, string> */
    private static function categoryOptions(): array
    {
        return Category::query()->orderBy('name')->pluck('name', 'name')->all();
    }

    /** @return array<string, string> */
    private static function taxClassOptions(): array
    {
        return TaxClass::query()->orderBy('name')->pluck('name', 'id')->all();
    }

    private static function defaultTaxClassId(): ?int
    {
        return TaxClass::query()->where('default', true)->value('id')
            ?? TaxClass::query()->value('id');
    }

    /** @return array<string, string> */
    private static function keygenProductOptions(): array
    {
        try {
            return collect(app(KeygenClient::class)->products())
                ->mapWithKeys(function (array $record): array {
                    $id = (string) ($record['id'] ?? '');
                    $name = (string) data_get($record, 'attributes.name', $id);

                    return $id === '' ? [] : [$id => $name];
                })
                ->all();
        } catch (Throwable) {
            return [];
        }
    }

    /** @return array<string, string> */
    private static function keygenPolicyOptions(?string $productId = null): array
    {
        try {
            return collect(app(KeygenClient::class)->policies($productId))
                ->mapWithKeys(function (array $record): array {
                    $id = (string) ($record['id'] ?? '');
                    $name = (string) data_get($record, 'attributes.name', $id);

                    return $id === '' ? [] : [$id => $name];
                })
                ->all();
        } catch (Throwable) {
            return [];
        }
    }
}
