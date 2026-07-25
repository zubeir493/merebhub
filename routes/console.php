<?php

use App\Services\WooCommerceService;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('merebhub:sync-woocommerce')
    ->hourly()
    ->withoutOverlapping()
    ->when(fn (): bool => app(WooCommerceService::class)->isConfigured());
