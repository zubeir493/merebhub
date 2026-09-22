<?php

namespace App\Filament\Admin\Resources\Merchants;

use App\Filament\Admin\Resources\Merchants\Pages\CreateMerchant;
use App\Filament\Admin\Resources\Merchants\Pages\EditMerchant;
use App\Filament\Admin\Resources\Merchants\Pages\ListMerchants;
use App\Filament\Admin\Resources\Merchants\Schemas\MerchantForm;
use App\Filament\Admin\Resources\Merchants\Tables\MerchantsTable;
use App\Models\Merchant;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class MerchantResource extends Resource
{
    protected static ?string $model = Merchant::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBuildingStorefront;

    protected static ?string $navigationLabel = 'Merchants';

    protected static UnitEnum|string|null $navigationGroup = null;

    protected static ?int $navigationSort = 75;

    protected static ?string $recordTitleAttribute = 'display_name';

    public static function form(Schema $schema): Schema
    {
        return MerchantForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return MerchantsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListMerchants::route('/'),
            'create' => CreateMerchant::route('/create'),
            'edit' => EditMerchant::route('/{record}/edit'),
        ];
    }
}
