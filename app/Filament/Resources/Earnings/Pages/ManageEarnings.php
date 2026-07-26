<?php

namespace App\Filament\Resources\Earnings\Pages;

use App\Filament\Resources\Earnings\EarningResource;
use Filament\Resources\Pages\ManageRecords;

class ManageEarnings extends ManageRecords
{
    protected static string $resource = EarningResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
