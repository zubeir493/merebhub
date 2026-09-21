<?php

use App\Filament\Admin\Pages\IntegrationSettings;
use App\Integrations\Chapa\ChapaClient;
use App\Integrations\Chapa\ChapaException;
use App\Integrations\Keygen\KeygenClient;
use App\Models\IntegrationSetting;
use App\Models\Staff;
use App\Support\IntegrationSettingsStore;
use App\Support\S3StorageConfigurator;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;

beforeEach(function (): void {
    $this->seed();
});

test('integration settings are encrypted and override environment fallbacks', function (): void {
    $store = app(IntegrationSettingsStore::class);
    $store->put('chapa', 'secret_key', 'chapa-secret-from-admin');
    $store->put('keygen', 'api_token', 'keygen-token-from-admin');

    config()->set('services.chapa.secret_key', 'env-chapa-secret');
    config()->set('services.keygen.api_token', 'env-keygen-token');
    config()->set('services.keygen.account_id', 'account-123');
    config()->set('services.keygen.verify', false);

    expect(IntegrationSetting::query()->firstOrFail()->getRawOriginal('value'))
        ->not->toBe('chapa-secret-from-admin')
        ->and($store->get('chapa', 'secret_key'))->toBe('chapa-secret-from-admin');

    Http::preventStrayRequests();
    Http::fake([
        'https://api.chapa.co/v1/transaction/initialize' => Http::response(['status' => 'success', 'data' => []]),
        'https://keygen.localhost:8443/v1/accounts/account-123/me' => Http::response(['data' => []]),
    ]);

    try {
        (new ChapaClient)->initialize([]);
    } catch (Throwable) {
        // The response shape is irrelevant; this request proves the stored token was selected.
    }
    (new KeygenClient)->account();

    Http::assertSent(fn ($request): bool => $request->hasHeader('Authorization', 'Bearer chapa-secret-from-admin'));
    Http::assertSent(fn ($request): bool => $request->hasHeader('Authorization', 'Bearer keygen-token-from-admin'));
});

test('an administrator can save Chapa and Keygen settings from the admin panel', function (): void {
    $staff = Staff::factory()->create(['admin' => true]);

    Filament::setCurrentPanel(Filament::getPanel('lunar'));
    Filament::bootCurrentPanel();
    $this->actingAs($staff, 'staff');

    Livewire::test(IntegrationSettings::class)
        ->fillForm([
            'chapa_public_key' => 'chapa-admin-public',
            'chapa_secret_key' => 'chapa-admin-secret',
            'chapa_webhook_secret' => 'chapa-webhook-secret',
            'chapa_base_url' => 'https://api.chapa.co/v1',
            'keygen_url' => 'https://keygen.localhost:8443',
            'keygen_account_id' => 'keygen-account',
            'keygen_api_token' => 'keygen-admin-token',
            'keygen_admin_email' => 'admin@example.test',
            'keygen_admin_password' => 'keygen-admin-password',
            'keygen_verify' => false,
            'mailtrap_enabled' => true,
            'mailtrap_host' => 'live.smtp.mailtrap.io',
            'mailtrap_port' => 2525,
            'mailtrap_username' => 'api',
            'mailtrap_password' => 'mailtrap-api-token',
            'mailtrap_scheme' => 'smtp',
            'mailtrap_from_address' => 'orders@merebhub.test',
            'mailtrap_from_name' => 'MerebHub Orders',
            's3_enabled' => true,
            's3_access_key_id' => 'backblaze-key-id',
            's3_secret_access_key' => 'backblaze-application-key',
            's3_public_bucket' => 'merebhub-assets',
            's3_private_bucket' => 'merebhub-downloads',
            's3_region' => 'eu-central-003',
            's3_endpoint' => 'https://s3.eu-central-003.backblazeb2.com',
            's3_public_url' => 'https://assets.merebhub.test',
            's3_use_path_style_endpoint' => true,
            's3_verify_ssl' => true,
        ])
        ->call('save')
        ->assertHasNoFormErrors()
        ->assertNotified();

    $settings = app(IntegrationSettingsStore::class);

    expect($settings->get('chapa', 'public_key'))->toBe('chapa-admin-public')
        ->and($settings->get('chapa', 'secret_key'))->toBe('chapa-admin-secret')
        ->and($settings->get('keygen', 'api_token'))->toBe('keygen-admin-token')
        ->and($settings->get('keygen', 'verify'))->toBe('0')
        ->and($settings->get('mailtrap', 'password'))->toBe('mailtrap-api-token')
        ->and(config('mail.default'))->toBe('mailtrap')
        ->and(config('mail.mailers.mailtrap.host'))->toBe('live.smtp.mailtrap.io')
        ->and(config('mail.from.address'))->toBe('orders@merebhub.test')
        ->and($settings->get('s3', 'secret_access_key'))->toBe('backblaze-application-key')
        ->and(config('filesystems.disks.s3.bucket'))->toBe('merebhub-assets')
        ->and(config('filesystems.disks.s3_private.bucket'))->toBe('merebhub-downloads')
        ->and(config('filesystems.disks.s3.region'))->toBe('eu-central-003')
        ->and(config('filesystems.disks.s3.endpoint'))->toBe('https://s3.eu-central-003.backblazeb2.com')
        ->and(data_get(config('filesystems.disks.s3'), 'http.verify'))->toBeTrue()
        ->and(config('marketplace.public_media_disk'))->toBe('s3')
        ->and(config('marketplace.private_files_disk'))->toBe('s3_private')
        ->and(config('support.attachments_disk'))->toBe('s3_private');

    expect(IntegrationSetting::query()
        ->where('provider', 's3')
        ->where('key', 'secret_access_key')
        ->firstOrFail()
        ->getRawOriginal('value'))
        ->not->toBe('backblaze-application-key');

    $settings->put('s3', 'enabled', false);
    app(S3StorageConfigurator::class)->apply();

    expect(config('marketplace.public_media_disk'))->toBe('public')
        ->and(config('marketplace.private_files_disk'))->toBe('private')
        ->and(config('support.attachments_disk'))->toBe('private');
});

test('Chapa validation responses remain actionable and are not retried', function (): void {
    config()->set('services.chapa.secret_key', 'test-secret');

    Http::fake([
        'https://api.chapa.co/v1/transaction/initialize' => Http::response([
            'status' => 'failed',
            'message' => ['Invalid API key', 'Check the configured project credentials'],
        ], 401),
    ]);

    expect(fn (): array => (new ChapaClient)->initialize(['amount' => '10']))
        ->toThrow(ChapaException::class, 'Invalid API key; Check the configured project credentials');

    Http::assertSentCount(1);
});
