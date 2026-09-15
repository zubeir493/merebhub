<?php

namespace App\Filament\Admin\Resources\LicenseMappings;

use App\Filament\Admin\Resources\LicenseMappings\Pages\CreateLicenseMapping;
use App\Filament\Admin\Resources\LicenseMappings\Pages\EditLicenseMapping;
use App\Filament\Admin\Resources\LicenseMappings\Pages\ListLicenseMappings;
use App\Filament\Admin\Resources\LicenseMappings\Schemas\LicenseMappingForm;
use App\Filament\Admin\Resources\LicenseMappings\Tables\LicenseMappingsTable;
use App\Models\LicenseMapping;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class LicenseMappingResource extends Resource
{
    protected static ?string $model = LicenseMapping::class;

    protected static ?string $navigationLabel = 'Licenses';

    protected static UnitEnum|string|null $navigationGroup = 'Fulfillment';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedKey;

    public static function form(Schema $schema): Schema
    {
        return LicenseMappingForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return LicenseMappingsTable::configure($table);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with(['product', 'productVariant'])
            ->latest();
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
            'index' => ListLicenseMappings::route('/'),
            'create' => CreateLicenseMapping::route('/create'),
            'edit' => EditLicenseMapping::route('/{record}/edit'),
        ];
    }
}
