<?php

namespace App\Filament\Merchant\Resources\Orders\Pages;

use App\Filament\Merchant\Resources\Orders\OrderResource;
use Filament\Resources\Pages\ListRecords;

class ListOrders extends ListRecords
{
    protected static string $resource = OrderResource::class;
}
