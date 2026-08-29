<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schema;

test('legacy catalog profile succeeds when no source tables are installed', function () {
    $this->artisan('legacy:catalog-profile')
        ->expectsOutput('No complete legacy catalog source was found; nothing was imported.')
        ->assertExitCode(0);
});

test('legacy catalog profile reports source table counts as json', function () {
    foreach (['authors', 'products', 'platforms', 'platform_product'] as $table) {
        Schema::create($table, function (Blueprint $blueprint): void {
            $blueprint->id();
        });
    }

    expect(Artisan::call('legacy:catalog-profile', ['--json' => true]))->toBe(0);

    $output = Artisan::output();

    expect($output)
        ->toContain('"source_available": true')
        ->toContain('"authors": 0')
        ->toContain('"products": 0');
});

test('legacy catalog profile fails when only part of the source is installed', function () {
    Schema::create('authors', function (Blueprint $blueprint): void {
        $blueprint->id();
    });

    expect(Artisan::call('legacy:catalog-profile'))->toBe(1);
    expect(Artisan::output())->toContain('The legacy catalog source is incomplete; no import was attempted.');
});
