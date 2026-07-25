<?php

namespace App\Filament\Resources\Platforms\Tables;

use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class PlatformsTable
{
    public static function configure(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('name')->searchable()->sortable()->weight('bold'),
            TextColumn::make('slug')->searchable(),
            TextColumn::make('products_count')->counts('products')->label('Products')->sortable(),
        ])->recordActions([EditAction::make()])->toolbarActions([DeleteBulkAction::make()]);
    }
}
