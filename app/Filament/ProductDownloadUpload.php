<?php

namespace App\Filament;

use App\Models\Product;
use Filament\Forms\Components\FileUpload;

class ProductDownloadUpload
{
    public static function make(): FileUpload
    {
        return FileUpload::make('downloadable_files')
            ->label('Downloadable files')
            ->helperText('Add up to ten installers or archives. Only customers with an active purchase can download them.')
            ->disk((string) config('marketplace.private_files_disk', 'private'))
            ->directory('downloads/products')
            ->visibility('private')
            ->multiple()
            ->appendFiles()
            ->panelLayout('compact')
            ->maxParallelUploads(3)
            ->uploadingMessage('Uploading customer files…')
            ->storeFileNamesIn('downloadable_file_names')
            ->acceptedFileTypes([
                'application/octet-stream',
                'application/zip',
                'application/x-zip-compressed',
                'application/x-7z-compressed',
                'application/x-rar-compressed',
                'application/x-tar',
                'application/gzip',
                'application/x-gzip',
                'application/x-dosexec',
                'application/x-msdownload',
                'application/vnd.microsoft.portable-executable',
                'application/x-msi',
                'application/x-apple-diskimage',
                'application/vnd.debian.binary-package',
                'application/x-rpm',
                'application/vnd.android.package-archive',
            ])
            ->maxFiles(10)
            ->maxSize(102400)
            ->previewable(false)
            ->openable(false)
            ->downloadable(false)
            ->preventFilePathTampering(
                allowFilePathUsing: fn (string $file, ?Product $record): bool => $record?->downloadableAssets()
                    ->where('path', $file)
                    ->exists() ?? false,
            )
            ->columnSpanFull();
    }
}
