<?php

namespace App\Filament\Admin\Resources\Products\Pages;

use App\Domain\Catalog\Actions\SyncProductConfigurationAction;
use App\Domain\Catalog\Actions\SyncProductDownloadsAction;
use App\Filament\Admin\Resources\Products\ProductResource;
use Filament\Resources\Pages\CreateRecord;

class CreateProduct extends CreateRecord
{
    protected static string $resource = ProductResource::class;

    /** @var array<string, mixed> */
    protected array $productConfiguration = [];

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $this->productConfiguration = $data;

        unset(
            $data['catalog_category'],
            $data['catalog_platform'],
            $data['default_price'],
            $data['default_compare_at_price'],
            $data['variants_data'],
            $data['keygen_product_id'],
            $data['keygen_policy_id'],
            $data['keygen_mapping_label'],
            $data['keygen_mapping_active'],
            $data['downloadable_files'],
            $data['downloadable_file_names'],
        );

        return $data;
    }

    protected function afterCreate(): void
    {
        app(SyncProductConfigurationAction::class)->handle($this->record, $this->productConfiguration);
        app(SyncProductDownloadsAction::class)->handle(
            $this->record,
            $this->productConfiguration['downloadable_files'] ?? [],
            $this->productConfiguration['downloadable_file_names'] ?? [],
        );
    }
}
