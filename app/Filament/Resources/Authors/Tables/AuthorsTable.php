<?php

namespace App\Filament\Resources\Authors\Tables;

use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class AuthorsTable
{
    public static function configure(Table $table): Table
    {
        return $table->columns([
            ImageColumn::make('avatar_path')->label('')->disk('public')->circular(),
            TextColumn::make('name')->searchable()->sortable()->weight('bold'),
            TextColumn::make('products_count')->counts('products')->label('Products')->sortable(),
            TextColumn::make('website_url')->label('Website')->limit(35)->url(fn ($state) => $state)->openUrlInNewTab(),
            IconColumn::make('is_public')->label('Public')->boolean(),
        ])->recordActions([EditAction::make()])->toolbarActions([DeleteBulkAction::make()]);
    }
}
