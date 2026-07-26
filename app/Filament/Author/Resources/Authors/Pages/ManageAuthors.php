<?php

namespace App\Filament\Author\Resources\Authors\Pages;

use App\Filament\Author\Resources\Authors\AuthorResource;
use Filament\Resources\Pages\ManageRecords;

class ManageAuthors extends ManageRecords
{
    protected static string $resource = AuthorResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
