<?php

namespace App\Filament\Merchant\Resources\Products;

use App\Domain\Catalog\Enums\ProductPublicationState;
use App\Filament\Merchant\Resources\Products\Pages\CreateProduct;
use App\Filament\Merchant\Resources\Products\Pages\EditProduct;
use App\Filament\Merchant\Resources\Products\Pages\ListProducts;
use App\Filament\Merchant\Resources\Products\Schemas\ProductForm;
use App\Filament\Merchant\Resources\Products\Tables\ProductsTable;
use App\Models\Merchant;
use App\Models\Product;
use App\Models\User;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use UnitEnum;

class ProductResource extends Resource
{
    protected static ?string $model = Product::class;

    protected static ?string $navigationLabel = 'My products';

    protected static UnitEnum|string|null $navigationGroup = 'Catalog';

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
        $user = Auth::user();

        if (! $user instanceof User) {
            return parent::getEloquentQuery()->whereKey(0);
        }

        return parent::getEloquentQuery()
            ->whereIn(
                (new Product)->qualifyColumn('merchant_id'),
                $user->approvedMerchants()->select((new Merchant)->qualifyColumn('id')),
            )
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
