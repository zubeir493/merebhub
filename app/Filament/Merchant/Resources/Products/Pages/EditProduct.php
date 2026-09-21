<?php

namespace App\Filament\Merchant\Resources\Products\Pages;

use App\Domain\Catalog\Actions\SyncProductDownloadsAction;
use App\Filament\Merchant\Resources\Products\ProductResource;
use App\Models\Product;
use Filament\Resources\Pages\EditRecord;

class EditProduct extends EditRecord
{
    protected static string $resource = ProductResource::class;

    /** @var array<string, mixed> */
    protected array $downloadConfiguration = [];

    protected function mutateFormDataBeforeFill(array $data): array
    {
        /** @var Product $product */
        $product = $this->record;
        $downloadableAssets = $product->downloadableAssets()->get();

        return [
            ...$data,
            'downloadable_files' => $downloadableAssets->pluck('path')->all(),
            'downloadable_file_names' => $downloadableAssets->pluck('filename', 'path')->all(),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $this->downloadConfiguration = $data;
        unset($data['downloadable_files'], $data['downloadable_file_names']);
        $data['merchant_id'] = $this->getRecord()->merchant_id;

        return $data;
    }

    protected function afterSave(): void
    {
        app(SyncProductDownloadsAction::class)->handle(
            $this->record,
            $this->downloadConfiguration['downloadable_files'] ?? [],
            $this->downloadConfiguration['downloadable_file_names'] ?? [],
        );
    }
}
