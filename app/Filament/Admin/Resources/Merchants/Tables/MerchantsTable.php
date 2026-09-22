<?php

namespace App\Filament\Admin\Resources\Merchants\Tables;

use App\Domain\Merchants\Enums\MerchantStatus;
use App\Domain\Merchants\Enums\MerchantType;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class MerchantsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('display_name')
                    ->label('Merchant')
                    ->description(fn ($record): ?string => $record->legal_name ?: null)
                    ->searchable()
                    ->sortable(),
                TextColumn::make('type')
                    ->label('Type')
                    ->badge()
                    ->formatStateUsing(fn ($state): string => match ($state instanceof MerchantType ? $state : MerchantType::tryFrom((string) $state)) {
                        MerchantType::LocalDeveloper => 'Local Developer',
                        MerchantType::GlobalPartner => 'Global Partner',
                        default => (string) $state,
                    }),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn ($state): string => match ($state instanceof MerchantStatus ? $state : MerchantStatus::tryFrom((string) $state)) {
                        MerchantStatus::Approved => 'success',
                        MerchantStatus::Pending => 'warning',
                        MerchantStatus::Suspended => 'danger',
                        MerchantStatus::Rejected => 'gray',
                        default => 'gray',
                    }),
                TextColumn::make('products_count')
                    ->label('Products')
                    ->counts('products')
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label('Registered')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                ActionGroup::make([
                    EditAction::make(),
                    Action::make('approve')
                        ->label('Approve')
                        ->icon(Heroicon::CheckCircle)
                        ->color('success')
                        ->requiresConfirmation()
                        ->visible(fn ($record): bool => $record->status !== MerchantStatus::Approved)
                        ->action(function ($record): void {
                            $record->update([
                                'status' => MerchantStatus::Approved,
                                'approved_at' => now(),
                            ]);
                            Notification::make()->title('Merchant approved')->success()->send();
                        }),
                    Action::make('suspend')
                        ->label('Suspend')
                        ->icon(Heroicon::NoSymbol)
                        ->color('danger')
                        ->requiresConfirmation()
                        ->visible(fn ($record): bool => $record->status === MerchantStatus::Approved)
                        ->action(function ($record): void {
                            $record->update([
                                'status' => MerchantStatus::Suspended,
                            ]);
                            Notification::make()->title('Merchant suspended')->warning()->send();
                        }),
                ])
                    ->label('Actions')
                    ->icon(Heroicon::OutlinedEllipsisVertical)
                    ->tooltip('Merchant actions')
                    ->color('gray'),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
