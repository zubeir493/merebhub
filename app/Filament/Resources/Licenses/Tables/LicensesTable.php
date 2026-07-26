<?php

namespace App\Filament\Resources\Licenses\Tables;

use App\Enums\LicenseStatus;
use App\Models\License;
use App\Services\KeygenService;
use Filament\Actions\Action;
use Filament\Actions\ViewAction;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Throwable;

class LicensesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('product.name')->searchable()->weight('bold'),
                TextColumn::make('buyer_email')->searchable(),
                TextColumn::make('license_key')->copyable()->copyMessage('License key copied')->limit(22)->tooltip(fn (License $record) => $record->license_key),
                TextColumn::make('status')->badge()->sortable(),
                TextColumn::make('activation_limit')->label('Activations')->numeric(),
                TextColumn::make('expires_at')->dateTime()->placeholder('Never')->sortable(),
                TextColumn::make('order.public_id')->label('Order')->searchable()->limit(14),
            ])
            ->filters([
                SelectFilter::make('status')->options(LicenseStatus::class),
            ])
            ->recordActions([
                Action::make('revoke')
                    ->color('danger')
                    ->icon(Heroicon::NoSymbol)
                    ->visible(fn (License $record): bool => $record->status === LicenseStatus::Active)
                    ->requiresConfirmation()
                    ->action(function (License $record, KeygenService $keygen): void {
                        try {
                            $keygen->revoke($record->keygen_license_id);
                            $record->update(['status' => LicenseStatus::Revoked, 'revoked_at' => now()]);
                            Notification::make()->title('License revoked')->success()->send();
                        } catch (Throwable $exception) {
                            report($exception);
                            Notification::make()->title('Keygen revoke failed')->body($exception->getMessage())->danger()->persistent()->send();
                        }
                    }),
                ViewAction::make(),
            ]);
    }
}
