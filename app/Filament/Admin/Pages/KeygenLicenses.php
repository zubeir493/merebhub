<?php

namespace App\Filament\Admin\Pages;

use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Component;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Throwable;

class KeygenLicenses extends KeygenTablePage
{
    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedKey;

    protected static ?string $navigationLabel = 'Keygen licenses';

    protected static string|\UnitEnum|null $navigationGroup = null;

    protected static ?string $navigationParentItem = 'Keygen products';

    protected static ?int $navigationSort = 20;

    protected string $view = 'filament.admin.pages.keygen-licenses';

    public function table(Table $table): Table
    {
        return $table
            ->records(fn (): array => collect($this->safeRecords(fn (): array => $this->keygen()->licenses()))
                ->mapWithKeys(fn (array $record): array => [(string) $record['id'] => $this->record($record)])
                ->all())
            ->columns([
                TextColumn::make('name')->label('Name')->placeholder('—')->searchable(),
                TextColumn::make('key')->label('License key')->copyable()->searchable(),
                TextColumn::make('status')->badge(),
                TextColumn::make('policy_id')->label('Policy')->placeholder('—')->limit(18)
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('expiry')->label('Expires')->placeholder('Perpetual'),
            ])
            ->headerActions([
                Action::make('create')
                    ->label('Generate license')
                    ->icon(Heroicon::Plus)
                    ->modalHeading('Generate Keygen license')
                    ->schema($this->createSchema())
                    ->action(function (array $data): void {
                        try {
                            $this->keygen()->createLicense(
                                (string) $data['policy_id'],
                                filled($data['name'] ?? null) ? (string) $data['name'] : 'MerebHub manual license',
                            );
                            $this->refreshRecords();
                            Notification::make()->title('License generated')->success()->send();
                        } catch (Throwable $exception) {
                            $this->notifyFailure('License generation failed', $exception);
                        }
                    }),
            ])
            ->recordActions([
                ActionGroup::make([
                    Action::make('edit')
                        ->label('Edit')->icon(Heroicon::PencilSquare)
                        ->fillForm(fn (array $record): array => [
                            'name' => $record['name'], 'expiry' => $record['expiry'],
                        ])
                        ->schema([
                            TextInput::make('name')->label('License name')->maxLength(255)->nullable(),
                            TextInput::make('expiry')->nullable()
                                ->helperText('Use an ISO-8601 timestamp, or clear it for a perpetual license.'),
                        ])
                        ->action(function (array $data, array $record): void {
                            try {
                                $this->keygen()->updateLicense((string) $record['id'], [
                                    'name' => filled($data['name'] ?? null) ? (string) $data['name'] : null,
                                    'expiry' => filled($data['expiry'] ?? null) ? (string) $data['expiry'] : null,
                                ]);
                                $this->refreshRecords();
                                Notification::make()->title('License updated')->success()->send();
                            } catch (Throwable $exception) {
                                $this->notifyFailure('License update failed', $exception);
                            }
                        }),
                    Action::make('suspend')->label('Suspend')->icon(Heroicon::Pause)->color('warning')
                        ->requiresConfirmation()
                        ->visible(fn (array $record): bool => $record['status'] === 'active')
                        ->action(function (array $record): void {
                            $this->runLicenseAction((string) $record['id'], 'suspend', 'License suspended');
                        }),
                    Action::make('reinstate')->label('Reinstate')->icon(Heroicon::ArrowPath)
                        ->visible(fn (array $record): bool => $record['status'] === 'suspended')
                        ->action(function (array $record): void {
                            $this->runLicenseAction((string) $record['id'], 'reinstate', 'License reinstated');
                        }),
                    Action::make('revoke')->label('Revoke')->icon(Heroicon::NoSymbol)->color('danger')
                        ->requiresConfirmation()
                        ->visible(fn (array $record): bool => $record['status'] !== 'revoked')
                        ->action(function (array $record): void {
                            $this->runLicenseAction((string) $record['id'], 'revoke', 'License revoked');
                        }),
                    Action::make('delete')->label('Delete')->icon(Heroicon::Trash)->color('danger')->requiresConfirmation()
                        ->action(function (array $record): void {
                            try {
                                $this->keygen()->deleteLicense((string) $record['id']);
                                $this->refreshRecords();
                                Notification::make()->title('License deleted')->success()->send();
                            } catch (Throwable $exception) {
                                $this->notifyFailure('License deletion failed', $exception);
                            }
                        }),
                ])
                    ->label('Actions')
                    ->icon(Heroicon::OutlinedEllipsisVertical)
                    ->tooltip('License actions')
                    ->color('gray'),
            ]);
    }

    /** @return array<string, mixed> */
    private function record(array $record): array
    {
        $attributes = (array) ($record['attributes'] ?? []);

        return [
            'id' => (string) $record['id'],
            'name' => $attributes['name'] ?? null,
            'key' => (string) ($attributes['key'] ?? ''),
            'status' => (string) ($attributes['status'] ?? ''),
            'expiry' => $attributes['expiry'] ?? null,
            'policy_id' => (string) data_get($record, 'relationships.policy.data.id', ''),
            'product_id' => (string) data_get($record, 'relationships.product.data.id', ''),
        ];
    }

    /** @return array<int, Component> */
    private function createSchema(): array
    {
        return [
            Select::make('policy_id')
                ->label('Keygen policy')
                ->options(fn (): array => collect($this->safeRecords(fn (): array => $this->keygen()->policies()))->mapWithKeys(fn (array $record): array => [
                    (string) $record['id'] => sprintf(
                        '%s (%s)',
                        data_get($record, 'attributes.name', $record['id']),
                        data_get($record, 'relationships.product.data.id', 'no product'),
                    ),
                ])->all())
                ->searchable()->required(),
            TextInput::make('name')->label('License name')->maxLength(255)->nullable(),
        ];
    }

    private function runLicenseAction(string $licenseId, string $action, string $successTitle): void
    {
        try {
            $this->keygen()->licenseAction($licenseId, $action);
            $this->refreshRecords();
            Notification::make()->title($successTitle)->success()->send();
        } catch (Throwable $exception) {
            $this->notifyFailure(ucfirst($action).' failed', $exception);
        }
    }
}
