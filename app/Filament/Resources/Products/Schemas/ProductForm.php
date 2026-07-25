<?php

namespace App\Filament\Resources\Products\Schemas;

use App\Enums\ProductStatus;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class ProductForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Listing')
                ->columns(2)
                ->schema([
                    TextInput::make('name')
                        ->required()
                        ->live(onBlur: true)
                        ->afterStateUpdated(fn ($state, $set) => $set('slug', Str::slug($state)))
                        ->maxLength(255),
                    TextInput::make('slug')->required()->unique(ignoreRecord: true),
                    TextInput::make('tagline')->required()->maxLength(255)->columnSpanFull(),
                    Select::make('author_id')->relationship('author', 'name')->searchable()->preload()->required(),
                    TextInput::make('category')->required()->datalist(['Developer tools', 'Productivity', 'Business', 'Design', 'Marketing', 'Data & analytics', 'Security', 'Utilities', 'Games']),
                    Select::make('platforms')->relationship('platforms', 'name')->multiple()->preload()->searchable()->required()->columnSpanFull(),
                    Textarea::make('description')->rows(8)->required()->columnSpanFull(),
                ]),
            Section::make('Pricing and publishing')
                ->columns(3)
                ->schema([
                    TextInput::make('price')->required()->numeric()->minValue(0)->suffix('ETB'),
                    TextInput::make('compare_at_price')->numeric()->minValue(0)->suffix('ETB'),
                    Select::make('status')->options(ProductStatus::class)->required()->default(ProductStatus::Draft),
                    Toggle::make('is_featured')->label('Featured on homepage'),
                    TextInput::make('weekly_sales')->numeric()->minValue(0)->default(0),
                    TextInput::make('keygen_policy_id')->label('Keygen policy ID')->helperText('Overrides the environment default.'),
                ]),
            Section::make('Media and integrations')
                ->columns(2)
                ->schema([
                    FileUpload::make('cover_path')
                        ->image()
                        ->disk('public')
                        ->directory('product-covers')
                        ->imageEditor()
                        ->required(fn (string $operation): bool => $operation === 'create'),
                    FileUpload::make('icon_path')->image()->disk('public')->directory('product-icons')->imageEditor(),
                    TextInput::make('wc_product_id')->label('WooCommerce product ID')->numeric()->disabled()->dehydrated(),
                ]),
        ]);
    }
}
