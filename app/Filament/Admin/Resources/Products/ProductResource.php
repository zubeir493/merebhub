<?php

namespace App\Filament\Admin\Resources\Products;

use App\Domain\Catalog\Enums\ProductPublicationState;
use App\Filament\Admin\Resources\Products\Pages\CreateProduct;
use App\Filament\Admin\Resources\Products\Pages\EditProduct;
use App\Filament\Admin\Resources\Products\Pages\ListProducts;
use App\Filament\Admin\Resources\Products\Schemas\ProductForm;
use App\Filament\Admin\Resources\Products\Tables\ProductsTable;
use App\Models\Product;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class ProductResource extends Resource
{
    protected static ?string $model = Product::class;

    protected static ?string $navigationLabel = 'Products';

    protected static UnitEnum|string|null $navigationGroup = 'Catalog review';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function form(Schema $schema): Schema
    {
        return ProductForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ProductsTable::configure($table);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with(['merchant', 'defaultUrl'])
            ->latest();
    }

    public static function publicationStateOptions(): array
    {
        return [
            ProductPublicationState::Draft->value => 'Draft',
            ProductPublicationState::Submitted->value => 'Submitted',
            ProductPublicationState::UnderReview->value => 'Under review',
            ProductPublicationState::ChangesRequested->value => 'Changes requested',
            ProductPublicationState::Approved->value => 'Approved',
            ProductPublicationState::Published->value => 'Published',
            ProductPublicationState::Rejected->value => 'Rejected',
            ProductPublicationState::Archived->value => 'Archived',
        ];
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
            'index' => ListProducts::route('/'),
            'create' => CreateProduct::route('/create'),
            'edit' => EditProduct::route('/{record}/edit'),
        ];
    }
}
