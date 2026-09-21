<?php

namespace App\Filament;

use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Support\Icons\Heroicon;

class ProductMediaSection
{
    public static function make(): Tabs
    {
        return Tabs::make('Product assets')
            ->tabs([
                Tab::make('Gallery')
                    ->icon(Heroicon::OutlinedPhoto)
                    ->schema([
                        SpatieMediaLibraryFileUpload::make('product_gallery')
                            ->label('Product gallery')
                            ->helperText('Add up to eight screenshots or product images. Drag to reorder; the first image becomes the cover.')
                            ->collection(config('lunar.media.collection'))
                            ->disk((string) config('marketplace.public_media_disk', 'public'))
                            ->visibility('public')
                            ->multiple()
                            ->appendFiles()
                            ->reorderable()
                            ->image()
                            ->imageEditor()
                            ->imageEditorAspectRatios([
                                null,
                                '16:10',
                                '4:3',
                                '1:1',
                            ])
                            ->panelLayout('grid')
                            ->imagePreviewHeight('180')
                            ->maxParallelUploads(4)
                            ->uploadingMessage('Uploading gallery images…')
                            ->maxFiles(8)
                            ->maxSize(10240),
                    ]),
                Tab::make('Customer downloads')
                    ->icon(Heroicon::OutlinedFolderArrowDown)
                    ->schema([
                        ProductDownloadUpload::make(),
                    ]),
            ])
            ->columnSpanFull();
    }
}
