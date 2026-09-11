<?php

namespace App\Filament\Admin\Resources\FulfillmentUnits\Tables;

use App\Domain\Fulfillment\Actions\ProvisionFulfillmentUnitAction;
use App\Domain\Fulfillment\Enums\FulfillmentUnitStatus;
use App\Models\FulfillmentUnit;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Throwable;

class FulfillmentUnitsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('public_id')
                    ->label('Fulfillment unit')
                    ->copyable()
                    ->searchable(),
                TextColumn::make('order.reference')
                    ->label('Order')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('product.name')
                    ->label('Product')
                    ->searchable(),
                TextColumn::make('provider')
                    ->badge(),
                TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn (mixed $state): string => str($state instanceof BackedEnum ? $state->value : $state)->replace('_', ' ')->title())
                    ->color(fn (mixed $state): string => match ($state instanceof BackedEnum ? $state->value : $state) {
                        FulfillmentUnitStatus::Completed->value => 'success',
                        FulfillmentUnitStatus::NeedsAttention->value => 'danger',
                        FulfillmentUnitStatus::Processing->value => 'warning',
                        default => 'gray',
                    })
                    ->sortable(),
                TextColumn::make('attempts_count')
                    ->label('Attempts')
                    ->sortable(),
                TextColumn::make('external_id')
                    ->label('Provider ID')
                    ->placeholder('—')
                    ->copyable(),
                TextColumn::make('last_error')
                    ->label('Last error')
                    ->limit(60)
                    ->placeholder('—'),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        FulfillmentUnitStatus::Pending->value => 'Pending',
                        FulfillmentUnitStatus::Processing->value => 'Processing',
                        FulfillmentUnitStatus::Completed->value => 'Completed',
                        FulfillmentUnitStatus::NeedsAttention->value => 'Needs attention',
                    ]),
            ])
            ->recordActions([
                Action::make('retry')
                    ->label('Retry provisioning')
                    ->icon('heroicon-o-arrow-path')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->visible(fn (FulfillmentUnit $record): bool => $record->status === FulfillmentUnitStatus::NeedsAttention)
                    ->action(function (FulfillmentUnit $record, ProvisionFulfillmentUnitAction $provision): void {
                        try {
                            $provision->handle($record);
                        } catch (Throwable $exception) {
                            report($exception);
                            Notification::make()
                                ->title('Provisioning still needs attention')
                                ->danger()
                                ->send();

                            return;
                        }

                        Notification::make()
                            ->title('Provisioning retried successfully')
                            ->success()
                            ->send();
                    }),
            ]);
    }
}
