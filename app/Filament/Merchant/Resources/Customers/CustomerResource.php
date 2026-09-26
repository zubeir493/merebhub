<?php

namespace App\Filament\Merchant\Resources\Customers;

use App\Filament\Merchant\Concerns\InteractsWithMerchantResources;
use App\Filament\Merchant\Resources\Customers\Pages\ListCustomers;
use App\Filament\Merchant\Resources\Customers\Tables\CustomersTable;
use App\Models\User;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Lunar\Core\Models\Customer;
use Lunar\Core\Models\Order;

class CustomerResource extends Resource
{
    use InteractsWithMerchantResources;

    protected static ?string $model = Customer::class;

    protected static ?string $navigationLabel = 'Customers';

    protected static ?int $navigationSort = 2;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUsers;

    public static function form(Schema $schema): Schema
    {
        return $schema;
    }

    public static function table(Table $table): Table
    {
        return CustomersTable::configure($table);
    }

    public static function getEloquentQuery(): Builder
    {
        $user = Auth::user();
        $customer = new Customer;
        $order = new Order;
        $customerTable = $customer->getTable();
        $orderTable = $order->getTable();

        if (! $user instanceof User) {
            return parent::getEloquentQuery()->whereKey(0);
        }

        $merchantOrders = $order->newQuery()
            ->whereIn($orderTable.'.id', static::merchantOrderIdsQuery())
            ->whereNotNull($orderTable.'.customer_id')
            ->select($orderTable.'.customer_id');

        $user = new User;
        $userTable = $user->getTable();
        $customerUserTable = (string) config('lunar.database.table_prefix', 'lunar_').'customer_user';
        $email = $user->newQuery()
            ->from($userTable.' as merchant_customer_users')
            ->join($customerUserTable.' as merchant_customer_links', 'merchant_customer_links.user_id', '=', 'merchant_customer_users.id')
            ->whereColumn('merchant_customer_links.customer_id', $customerTable.'.id')
            ->select('merchant_customer_users.email')
            ->limit(1);

        return parent::getEloquentQuery()
            ->whereIn($customerTable.'.id', $merchantOrders)
            ->select($customerTable.'.*')
            ->selectSub($email, 'email')
            ->withCount([
                'orders as merchant_orders_count' => fn (Builder $query): Builder => $query
                    ->whereIn($query->qualifyColumn('id'), static::merchantOrderIdsQuery()),
            ])
            ->withMax([
                'orders as merchant_last_order_at' => fn (Builder $query): Builder => $query
                    ->whereIn($query->qualifyColumn('id'), static::merchantOrderIdsQuery()),
            ], 'placed_at')
            ->latest('merchant_last_order_at');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCustomers::route('/'),
        ];
    }
}
