<?php

namespace App\Filament\Admin\Pages;

use App\Support\IntegrationSettingsStore;
use App\Support\MailtrapConfigurator;
use App\Support\S3StorageConfigurator;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Throwable;

class IntegrationSettings extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedCog6Tooth;

    protected static ?string $navigationLabel = 'Integration settings';

    protected static string|\UnitEnum|null $navigationGroup = null;

    protected static ?int $navigationSort = 140;

    protected static ?string $title = 'Integration settings';

    protected string $view = 'filament.admin.pages.integration-settings';

    /** @var array<string, mixed> */
    public array $data = [];

    public function mount(): void
    {
        $settings = app(IntegrationSettingsStore::class);

        $this->form->fill([
            'chapa_public_key' => $settings->get('chapa', 'public_key', config('services.chapa.public_key')),
            'chapa_base_url' => $settings->get('chapa', 'base_url', config('services.chapa.base_url')),
            'keygen_url' => $settings->get('keygen', 'url', config('services.keygen.url')),
            'keygen_middleware_url' => $settings->get('keygen', 'middleware_url', config('services.keygen.middleware_url')),
            'keygen_host_header' => $settings->get('keygen', 'host_header', config('services.keygen.host_header')),
            'keygen_account_id' => $settings->get('keygen', 'account_id', config('services.keygen.account_id')),
            'keygen_verify' => filter_var($settings->get('keygen', 'verify', config('services.keygen.verify', true)), FILTER_VALIDATE_BOOL),
            'keygen_admin_email' => $settings->get('keygen', 'admin_email'),
            'mailtrap_enabled' => filter_var($settings->get('mailtrap', 'enabled', config('mail.default') === 'mailtrap'), FILTER_VALIDATE_BOOL),
            'mailtrap_host' => $settings->get('mailtrap', 'host', config('mail.mailers.mailtrap.host')),
            'mailtrap_port' => $settings->get('mailtrap', 'port', config('mail.mailers.mailtrap.port', 2525)),
            'mailtrap_username' => $settings->get('mailtrap', 'username', config('mail.mailers.mailtrap.username')),
            'mailtrap_scheme' => $settings->get('mailtrap', 'scheme', config('mail.mailers.mailtrap.scheme') ?: 'smtp'),
            'mailtrap_from_address' => $settings->get('mailtrap', 'from_address', config('mail.from.address')),
            'mailtrap_from_name' => $settings->get('mailtrap', 'from_name', config('mail.from.name')),
            's3_enabled' => filter_var($settings->get('s3', 'enabled', config('marketplace.object_storage.enabled', false)), FILTER_VALIDATE_BOOL),
            's3_access_key_id' => $settings->get('s3', 'access_key_id', config('filesystems.disks.s3.key')),
            's3_public_bucket' => $settings->get('s3', 'public_bucket', config('filesystems.disks.s3.bucket')),
            's3_private_bucket' => $settings->get('s3', 'private_bucket', config('filesystems.disks.s3_private.bucket')),
            's3_region' => $settings->get('s3', 'region', config('filesystems.disks.s3.region', 'eu-central-003')),
            's3_endpoint' => $settings->get('s3', 'endpoint', config('filesystems.disks.s3.endpoint', 'https://s3.eu-central-003.backblazeb2.com')),
            's3_public_url' => $settings->get('s3', 'public_url', config('filesystems.disks.s3.url')),
            's3_use_path_style_endpoint' => filter_var($settings->get('s3', 'use_path_style_endpoint', config('filesystems.disks.s3.use_path_style_endpoint', true)), FILTER_VALIDATE_BOOL),
            's3_verify_ssl' => filter_var($settings->get('s3', 'verify_ssl', data_get(config('filesystems.disks.s3'), 'http.verify', true)), FILTER_VALIDATE_BOOL),
        ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Tabs::make('Integration categories')
                    ->persistTabInQueryString('settings-tab')
                    ->tabs([
                        Tab::make('Payments')
                            ->icon(Heroicon::OutlinedCreditCard)
                            ->schema([
                                Section::make('Chapa checkout')
                                    ->description('These values are encrypted in the application database and are never sent to the storefront.')
                                    ->schema([
                                        TextInput::make('chapa_public_key')
                                            ->label('Public key')
                                            ->helperText('Used by Chapa client-side integrations and kept here with the rest of the account credentials.')
                                            ->dehydrated(),
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
                            ]),
                        Tab::make('Licensing')
                            ->icon(Heroicon::OutlinedKey)
                            ->schema([
                                Section::make('Keygen licensing')
                                    ->description('The API token is used for server-side license management. The middleware connection powers offline activation file generation.')
                                    ->schema([
                                        TextInput::make('keygen_url')
                                            ->label('Server URL')
                                            ->url()
                                            ->required(),
                                        TextInput::make('keygen_middleware_url')
                                            ->label('Offline activation middleware URL')
                                            ->url()
                                            ->helperText('The bridge endpoint that accepts .lreq files and returns generated .lic files.')
                                            ->nullable(),
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
                                        TextInput::make('keygen_admin_token')
                                            ->label('Offline middleware admin token')
                                            ->password()
                                            ->revealable()
                                            ->helperText('Optional for a local bridge; leave blank to keep the current token.')
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
                            ]),
                        Tab::make('Email')
                            ->icon(Heroicon::OutlinedEnvelope)
                            ->schema([
                                Section::make('Mailtrap email delivery')
                                    ->description('Routes every application email through Mailtrap, including verification, support, license, and transaction messages.')
                                    ->schema([
                                        Toggle::make('mailtrap_enabled')
                                            ->label('Use Mailtrap for all email')
                                            ->default(true)
                                            ->columnSpanFull(),
                                        TextInput::make('mailtrap_host')
                                            ->label('SMTP host')
                                            ->placeholder('live.smtp.mailtrap.io')
                                            ->required(fn (Get $get): bool => (bool) $get('mailtrap_enabled')),
                                        TextInput::make('mailtrap_port')
                                            ->label('SMTP port')
                                            ->numeric()
                                            ->minValue(1)
                                            ->maxValue(65535)
                                            ->default(2525)
                                            ->required(fn (Get $get): bool => (bool) $get('mailtrap_enabled')),
                                        TextInput::make('mailtrap_username')
                                            ->label('SMTP username')
                                            ->required(fn (Get $get): bool => (bool) $get('mailtrap_enabled')),
                                        TextInput::make('mailtrap_password')
                                            ->label('SMTP password or API token')
                                            ->password()
                                            ->revealable()
                                            ->helperText('Leave blank to keep the current credential.')
                                            ->required(fn (Get $get): bool => (bool) $get('mailtrap_enabled') && blank(
                                                app(IntegrationSettingsStore::class)->get('mailtrap', 'password', config('mail.mailers.mailtrap.password')),
                                            ))
                                            ->dehydrated(),
                                        Select::make('mailtrap_scheme')
                                            ->label('Connection security')
                                            ->options([
                                                'smtp' => 'SMTP with automatic TLS',
                                                'smtps' => 'SMTPS (TLS from connection)',
                                            ])
                                            ->required(fn (Get $get): bool => (bool) $get('mailtrap_enabled'))
                                            ->default('smtp'),
                                        TextInput::make('mailtrap_from_address')
                                            ->label('From address')
                                            ->email()
                                            ->required(fn (Get $get): bool => (bool) $get('mailtrap_enabled')),
                                        TextInput::make('mailtrap_from_name')
                                            ->label('From name')
                                            ->required(fn (Get $get): bool => (bool) $get('mailtrap_enabled')),
                                    ])
                                    ->columns(2),
                            ]),
                        Tab::make('Storage')
                            ->icon(Heroicon::OutlinedCircleStack)
                            ->schema([
                                Section::make('Backblaze B2 object storage')
                                    ->description('Store product images in a public bucket and paid downloads in a separate private bucket through Backblaze’s S3-compatible API.')
                                    ->schema([
                                        Toggle::make('s3_enabled')
                                            ->label('Use Backblaze B2 for new uploads')
                                            ->helperText('Existing files stay on their current disks; new product media, downloads, and support attachments use B2.')
                                            ->default(false)
                                            ->columnSpanFull(),
                                        TextInput::make('s3_access_key_id')
                                            ->label('Application key ID')
                                            ->required(fn (Get $get): bool => (bool) $get('s3_enabled')),
                                        TextInput::make('s3_secret_access_key')
                                            ->label('Application key')
                                            ->password()
                                            ->revealable()
                                            ->helperText('Leave blank to keep the current key.')
                                            ->required(fn (Get $get): bool => (bool) $get('s3_enabled') && blank(
                                                app(IntegrationSettingsStore::class)->get('s3', 'secret_access_key', config('filesystems.disks.s3.secret')),
                                            )),
                                        TextInput::make('s3_public_bucket')
                                            ->label('Public assets bucket')
                                            ->helperText('This bucket serves product images and other public media.')
                                            ->required(fn (Get $get): bool => (bool) $get('s3_enabled')),
                                        TextInput::make('s3_private_bucket')
                                            ->label('Private downloads bucket')
                                            ->helperText('Backblaze applies access control at bucket level, so this must be separate from the public assets bucket.')
                                            ->different('s3_public_bucket')
                                            ->required(fn (Get $get): bool => (bool) $get('s3_enabled')),
                                        TextInput::make('s3_region')
                                            ->label('Region')
                                            ->default('eu-central-003')
                                            ->required(fn (Get $get): bool => (bool) $get('s3_enabled')),
                                        TextInput::make('s3_endpoint')
                                            ->label('S3 endpoint')
                                            ->url()
                                            ->default('https://s3.eu-central-003.backblazeb2.com')
                                            ->required(fn (Get $get): bool => (bool) $get('s3_enabled')),
                                        TextInput::make('s3_public_url')
                                            ->label('Public asset URL')
                                            ->url()
                                            ->placeholder('Optional CDN or custom domain')
                                            ->helperText('Leave blank to use the Backblaze endpoint URL.')
                                            ->nullable()
                                            ->columnSpanFull(),
                                        Toggle::make('s3_use_path_style_endpoint')
                                            ->label('Use path-style endpoints')
                                            ->default(true),
                                        Toggle::make('s3_verify_ssl')
                                            ->label('Verify SSL certificates')
                                            ->helperText('Keep enabled outside local troubleshooting.')
                                            ->default(true),
                                    ])
                                    ->columns(2),
                            ]),
                    ]),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        try {
            $data = $this->form->getState();
            $settings = app(IntegrationSettingsStore::class);

            foreach ([
                'chapa_public_key' => ['chapa', 'public_key'],
                'chapa_secret_key' => ['chapa', 'secret_key'],
                'chapa_webhook_secret' => ['chapa', 'webhook_secret'],
                'chapa_base_url' => ['chapa', 'base_url'],
                'keygen_url' => ['keygen', 'url'],
                'keygen_middleware_url' => ['keygen', 'middleware_url'],
                'keygen_host_header' => ['keygen', 'host_header'],
                'keygen_account_id' => ['keygen', 'account_id'],
                'keygen_api_token' => ['keygen', 'api_token'],
                'keygen_admin_token' => ['keygen', 'admin_token'],
                'keygen_admin_email' => ['keygen', 'admin_email'],
                'keygen_admin_password' => ['keygen', 'admin_password'],
                'keygen_verify' => ['keygen', 'verify'],
                'mailtrap_enabled' => ['mailtrap', 'enabled'],
                'mailtrap_host' => ['mailtrap', 'host'],
                'mailtrap_port' => ['mailtrap', 'port'],
                'mailtrap_username' => ['mailtrap', 'username'],
                'mailtrap_password' => ['mailtrap', 'password'],
                'mailtrap_scheme' => ['mailtrap', 'scheme'],
                'mailtrap_from_address' => ['mailtrap', 'from_address'],
                'mailtrap_from_name' => ['mailtrap', 'from_name'],
                's3_enabled' => ['s3', 'enabled'],
                's3_access_key_id' => ['s3', 'access_key_id'],
                's3_secret_access_key' => ['s3', 'secret_access_key'],
                's3_public_bucket' => ['s3', 'public_bucket'],
                's3_private_bucket' => ['s3', 'private_bucket'],
                's3_region' => ['s3', 'region'],
                's3_endpoint' => ['s3', 'endpoint'],
                's3_public_url' => ['s3', 'public_url'],
                's3_use_path_style_endpoint' => ['s3', 'use_path_style_endpoint'],
                's3_verify_ssl' => ['s3', 'verify_ssl'],
            ] as $field => [$provider, $key]) {
                if (in_array($field, ['chapa_public_key', 'chapa_secret_key', 'chapa_webhook_secret', 'keygen_api_token', 'keygen_admin_password', 'keygen_admin_token', 'mailtrap_password', 's3_secret_access_key'], true)
                    && blank($data[$field] ?? null)) {
                    continue;
                }

                $value = in_array($field, ['keygen_verify', 'mailtrap_enabled', 's3_enabled', 's3_use_path_style_endpoint', 's3_verify_ssl'], true)
                    ? filter_var($data[$field] ?? false, FILTER_VALIDATE_BOOL)
                    : ($data[$field] ?? null);

                $settings->put($provider, $key, $value);
            }

            app(MailtrapConfigurator::class)->apply();
            app(S3StorageConfigurator::class)->apply();

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
