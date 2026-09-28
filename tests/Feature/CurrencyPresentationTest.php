<?php

use Lunar\Core\Models\Currency;
use Lunar\Core\Pricing\PriceFormatterInterface;

beforeEach(function (): void {
    $this->seed();
});

test('the storefront uses Ethiopian Birr and formats it as Br', function (): void {
    $currency = Currency::query()
        ->where('code', 'ETB')
        ->firstOrFail();

    $formattedPrice = app(PriceFormatterInterface::class, [
        'value' => 10000,
        'currency' => $currency,
    ])->formatted();

    expect(Currency::getDefault()->code)->toBe('ETB')
        ->and($formattedPrice)->toContain('Br')
        ->and($formattedPrice)->toContain('100.00')
        ->and($formattedPrice)->not->toContain('±');
});
