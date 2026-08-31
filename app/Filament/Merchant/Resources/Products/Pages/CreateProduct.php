<?php

namespace App\Filament\Merchant\Resources\Products\Pages;

use App\Filament\Merchant\Resources\Products\ProductResource;
use App\Models\User;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Validation\ValidationException;

class CreateProduct extends CreateRecord
{
    protected static string $resource = ProductResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $user = auth()->user();
        $merchantId = (int) ($data['merchant_id'] ?? 0);

        if (! $user instanceof User || ! $user->approvedMerchants()->whereKey($merchantId)->exists()) {
            throw ValidationException::withMessages([
                'merchant_id' => 'Select an approved merchant account you belong to.',
            ]);
        }

        $data['publication_state'] = 'draft';

        return $data;
    }
}
