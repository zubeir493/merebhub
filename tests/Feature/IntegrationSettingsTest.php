<?php

use App\Filament\Admin\Pages\IntegrationSettings;
use App\Integrations\Chapa\ChapaClient;
use App\Integrations\Keygen\KeygenClient;
use App\Models\IntegrationSetting;
use App\Models\Staff;
use App\Support\IntegrationSettingsStore;
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
            'chapa_secret_key' => 'chapa-admin-secret',
            'chapa_webhook_secret' => 'chapa-webhook-secret',
            'chapa_base_url' => 'https://api.chapa.co/v1',
            'keygen_url' => 'https://keygen.localhost:8443',
            'keygen_account_id' => 'keygen-account',
            'keygen_api_token' => 'keygen-admin-token',
            'keygen_admin_email' => 'admin@example.test',
            'keygen_admin_password' => 'keygen-admin-password',
            'keygen_verify' => false,
        ])
        ->call('save')
        ->assertHasNoFormErrors()
        ->assertNotified();

    $settings = app(IntegrationSettingsStore::class);

    expect($settings->get('chapa', 'secret_key'))->toBe('chapa-admin-secret')
        ->and($settings->get('keygen', 'api_token'))->toBe('keygen-admin-token')
        ->and($settings->get('keygen', 'verify'))->toBe('0');
});
