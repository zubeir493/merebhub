<?php

namespace App\Filament\Admin\Resources\FulfillmentUnits\Pages;

use App\Filament\Admin\Resources\FulfillmentUnits\FulfillmentUnitResource;
use Filament\Resources\Pages\ListRecords;

class ListFulfillmentUnits extends ListRecords
{
    protected static string $resource = FulfillmentUnitResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
