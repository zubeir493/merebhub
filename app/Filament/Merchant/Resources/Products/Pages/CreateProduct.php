<?php

namespace App\Filament\Merchant\Resources\Products\Pages;

use App\Domain\Catalog\Actions\SyncProductDownloadsAction;
use App\Filament\Merchant\Resources\Products\ProductResource;
use App\Models\User;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Validation\ValidationException;

class CreateProduct extends CreateRecord
{
    protected static string $resource = ProductResource::class;

    /** @var array<string, mixed> */
    protected array $downloadConfiguration = [];

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $user = auth()->user();
        $merchantId = (int) ($data['merchant_id'] ?? 0);

        if (! $user instanceof User || ! $user->approvedMerchants()->whereKey($merchantId)->exists()) {
            throw ValidationException::withMessages([
                'merchant_id' => 'Select an approved merchant account you belong to.',
            ]);
        }

        $this->downloadConfiguration = $data;
        unset($data['downloadable_files'], $data['downloadable_file_names']);

        $data['publication_state'] = 'draft';

        return $data;
    }

    protected function afterCreate(): void
    {
        app(SyncProductDownloadsAction::class)->handle(
            $this->record,
            $this->downloadConfiguration['downloadable_files'] ?? [],
            $this->downloadConfiguration['downloadable_file_names'] ?? [],
        );
    }
}
