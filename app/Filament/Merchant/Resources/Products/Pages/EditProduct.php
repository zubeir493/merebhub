<?php

namespace App\Filament\Merchant\Resources\Products\Pages;

use App\Filament\Merchant\Resources\Products\ProductResource;
use Filament\Resources\Pages\EditRecord;

class EditProduct extends EditRecord
{
    protected static string $resource = ProductResource::class;

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $data['merchant_id'] = $this->getRecord()->merchant_id;

        return $data;
    }
}
