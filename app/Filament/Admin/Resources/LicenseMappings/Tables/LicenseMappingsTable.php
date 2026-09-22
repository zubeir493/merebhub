<?php

namespace App\Filament\Admin\Resources\LicenseMappings\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class LicenseMappingsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('product.name')
                    ->label('Product')
                    ->description(fn ($record): string => $record->productVariant ? 'SKU: '.$record->productVariant->sku : 'All variants')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('label')
                    ->placeholder('—'),
                TextColumn::make('keygen_policy_id')
                    ->label('Keygen policy')
                    ->copyable()
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                IconColumn::make('active')
                    ->boolean()
                    ->label('Active'),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->label('Last updated'),
            ])
            ->filters([])
            ->recordActions([
                EditAction::make()
                    ->iconButton()
                    ->tooltip('Edit license mapping'),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
