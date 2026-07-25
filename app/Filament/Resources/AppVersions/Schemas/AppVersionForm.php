<?php

namespace App\Filament\Resources\AppVersions\Schemas;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class AppVersionForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('product_id')->relationship('product', 'name')->searchable()->preload()->required(),
            TextInput::make('version_number')->required()->maxLength(50),
            FileUpload::make('file_path')
                ->label('Build file')
                ->disk(config('filesystems.builds_disk'))
                ->directory('product-builds')
                ->downloadable()
                ->required()
                ->columnSpanFull(),
            Hidden::make('file_size')->default(0),
            Textarea::make('changelog')->rows(6)->columnSpanFull(),
        ])->columns(2);
    }
}
