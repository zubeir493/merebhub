<?php

use App\Models\DownloadableAsset;
use App\Models\Product;
use App\Models\Staff;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

test('imports WooCommerce products, variations, remote assets, and an admin user', function (): void {
    Storage::fake('public');
    Storage::fake('private');
    Http::preventStrayRequests();
    Http::fake([
        'https://example.test/test.webp' => Http::response('image-bytes', 200),
        'https://example.test/test.zip' => Http::response('archive-bytes', 200),
    ]);

    $this->artisan('merebhub:import-woocommerce', [
        'file' => base_path('tests/Fixtures/woocommerce-products.csv'),
        '--admin-email' => 'owner@example.test',
        '--admin-password' => 'correct-horse-battery',
    ])->assertExitCode(0);

    $product = Product::query()->where('name->en', 'Test Suite')->firstOrFail();

    expect((string) $product->status)->toBe('published')
        ->and($product->publication_state)->toBe('published')
        ->and($product->fulfillment_summary['source'])->toBe('woocommerce')
        ->and($product->attr('legacy_id'))->toBe('900')
        ->and($product->variants()->pluck('sku')->all())->toBe([
            'TEST-SUITE-BASIC',
            'TEST-SUITE-PRO',
        ]);

    $asset = DownloadableAsset::query()->whereBelongsTo($product)->firstOrFail();

    expect($asset->filename)->toBe('test.zip')
        ->and($asset->scan_status->value)->toBe('clean');
    Storage::disk('private')->assertExists($asset->path);
    Storage::disk('public')->assertExists('products/imported/test-suite.webp');

    $admin = Staff::query()->where('email', 'owner@example.test')->firstOrFail();

    expect($admin->admin)->toBeTrue()
        ->and(Hash::check('correct-horse-battery', $admin->password))->toBeTrue();
});

test('rerunning the WooCommerce import updates records instead of duplicating them', function (): void {
    $arguments = [
        'file' => base_path('tests/Fixtures/woocommerce-products.csv'),
        '--skip-remote-assets' => true,
    ];

    $this->artisan('merebhub:import-woocommerce', $arguments)->assertExitCode(0);
    $this->artisan('merebhub:import-woocommerce', $arguments)->assertExitCode(0);

    expect(Product::query()->where('name->en', 'Test Suite')->count())->toBe(1)
        ->and(Product::query()->where('name->en', 'Test Suite')->firstOrFail()->variants()->count())->toBe(2);
});
