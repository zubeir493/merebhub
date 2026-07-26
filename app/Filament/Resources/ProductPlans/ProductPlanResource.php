<?php

namespace App\Filament\Resources\ProductPlans;

use App\Enums\BillingInterval;
use App\Enums\BillingModel;
use App\Enums\FulfillmentType;
use App\Filament\Resources\ProductPlans\Pages\ManageProductPlans;
use App\Models\ProductPlan;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Str;

class ProductPlanResource extends Resource
{
    protected static ?string $model = ProductPlan::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleGroup;

    protected static ?string $navigationLabel = 'Product plans';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('product_id')->relationship('product', 'name')->searchable()->preload()->required(),
                TextInput::make('name')
                    ->required()
                    ->live(onBlur: true)
                    ->afterStateUpdated(fn ($state, $set) => $set('slug', Str::slug($state))),
                TextInput::make('slug')->required(),
                Textarea::make('description')->columnSpanFull(),
                TextInput::make('price_minor')->label('Price (minor units)')->numeric()->minValue(0)->required()->helperText('100 = 1.00 ETB'),
                TextInput::make('currency')->default('ETB')->disabled()->dehydrated(),
                Select::make('billing_model')->options(BillingModel::class)->required(),
                Select::make('billing_interval')->options(BillingInterval::class),
                Select::make('license_type')->options([
                    'perpetual' => 'Perpetual',
                    'fixed_term' => 'Fixed term',
                    'trial' => 'Trial',
                ])->required(),
                TextInput::make('license_duration_days')->numeric()->minValue(1),
                TextInput::make('activation_limit')->numeric()->minValue(1)->required(),
                Select::make('fulfillment_type')->options(FulfillmentType::class)->required(),
                TextInput::make('support_duration_days')->numeric()->minValue(1),
                TextInput::make('update_duration_days')->numeric()->minValue(1),
                TextInput::make('keygen_policy_id')->maxLength(255),
                KeyValue::make('entitlements')->columnSpanFull(),
                TextInput::make('sort_order')->numeric()->minValue(0),
                Toggle::make('is_active')->default(true),
            ])
            ->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('product.name')->searchable()->sortable(),
                TextColumn::make('name')->searchable()->weight('bold'),
                TextColumn::make('price_minor')->label('Price')->formatStateUsing(fn (int $state): string => number_format($state / 100, 2).' ETB'),
                TextColumn::make('billing_model')->badge(),
                TextColumn::make('fulfillment_type')->badge(),
                IconColumn::make('is_active')->boolean(),
            ])
            ->recordActions([
                EditAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageProductPlans::route('/'),
        ];
    }
}
