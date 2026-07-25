<?php

namespace App\Filament\Resources\Orders\Tables;

use App\Enums\OrderStatus;
use App\Jobs\ProvisionOrderLicenseJob;
use App\Models\Order;
use Filament\Actions\Action;
use Filament\Actions\ViewAction;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class OrdersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('wc_order_id')->label('Order')->prefix('#')->searchable()->sortable()->weight('bold'),
                TextColumn::make('product.name')->searchable()->sortable(),
                TextColumn::make('buyer_email')->searchable(),
                TextColumn::make('amount')->money(fn (Order $record) => $record->currency)->sortable(),
                TextColumn::make('status')->badge()->sortable(),
                IconColumn::make('license.id')->label('Licensed')->boolean(),
                TextColumn::make('paid_at')->label('Paid')->dateTime()->placeholder('—')->sortable(),
                TextColumn::make('fulfillment_error')->label('Issue')->limit(40)->color('danger')->toggleable(),
            ])
            ->filters([
                SelectFilter::make('status')->options(OrderStatus::class),
            ])
            ->recordActions([
                Action::make('retryFulfillment')
                    ->label('Retry license')
                    ->icon(Heroicon::ArrowPath)
                    ->visible(fn (Order $record): bool => $record->status === OrderStatus::Paid && ! $record->license)
                    ->action(function (Order $record): void {
                        ProvisionOrderLicenseJob::dispatch($record->id);
                        Notification::make()->title('License delivery queued')->success()->send();
                    }),
                ViewAction::make(),
            ]);
    }
}
