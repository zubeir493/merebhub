<?php

namespace App\Filament\Admin\Resources\LicenseMappings\Pages;

use App\Filament\Admin\Resources\LicenseMappings\LicenseMappingResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditLicenseMapping extends EditRecord
{
    protected static string $resource = LicenseMappingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
