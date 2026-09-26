<?php

namespace App\Filament\Merchant\Resources\Orders;

use App\Filament\Merchant\Concerns\InteractsWithMerchantResources;
use App\Filament\Merchant\Resources\Orders\Pages\ListOrders;
use App\Filament\Merchant\Resources\Orders\Tables\OrdersTable;
use App\Models\User;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Lunar\Core\Models\Order;

class OrderResource extends Resource
{
    use InteractsWithMerchantResources;

    protected static ?string $model = Order::class;

    protected static ?string $navigationLabel = 'Orders';

    protected static ?int $navigationSort = 1;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedShoppingBag;

    public static function form(Schema $schema): Schema
    {
        return $schema;
    }

    public static function table(Table $table): Table
    {
        return OrdersTable::configure($table);
    }

    public static function getEloquentQuery(): Builder
    {
        $user = Auth::user();
        $order = new Order;
        $orderTable = $order->getTable();

        if (! $user instanceof User) {
            return parent::getEloquentQuery()->whereKey(0);
        }

        $merchantSales = static::merchantOrderLineQuery()
            ->whereColumn('merchant_resource_lines.order_id', $orderTable.'.id')
            ->selectRaw('COALESCE(SUM(merchant_resource_lines.total), 0)');

        return parent::getEloquentQuery()
            ->whereIn($orderTable.'.id', static::merchantOrderIdsQuery())
            ->select($orderTable.'.*')
            ->selectSub($merchantSales, 'merchant_sales_total')
            ->with('customer')
            ->latest('placed_at');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListOrders::route('/'),
        ];
    }
}
