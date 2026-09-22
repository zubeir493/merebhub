<?php

use App\Models\Staff;
use Filament\Facades\Filament;
use Filament\Support\Facades\FilamentIcon;
use Lunar\Admin\Filament\Resources\AttributeGroupResource;
use Lunar\Admin\Filament\Resources\ProductVariantResource;
use Lunar\Admin\Support\Facades\LunarPanel;
use Lunar\Filament\Models\Staff as FilamentStaff;

beforeEach(function (): void {
    $staff = Staff::factory()->create(['admin' => true]);

    Filament::setCurrentPanel(Filament::getPanel('lunar'));
    Filament::bootCurrentPanel();
    $this->actingAs($staff, 'staff');
});

test('the admin sidebar uses a flat commerce order with native parent items', function (): void {
    $groups = Filament::getPanel('lunar')->getNavigation();
    $group = $groups[0];

    expect($groups)->toHaveCount(1)
        ->and($group->getLabel())->toBeNull();

    $items = collect($group->getItems());

    expect($items->map(fn ($item): string => $item->getLabel())->all())
        ->toBe([
            'Dashboard',
            'Orders',
            'Products',
            'Customers',
            'Merchants',
            'Support inbox',
            'Discounts',
            'Fulfillment recovery',
            'Licenses',
            'Keygen products',
            'Staff',
            'Integration settings',
        ]);

    $keygenProducts = $items->first(fn ($item): bool => $item->getLabel() === 'Keygen products');
    $products = $items->first(fn ($item): bool => $item->getLabel() === 'Products');

    expect(collect($products->getChildItems())->map(fn ($item): string => $item->getLabel())->all())
        ->toBe(['Categories', 'Collection Groups', 'Brands', 'Tags'])
        ->and(collect($keygenProducts->getChildItems())->map(fn ($item): string => $item->getLabel())->all())
        ->toBe(['Keygen policies', 'Keygen licenses']);
});

test('integration settings is the final item in the admin sidebar', function (): void {
    $items = Filament::getPanel('lunar')->getNavigation()[0]->getItems();

    expect(collect($items)->last()->getLabel())->toBe('Integration settings');
});

test('the panel uses a text brand without an image logo', function (): void {
    $panel = Filament::getPanel('lunar');

    expect((string) $panel->getBrandName())->toBe('MerebHub')
        ->and($panel->getBrandLogo())->toBeNull();
});

test('application staff can open custom admin resources without policy type errors', function (): void {
    $this->get('/admin/fulfillment-units')->assertSuccessful();
});

test('the package staff model can open custom admin resources without policy type errors', function (): void {
    $staff = FilamentStaff::forceCreate([
        'first_name' => 'Package',
        'last_name' => 'Staff',
        'email' => 'package-staff@example.test',
        'password' => 'password',
        'admin' => true,
    ]);

    $this->actingAs($staff, 'staff')
        ->get('/admin/fulfillment-units')
        ->assertSuccessful();
});

test('Filament sidebars cannot be collapsed on desktop', function (): void {
    expect(Filament::getPanel('lunar')->isSidebarCollapsibleOnDesktop())->toBeFalse()
        ->and(Filament::getPanel('lunar')->isSidebarFullyCollapsibleOnDesktop())->toBeFalse()
        ->and(Filament::getPanel('merchant')->isSidebarCollapsibleOnDesktop())->toBeFalse()
        ->and(Filament::getPanel('merchant')->isSidebarFullyCollapsibleOnDesktop())->toBeFalse();
});

test('the dashboard uses a home icon and unused catalog resources stay hidden', function (): void {
    expect(FilamentIcon::resolve('lunar::dashboard'))->toBe('heroicon-o-home')
        ->and(LunarPanel::getActiveResources())
        ->not->toContain(AttributeGroupResource::class, ProductVariantResource::class);
});

test('removed Lunar pages return not found when visited directly', function () {
    foreach ([
        '/lunar/channels',
        '/lunar/locations',
        '/lunar/taxes',
        '/lunar/products/1/shipping',
        '/lunar/product-variants/1/shipping',
        '/admin/attribute-groups',
        '/admin/product-variants',
    ] as $path) {
        $this->get($path)->assertNotFound();
    }
});
