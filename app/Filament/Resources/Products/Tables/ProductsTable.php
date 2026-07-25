<?php

namespace App\Filament\Resources\Products\Tables;

use App\Enums\ProductStatus;
use App\Models\Product;
use App\Services\WooCommerceService;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Filament\Actions\ActionGroup;
use Throwable;

class ProductsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('updated_at', 'desc')
            ->columns([
                ImageColumn::make('cover_path')
                    ->label('')
                    ->getStateUsing(fn(Product $record): ?string => $record->coverUrl())
                    ->square()
                    ->imageSize(44),
                TextColumn::make('name')->searchable()->sortable()->weight('bold')->description(fn(Product $record) => $record->author->name),
                TextColumn::make('category')->badge()->searchable(),
                TextColumn::make('price')->money('ETB')->sortable(),
                TextColumn::make('status')->badge()->sortable(),
                IconColumn::make('is_featured')->label('Featured')->boolean(),
                TextColumn::make('wc_product_id')->label('Woo ID')->placeholder('Not synced')->sortable(),
                TextColumn::make('weekly_sales')->label('7-day sales')->numeric()->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')->options(ProductStatus::class),
                SelectFilter::make('author')->relationship('author', 'name')->searchable()->preload(),
            ])
            ->recordActions([
                ActionGroup::make([
                    Action::make('sync')
                        ->label('Sync')
                        ->icon(Heroicon::ArrowPath)
                        ->requiresConfirmation()
                        ->action(function (Product $record, WooCommerceService $woocommerce): void {
                            try {
                                $response = $woocommerce->syncProduct($record);
                                $record->update(['wc_product_id' => $response['id']]);
                                Notification::make()->title('WooCommerce product synced')->success()->send();
                            } catch (Throwable $exception) {
                                report($exception);
                                Notification::make()->title('WooCommerce sync failed')->body($exception->getMessage())->danger()->persistent()->send();
                            }
                        }),
                    ViewAction::make(),
                    EditAction::make(),
                ])
            ]);
    }
}
