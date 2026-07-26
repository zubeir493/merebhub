<?php

namespace App\Filament\Resources\Products\Tables;

use App\Enums\ProductStatus;
use App\Models\Product;
use Filament\Actions\ActionGroup;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class ProductsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('updated_at', 'desc')
            ->columns([
                ImageColumn::make('cover_path')
                    ->label('')
                    ->getStateUsing(fn (Product $record): ?string => $record->coverUrl())
                    ->square()
                    ->imageSize(44),
                TextColumn::make('name')->searchable()->sortable()->weight('bold')->description(fn (Product $record) => $record->author->name),
                TextColumn::make('category')->badge()->searchable(),
                TextColumn::make('price')->money('ETB')->sortable(),
                TextColumn::make('status')->badge()->sortable(),
                IconColumn::make('is_featured')->label('Featured')->boolean(),
                TextColumn::make('weekly_sales')->label('7-day sales')->numeric()->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')->options(ProductStatus::class),
                SelectFilter::make('author')->relationship('author', 'name')->searchable()->preload(),
            ])
            ->recordActions([
                ActionGroup::make([
                    ViewAction::make(),
                    EditAction::make(),
                ]),
            ]);
    }
}
