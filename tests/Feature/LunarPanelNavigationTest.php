<?php

use App\Filament\Admin\Widgets\AdminLatestOrdersTable;
use App\Filament\Admin\Widgets\AdminOrdersSalesChart;
use App\Filament\Admin\Widgets\AdminOrderStatsOverview;
use App\Filament\Admin\Widgets\AdminPopularProductsTable;
use App\Models\Staff;
use App\Support\DashboardExtension;
use Filament\Facades\Filament;
use Filament\Support\Facades\FilamentIcon;
use Livewire\Livewire;
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
            'Coupons',
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

test('merchant panel shares the admin visual theme', function (): void {
    $adminPanel = Filament::getPanel('lunar');
    $merchantPanel = Filament::getPanel('merchant');

    expect($merchantPanel->getViteTheme())->toBe($adminPanel->getViteTheme())
        ->and($merchantPanel->getFontFamily())->toBe($adminPanel->getFontFamily())
        ->and($merchantPanel->getDefaultThemeMode())->toBe($adminPanel->getDefaultThemeMode())
        ->and($merchantPanel->hasTopbar())->toBe($adminPanel->hasTopbar())
        ->and($merchantPanel->getBrandName())->toBe($adminPanel->getBrandName());
});

test('the admin dashboard uses a gap-free widget layout and shows the staff name', function (): void {
    $staff = Staff::forceCreate([
        'first_name' => 'Admin',
        'last_name' => 'Operator',
        'email' => 'admin-dashboard-name@example.test',
        'password' => 'password',
        'admin' => true,
    ]);

    $this->actingAs($staff, 'staff')
        ->get('/admin')
        ->assertSuccessful()
        ->assertSee('Admin Operator');

    $extension = app(DashboardExtension::class);

    expect($extension->getOverviewWidgets([]))->toBe([AdminOrderStatsOverview::class])
        ->and($extension->getChartWidgets([]))->toBe([AdminPopularProductsTable::class, AdminOrdersSalesChart::class])
        ->and($extension->getTableWidgets([]))->toBe([AdminLatestOrdersTable::class])
        ->and((new AdminOrderStatsOverview)->getColumnSpan())->toBe('full')
        ->and((new AdminOrdersSalesChart)->getColumnSpan())->toBe(['lg' => 1, 'xl' => 1])
        ->and((new AdminPopularProductsTable)->getColumnSpan())->toBe(['lg' => 1, 'xl' => 1])
        ->and((new AdminLatestOrdersTable)->getColumnSpan())->toBe('full');

    $latestOrders = Livewire::test(AdminLatestOrdersTable::class)->instance();

    expect($latestOrders->getTable()->getColumns())->toHaveCount(6)
        ->and(collect($latestOrders->getTable()->getColumns())->map(fn ($column): string => $column->getName())->values()->all())
        ->toBe(['reference', 'billingAddress.fullName', 'closed_at', 'payment_status', 'total', 'placed_at']);

    $bestSellers = Livewire::test(AdminPopularProductsTable::class)->instance();

    expect($bestSellers->getTable()->getDescription())->toBeNull();
});

test('the dashboard uses a home icon and unused catalog resources stay hidden', function (): void {
    expect(FilamentIcon::resolve('lunar::dashboard'))->toBe('heroicon-o-home')
        ->and(LunarPanel::getActiveResources())
        ->not->toContain(AttributeGroupResource::class, ProductVariantResource::class);
});

test('removed Lunar pages return not found when visited directly', function (): void {
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
