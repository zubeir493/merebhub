<?php

namespace App\Filament\Admin\Resources\FulfillmentUnits;

use App\Filament\Admin\Resources\FulfillmentUnits\Pages\ListFulfillmentUnits;
use App\Filament\Admin\Resources\FulfillmentUnits\Tables\FulfillmentUnitsTable;
use App\Models\FulfillmentUnit;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class FulfillmentUnitResource extends Resource
{
    protected static ?string $model = FulfillmentUnit::class;

    protected static ?string $navigationLabel = 'Fulfillment recovery';

    protected static UnitEnum|string|null $navigationGroup = null;

    protected static ?int $navigationSort = 100;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedWrenchScrewdriver;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public static function table(Table $table): Table
    {
        return FulfillmentUnitsTable::configure($table);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with(['order', 'product'])
            ->withCount('attempts')
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
            'index' => ListFulfillmentUnits::route('/'),
        ];
    }
}
