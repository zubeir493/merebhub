<?php

namespace App\Filament\Admin\Extensions;

use App\Domain\Fulfillment\Actions\RegenerateOrderLicensesAction;
use App\Models\Product;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Lunar\Core\Models\Order;
use Lunar\Core\Models\ProductVariant;
use Lunar\Core\States\Order\Fulfilment\FulfilmentStatus;
use Lunar\Core\States\Order\Payment\PaymentStatus;
use Lunar\Filament\Support\CustomerStatus;
use Throwable;

class OrderTableExtension
{
    public function configureTable(Table $table): Table
    {
        return $table
            ->columns(self::getOrderedColumns())
            ->recordActions([
                ActionGroup::make([
                    ...$table->getRecordActions(),
                    Action::make('regenerateLicenses')
                        ->label('Regenerate missing licenses')
                        ->icon('heroicon-o-arrow-path')
                        ->color('warning')
                        ->requiresConfirmation()
                        ->visible(fn (Order $record): bool => $record->placed_at !== null)
                        ->action(function (Order $record, RegenerateOrderLicensesAction $regenerate): void {
                            try {
                                $result = $regenerate->handle($record);
                            } catch (Throwable $exception) {
                                report($exception);
                                Notification::make()
                                    ->title('License regeneration failed')
                                    ->body($exception->getMessage())
                                    ->danger()
                                    ->send();

                                return;
                            }

                            if ($result['failed'] !== []) {
                                Notification::make()
                                    ->title('Some licenses still need attention')
                                    ->body(sprintf(
                                        '%d attempted, %d generated, %d failed.',
                                        $result['attempted'],
                                        $result['succeeded'],
                                        count($result['failed']),
                                    ))
                                    ->warning()
                                    ->send();

                                return;
                            }

                            if ($result['attempted'] === 0) {
                                Notification::make()
                                    ->title('No missing licenses found')
                                    ->info()
                                    ->send();

                                return;
                            }

                            Notification::make()
                                ->title('Missing licenses regenerated')
                                ->body(sprintf('%d license(s) generated successfully.', $result['succeeded']))
                                ->success()
                                ->send();
                        }),
                ])
                    ->label('Actions')
                    ->icon(Heroicon::OutlinedEllipsisVertical)
                    ->tooltip('Order actions')
                    ->color('gray'),
            ]);
    }

    /**
     * @return array<int, TextColumn>
     */
    public static function getOrderedColumns(): array
    {
        return [
            TextColumn::make('reference')
                ->label(__('lunar-filament::order.table.reference.label'))
                ->searchable()
                ->sortable()
                ->copyable(),
            TextColumn::make('billingAddress.fullName')
                ->label(__('lunar-filament::order.table.customer.label'))
                ->description(fn (Order $record): ?string => $record->billingAddress?->contact_email)
                ->searchable(['first_name', 'last_name']),
            TextColumn::make('closed_at')
                ->label(__('lunar-filament::order.table.status.label'))
                ->state(fn (Order $record): string => __('lunar::states.order.'.$record->lifecycleStatus()))
                ->color(fn (Order $record): string => match ($record->lifecycleStatus()) {
                    'cancelled' => 'danger',
                    'closed' => 'gray',
                    default => 'success',
                })
                ->badge(),
            TextColumn::make('payment_status')
                ->label(__('lunar-filament::order.table.payment_status.label'))
                ->formatStateUsing(fn ($state) => $state instanceof PaymentStatus ? $state->label() : (string) $state)
                ->badge(),
            TextColumn::make('total')
                ->label(__('lunar-filament::order.table.total.label'))
                ->formatStateUsing(fn ($state, $record): string => $record->format('total'))
                ->sortable(),
            TextColumn::make('placed_at')
                ->label(__('lunar-filament::order.table.date.label'))
                ->dateTime()
                ->sortable(),
            TextColumn::make('fulfilment_status')
                ->label(__('lunar-filament::order.table.fulfilment_status.label'))
                ->toggleable(isToggledHiddenByDefault: true)
                ->formatStateUsing(fn ($state) => $state instanceof FulfilmentStatus ? $state->label() : (string) $state)
                ->badge(),
            TextColumn::make('customer_reference')
                ->label(__('lunar-filament::order.table.customer_reference.label'))
                ->toggleable(isToggledHiddenByDefault: true)
                ->searchable(),
            TextColumn::make('new_customer')
                ->label(__('lunar-filament::order.table.new_customer.label'))
                ->toggleable(isToggledHiddenByDefault: true)
                ->formatStateUsing(fn (bool $state) => CustomerStatus::getLabel($state))
                ->color(fn (bool $state) => CustomerStatus::getColor($state))
                ->icon(fn (bool $state) => CustomerStatus::getIcon($state))
                ->badge(),
            TextColumn::make('tags.value')
                ->label(__('lunar-filament::order.table.tags.label'))
                ->badge()
                ->toggleable(isToggledHiddenByDefault: true)
                ->separator(','),
            TextColumn::make('billingAddress.postcode')
                ->label(__('lunar-filament::order.table.postcode.label'))
                ->toggleable(isToggledHiddenByDefault: true)
                ->searchable(),
            TextColumn::make('billingAddress.contact_email')
                ->label(__('lunar-filament::order.table.email.label'))
                ->toggleable(isToggledHiddenByDefault: true)
                ->copyable()
                ->searchable(),
            TextColumn::make('billingAddress.contact_phone')
                ->label(__('lunar-filament::order.table.phone.label'))
                ->toggleable(isToggledHiddenByDefault: true),
        ];
    }

    /**
     * @param  array<int, mixed>  $columns
     * @return array<int, mixed>
     */
    public function extendOrderLinesTableColumns(array $columns): array
    {
        return [
            ...$columns,
            TextColumn::make('variant_name')
                ->label('Variant')
                ->placeholder('Standard license')
                ->getStateUsing(function ($record): string {
                    $variant = $record->purchasable;

                    return $variant instanceof ProductVariant
                        ? Product::displayVariantName($variant)
                        : ($record->option ?: 'Standard license');
                }),
        ];
    }
}
