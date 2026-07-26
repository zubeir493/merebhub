<?php

namespace App\Filament\Author\Resources\Earnings\Pages;

use App\Filament\Author\Resources\Earnings\EarningResource;
use Filament\Resources\Pages\ManageRecords;

class ManageEarnings extends ManageRecords
{
    protected static string $resource = EarningResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
