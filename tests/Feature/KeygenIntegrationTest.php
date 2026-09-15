<?php

use App\Domain\Fulfillment\Data\LicenseProvisioningRequest;
use App\Domain\Fulfillment\Providers\KeygenLicenseProvider;
use App\Filament\Admin\Resources\LicenseMappings\LicenseMappingResource;
use App\Integrations\Keygen\KeygenClient;
use App\Models\LicenseMapping;
use App\Models\Product;
use App\Models\Staff;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Http;

beforeEach(function (): void {
    config()->set('services.keygen.url', 'https://keygen.localhost:8443');
    config()->set('services.keygen.account_id', 'account-123');
    config()->set('services.keygen.api_token', 'server-token');
    config()->set('services.keygen.verify', false);
});

test('the Keygen provider creates a license through the mapped policy', function (): void {
    Http::preventStrayRequests();
    Http::fake([
        'https://keygen.localhost:8443/v1/accounts/account-123/licenses' => Http::response([
            'data' => [
                'id' => 'license-123',
                'type' => 'licenses',
                'attributes' => [
                    'key' => 'KEYGEN-123',
                    'status' => 'active',
                    'metadata' => ['merebhub_idempotency_key' => 'unit-123'],
                ],
                'relationships' => [
                    'policy' => ['data' => ['id' => 'policy-123']],
                    'product' => ['data' => ['id' => 'product-123']],
                ],
            ],
        ], 201),
    ]);

    $result = (new KeygenLicenseProvider(new KeygenClient))->provision(new LicenseProvisioningRequest(
        idempotencyKey: 'unit-123',
        productIdentifier: 'merebhub-product',
        orderReference: 'MH-1001',
        customerEmail: 'buyer@example.test',
        licensePolicyId: 'policy-123',
    ));

    expect($result->externalId)->toBe('license-123')
        ->and($result->licenseKey)->toBe('KEYGEN-123')
        ->and($result->metadata['keygen_policy_id'])->toBe('policy-123');

    Http::assertSent(function ($request): bool {
        return $request->url() === 'https://keygen.localhost:8443/v1/accounts/account-123/licenses'
            && $request->hasHeader('Authorization', 'Bearer server-token')
            && $request['data']['relationships']['policy']['data']['id'] === 'policy-123'
            && $request['data']['attributes']['metadata']['merebhub_idempotency_key'] === 'unit-123';
    });
});

test('the Keygen client can exchange administrator credentials for a token', function (): void {
    Http::preventStrayRequests();
    Http::fake([
        'https://keygen.localhost:8443/v1/accounts/account-123/tokens' => Http::response([
            'data' => ['attributes' => ['token' => 'issued-token']],
        ], 201),
    ]);

    $token = (new KeygenClient)->issueToken('admin@example.test', 'test-password');

    expect($token)->toBe('issued-token');
    Http::assertSent(fn ($request): bool => $request->url() === 'https://keygen.localhost:8443/v1/accounts/account-123/tokens'
        && $request->hasHeader('Authorization', 'Basic '.base64_encode('admin@example.test:test-password')));
});

test('the Keygen provider recovers a license by its idempotency metadata', function (): void {
    Http::preventStrayRequests();
    Http::fake([
        'https://keygen.localhost:8443/v1/accounts/account-123/licenses*' => Http::response([
            'data' => [[
                'id' => 'license-recovered',
                'type' => 'licenses',
                'attributes' => ['key' => 'KEYGEN-RECOVERED', 'status' => 'active'],
                'relationships' => [],
            ]],
        ]),
    ]);

    $result = (new KeygenLicenseProvider(new KeygenClient))->findByIdempotencyKey('unit-456');

    expect($result?->externalId)->toBe('license-recovered')
        ->and($result?->licenseKey)->toBe('KEYGEN-RECOVERED');

    Http::assertSent(fn ($request): bool => str_contains($request->url(), 'unit-456'));
});

test('a variant mapping overrides the product-wide Keygen policy', function (): void {
    $this->seed();
    $product = Product::query()->with('variants')->firstOrFail();
    $variant = $product->variants->firstOrFail();

    $defaultMapping = LicenseMapping::query()->create([
        'product_id' => $product->getKey(),
        'keygen_policy_id' => 'policy-default',
    ]);
    $variantMapping = LicenseMapping::query()->create([
        'product_id' => $product->getKey(),
        'product_variant_id' => $variant->getKey(),
        'keygen_policy_id' => 'policy-variant',
    ]);

    expect(LicenseMapping::resolveFor($product->getKey(), $variant->getKey())->is($variantMapping))->toBeTrue()
        ->and(LicenseMapping::resolveFor($product->getKey(), 999999)->is($defaultMapping))->toBeTrue();
});

test('only admin staff can access the license mapping policy', function (): void {
    $admin = Staff::factory()->create(['admin' => true]);
    $staff = Staff::factory()->create(['admin' => false]);

    expect(Gate::forUser($admin)->allows('viewAny', LicenseMapping::class))->toBeTrue()
        ->and(Gate::forUser($staff)->allows('viewAny', LicenseMapping::class))->toBeFalse();
});

test('the admin panel registers the licenses module', function (): void {
    expect(Filament::getPanel('lunar')->getResources())->toContain(LicenseMappingResource::class);
});
