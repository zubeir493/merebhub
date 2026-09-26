<?php

namespace App\Filament\Admin\Resources\Merchants\Pages;

use App\Domain\Merchants\Enums\MerchantMembershipRole;
use App\Domain\Merchants\Enums\MerchantStatus;
use App\Filament\Admin\Resources\Merchants\MerchantResource;
use App\Models\Merchant;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Icons\Heroicon;

class EditMerchant extends EditRecord
{
    protected static string $resource = MerchantResource::class;

    protected function mutateFormDataBeforeFill(array $data): array
    {
        if ($owner = $this->getMerchantOwner()) {
            $data['account_email'] = $owner->email;
        }

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        if ($owner = $this->getMerchantOwner()) {
            $owner->forceFill([
                'name' => $data['display_name'],
                'email' => $data['account_email'] ?? $owner->email,
                'email_verified_at' => $owner->email_verified_at ?? now(),
            ]);

            if (filled($data['account_password'] ?? null)) {
                $owner->password = $data['account_password'];
            }

            $owner->save();
        }

        unset($data['account_email'], $data['account_password']);

        return $data;
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('resetPassword')
                ->label('Reset password')
                ->icon(Heroicon::Key)
                ->color('gray')
                ->form([
                    TextInput::make('password')
                        ->label('New password')
                        ->password()
                        ->required()
                        ->minLength(8)
                        ->autocomplete('new-password'),
                ])
                ->requiresConfirmation()
                ->visible(fn (Merchant $record): bool => $record->memberships()
                    ->where('merchant_role', MerchantMembershipRole::Owner->value)
                    ->exists())
                ->action(function (Merchant $record, array $data): void {
                    $owner = $record->memberships()
                        ->where('merchant_role', MerchantMembershipRole::Owner->value)
                        ->first()?->user;

                    if (! $owner) {
                        Notification::make()
                            ->title('Merchant owner not found')
                            ->danger()
                            ->send();

                        return;
                    }

                    $owner->forceFill([
                        'password' => $data['password'],
                        'email_verified_at' => $owner->email_verified_at ?? now(),
                    ])->save();

                    Notification::make()
                        ->title('Password reset')
                        ->success()
                        ->send();
                }),
            Action::make('toggleStatus')
                ->label(fn (Merchant $record): string => $record->status === MerchantStatus::Suspended ? 'Restore merchant' : 'Suspend merchant')
                ->icon(fn (Merchant $record): Heroicon => $record->status === MerchantStatus::Suspended ? Heroicon::ArrowPath : Heroicon::NoSymbol)
                ->color(fn (Merchant $record): string => $record->status === MerchantStatus::Suspended ? 'success' : 'danger')
                ->requiresConfirmation()
                ->visible(fn (Merchant $record): bool => in_array($record->status, [MerchantStatus::Approved, MerchantStatus::Suspended], true))
                ->action(function (Merchant $record): void {
                    $isSuspended = $record->status === MerchantStatus::Suspended;

                    $record->update([
                        'status' => $isSuspended ? MerchantStatus::Approved : MerchantStatus::Suspended,
                    ]);

                    Notification::make()
                        ->title($isSuspended ? 'Merchant restored' : 'Merchant suspended')
                        ->body($isSuspended ? 'The merchant can access the merchant panel again.' : 'The merchant can no longer access the merchant panel.')
                        ->success($isSuspended)
                        ->warning(! $isSuspended)
                        ->send();
                }),
            DeleteAction::make(),
        ];
    }

    private function getMerchantOwner(): ?User
    {
        /** @var Merchant $merchant */
        $merchant = $this->getRecord();

        return $merchant->memberships()
            ->where('merchant_role', MerchantMembershipRole::Owner->value)
            ->first()?->user;
    }
}
