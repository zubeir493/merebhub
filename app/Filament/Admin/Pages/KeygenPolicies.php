<?php

namespace App\Filament\Admin\Pages;

use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Section;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Throwable;

class KeygenPolicies extends KeygenTablePage
{
    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedAdjustmentsHorizontal;

    protected static ?string $navigationLabel = 'Keygen policies';

    protected static string|\UnitEnum|null $navigationGroup = null;

    protected static ?string $navigationParentItem = 'Keygen products';

    protected static ?int $navigationSort = 10;

    protected string $view = 'filament.admin.pages.keygen-policies';

    public function table(Table $table): Table
    {
        return $table
            ->records(fn (): array => collect($this->safeRecords(fn (): array => $this->keygen()->policies()))
                ->mapWithKeys(fn (array $record): array => [(string) $record['id'] => $this->record($record)])
                ->all())
            ->columns([
                TextColumn::make('name')->label('Name')->searchable(),
                TextColumn::make('duration')->label('Duration')
                    ->formatStateUsing(fn ($state) => $state ? "{$state} days" : 'Perpetual'),
                TextColumn::make('maxMachines')->label('Device limit')
                    ->formatStateUsing(fn ($state) => $state ? "{$state} devices" : 'Unlimited')
                    ->badge()
                    ->color(fn ($state) => $state ? 'info' : 'gray'),
                TextColumn::make('scheme')->label('Scheme')->badge(),
                TextColumn::make('product_id')->label('Keygen product')->copyable()->limit(18)
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('id')->label('Keygen ID')->copyable()->limit(18)
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->headerActions([
                Action::make('create')
                    ->label('Create policy')
                    ->icon(Heroicon::Plus)
                    ->modalHeading('Create Keygen policy')
                    ->schema($this->policySchema())
                    ->action(function (array $data): void {
                        try {
                            $this->keygen()->createPolicy(
                                (string) $data['name'],
                                (string) $data['product_id'],
                                $this->policyAttributes($data),
                            );
                            $this->refreshRecords();
                            Notification::make()->title('Keygen policy created')->success()->send();
                        } catch (Throwable $exception) {
                            $this->notifyFailure('Policy creation failed', $exception);
                        }
                    }),
            ])
            ->recordActions([
                ActionGroup::make([
                    Action::make('edit')
                        ->label('Edit')
                        ->icon(Heroicon::PencilSquare)
                        ->fillForm(fn (array $record): array => [
                            'name' => $record['name'], 'product_id' => $record['product_id'],
                            'duration' => $record['duration'], 'maxMachines' => $record['maxMachines'],
                            'scheme' => $record['scheme'],
                            'floating' => $record['floating'], 'protected' => $record['protected'],
                            'requireProductScope' => $record['requireProductScope'],
                        ])
                        ->schema($this->policySchema(false))
                        ->action(function (array $data, array $record): void {
                            try {
                                $this->keygen()->updatePolicy((string) $record['id'], [
                                    'name' => (string) $data['name'],
                                    ...$this->policyAttributes($data),
                                ]);
                                $this->refreshRecords();
                                Notification::make()->title('Keygen policy updated')->success()->send();
                            } catch (Throwable $exception) {
                                $this->notifyFailure('Policy update failed', $exception);
                            }
                        }),
                    Action::make('delete')
                        ->label('Delete')->icon(Heroicon::Trash)->color('danger')->requiresConfirmation()
                        ->action(function (array $record): void {
                            try {
                                $this->keygen()->deletePolicy((string) $record['id']);
                                $this->refreshRecords();
                                Notification::make()->title('Keygen policy deleted')->success()->send();
                            } catch (Throwable $exception) {
                                $this->notifyFailure('Policy deletion failed', $exception);
                            }
                        }),
                ])
                    ->label('Actions')
                    ->icon(Heroicon::OutlinedEllipsisVertical)
                    ->tooltip('Keygen policy actions')
                    ->color('gray'),
            ]);
    }

    /** @return array<string, mixed> */
    private function record(array $record): array
    {
        $attributes = (array) ($record['attributes'] ?? []);

        return [
            'id' => (string) $record['id'],
            'name' => (string) ($attributes['name'] ?? ''),
            'product_id' => (string) data_get($record, 'relationships.product.data.id', ''),
            'duration' => $attributes['duration'] ?? null,
            'maxMachines' => $attributes['maxMachines'] ?? null,
            'scheme' => (string) ($attributes['scheme'] ?? ''),
            'floating' => (bool) ($attributes['floating'] ?? false),
            'protected' => (bool) ($attributes['protected'] ?? false),
            'requireProductScope' => (bool) ($attributes['requireProductScope'] ?? false),
        ];
    }

    /** @return array<int, Component> */
    private function policySchema(bool $includeProduct = true): array
    {
        return [
            TextInput::make('name')
                ->label('Policy name')
                ->required()
                ->maxLength(255)
                ->helperText('A descriptive name for this licensing policy, e.g. "Standard 3-Device Commercial".'),
            ...($includeProduct ? [Select::make('product_id')
                ->label('Keygen product')
                ->options(fn (): array => collect($this->safeRecords(fn (): array => $this->keygen()->products()))->mapWithKeys(fn (array $record): array => [
                    (string) $record['id'] => (string) data_get($record, 'attributes.name', $record['id']),
                ])->all())
                ->searchable()->required()] : []),
            TextInput::make('maxMachines')
                ->label('Device limit (max machines)')
                ->numeric()
                ->minValue(1)
                ->nullable()
                ->helperText('Maximum devices or machines allowed to activate this license concurrently. Leave empty for unlimited.'),
            TextInput::make('duration')
                ->label('Duration (days)')
                ->numeric()
                ->minValue(1)
                ->nullable()
                ->helperText('License validity length in days. Leave empty for a perpetual license.'),
            Section::make('Advanced licensing options')
                ->description('Cryptographic signing scheme, floating lease model, and product isolation.')
                ->collapsible()
                ->collapsed()
                ->schema([
                    Select::make('scheme')
                        ->label('Cryptographic scheme')
                        ->options([
                            'ED25519_SIGN' => 'Ed25519 signed (Recommended)',
                            'RSA_2048_PKCS1_SIGN' => 'RSA 2048 signed',
                            'RSA_4096_PKCS1_SIGN' => 'RSA 4096 signed',
                        ])
                        ->default('ED25519_SIGN')
                        ->required()
                        ->helperText('Algorithm used to sign license keys and offline activation files. Ed25519 is fast and compact.'),
                    Select::make('floating')
                        ->label('Activation mode')
                        ->options([
                            false => 'Node-locked (Tied to machine fingerprint)',
                            true => 'Floating (Temporary dynamic lease checkout)',
                        ])
                        ->default(false)
                        ->required()
                        ->helperText('Node-locked ties activation to a fixed device until deactivated. Floating allows devices to lease activations dynamically.'),
                    Select::make('protected')
                        ->label('Tamper protection')
                        ->options([
                            false => 'Standard (Normal policy)',
                            true => 'Protected (Admin credentials required to alter)',
                        ])
                        ->default(false)
                        ->required()
                        ->helperText('Protected policies require account-level administrative permissions to modify or delete.'),
                    Select::make('requireProductScope')
                        ->label('Require product scope')
                        ->options([
                            false => 'No (Allow global validation)',
                            true => 'Yes (Strict product validation)',
                        ])
                        ->default(false)
                        ->required()
                        ->helperText('When enabled, license keys can only validate against API requests specifying this exact Keygen product ID.'),
                ]),
        ];
    }

    /** @param array<string, mixed> $data */
    private function policyAttributes(array $data): array
    {
        return [
            'duration' => filled($data['duration'] ?? null) ? (int) $data['duration'] : null,
            'maxMachines' => filled($data['maxMachines'] ?? null) ? (int) $data['maxMachines'] : null,
            'scheme' => (string) ($data['scheme'] ?? 'ED25519_SIGN'),
            'floating' => filter_var($data['floating'] ?? false, FILTER_VALIDATE_BOOLEAN),
            'protected' => filter_var($data['protected'] ?? false, FILTER_VALIDATE_BOOLEAN),
            'requireProductScope' => filter_var($data['requireProductScope'] ?? false, FILTER_VALIDATE_BOOLEAN),
        ];
    }
}
