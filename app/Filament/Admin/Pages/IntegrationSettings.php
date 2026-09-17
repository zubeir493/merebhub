<?php

namespace App\Filament\Admin\Pages;

use App\Support\IntegrationSettingsStore;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Throwable;

class IntegrationSettings extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedCog6Tooth;

    protected static ?string $navigationLabel = 'Integration settings';

    protected static string|\UnitEnum|null $navigationGroup = 'System';

    protected static ?int $navigationSort = 10;

    protected static ?string $title = 'Integration settings';

    protected string $view = 'filament.admin.pages.integration-settings';

    /** @var array<string, mixed> */
    public array $data = [];

    public function mount(): void
    {
        $settings = app(IntegrationSettingsStore::class);

        $this->form->fill([
            'chapa_base_url' => $settings->get('chapa', 'base_url', config('services.chapa.base_url')),
            'keygen_url' => $settings->get('keygen', 'url', config('services.keygen.url')),
            'keygen_host_header' => $settings->get('keygen', 'host_header', config('services.keygen.host_header')),
            'keygen_account_id' => $settings->get('keygen', 'account_id', config('services.keygen.account_id')),
            'keygen_verify' => filter_var($settings->get('keygen', 'verify', config('services.keygen.verify', true)), FILTER_VALIDATE_BOOL),
            'keygen_admin_email' => $settings->get('keygen', 'admin_email'),
        ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Chapa checkout')
                    ->description('These values are encrypted in the application database and are never sent to the storefront.')
                    ->schema([
                        TextInput::make('chapa_secret_key')
                            ->label('Secret key')
                            ->password()
                            ->revealable()
                            ->helperText('Leave blank to keep the current key.')
                            ->dehydrated(),
                        TextInput::make('chapa_webhook_secret')
                            ->label('Webhook secret')
                            ->password()
                            ->revealable()
                            ->helperText('Leave blank to keep the current secret.')
                            ->dehydrated(),
                        TextInput::make('chapa_base_url')
                            ->label('API base URL')
                            ->url()
                            ->required(),
                    ])
                    ->columns(2),
                Section::make('Keygen licensing')
                    ->description('The API token is used for server-side license management. Administrator credentials are optional and are stored only for future token exchange tooling.')
                    ->schema([
                        TextInput::make('keygen_url')
                            ->label('Server URL')
                            ->url()
                            ->required(),
                        TextInput::make('keygen_host_header')
                            ->label('Host header')
                            ->helperText('Only needed when the local Keygen proxy requires a specific host.')
                            ->nullable(),
                        TextInput::make('keygen_account_id')
                            ->label('Account ID')
                            ->required(),
                        TextInput::make('keygen_api_token')
                            ->label('API token')
                            ->password()
                            ->revealable()
                            ->helperText('Leave blank to keep the current token.')
                            ->dehydrated(),
                        TextInput::make('keygen_admin_email')
                            ->label('Administrator email')
                            ->email()
                            ->nullable(),
                        TextInput::make('keygen_admin_password')
                            ->label('Administrator password')
                            ->password()
                            ->revealable()
                            ->helperText('Optional; leave blank to keep the current password.')
                            ->dehydrated(),
                        Toggle::make('keygen_verify')
                            ->label('Verify TLS certificates')
                            ->default(true),
                    ])
                    ->columns(2),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        try {
            $data = $this->form->getState();
            $settings = app(IntegrationSettingsStore::class);

            foreach ([
                'chapa_secret_key' => ['chapa', 'secret_key'],
                'chapa_webhook_secret' => ['chapa', 'webhook_secret'],
                'chapa_base_url' => ['chapa', 'base_url'],
                'keygen_url' => ['keygen', 'url'],
                'keygen_host_header' => ['keygen', 'host_header'],
                'keygen_account_id' => ['keygen', 'account_id'],
                'keygen_api_token' => ['keygen', 'api_token'],
                'keygen_admin_email' => ['keygen', 'admin_email'],
                'keygen_admin_password' => ['keygen', 'admin_password'],
                'keygen_verify' => ['keygen', 'verify'],
            ] as $field => [$provider, $key]) {
                if (in_array($field, ['chapa_secret_key', 'chapa_webhook_secret', 'keygen_api_token', 'keygen_admin_password'], true)
                    && blank($data[$field] ?? null)) {
                    continue;
                }

                $value = $field === 'keygen_verify'
                    ? filter_var($data[$field] ?? false, FILTER_VALIDATE_BOOL)
                    : ($data[$field] ?? null);

                $settings->put($provider, $key, $value);
            }

            Notification::make()
                ->title('Integration settings saved')
                ->success()
                ->send();
        } catch (Throwable $exception) {
            report($exception);

            Notification::make()
                ->title('Integration settings could not be saved')
                ->body('Please check the form and try again.')
                ->danger()
                ->send();
        }
    }
}
