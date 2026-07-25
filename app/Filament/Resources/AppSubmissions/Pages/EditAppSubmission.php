<?php

namespace App\Filament\Resources\AppSubmissions\Pages;

use App\Filament\Resources\AppSubmissions\AppSubmissionResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditAppSubmission extends EditRecord
{
    protected static string $resource = AppSubmissionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}
