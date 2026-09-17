<?php

use Filament\Facades\Filament;

test('the unified admin sidebar is flat and has no navigation groups', function () {
    $groups = Filament::getPanel('lunar')->getNavigation();

    expect($groups)->toHaveCount(1)
        ->and($groups[0]->getLabel())->toBeNull();
});

test('Filament sidebars cannot be collapsed on desktop', function (): void {
    expect(Filament::getPanel('lunar')->isSidebarCollapsibleOnDesktop())->toBeFalse()
        ->and(Filament::getPanel('lunar')->isSidebarFullyCollapsibleOnDesktop())->toBeFalse()
        ->and(Filament::getPanel('merchant')->isSidebarCollapsibleOnDesktop())->toBeFalse()
        ->and(Filament::getPanel('merchant')->isSidebarFullyCollapsibleOnDesktop())->toBeFalse();
});

test('removed Lunar pages return not found when visited directly', function () {
    foreach ([
        '/lunar/channels',
        '/lunar/locations',
        '/lunar/taxes',
        '/lunar/products/1/shipping',
        '/lunar/product-variants/1/shipping',
    ] as $path) {
        $this->get($path)->assertNotFound();
    }
});
