<?php

namespace App\Filament\Resources\AppSubmissions\Pages;

use App\Filament\Resources\AppSubmissions\AppSubmissionResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListAppSubmissions extends ListRecords
{
    protected static string $resource = AppSubmissionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
