<?php

use App\Domain\Merchants\Actions\CreateMerchantAction;
use App\Domain\Merchants\Enums\MerchantStatus;
use App\Domain\Merchants\Enums\MerchantType;
use App\Filament\Merchant\Pages\Dashboard;
use App\Filament\Merchant\Resources\Customers\Pages\ListCustomers;
use App\Filament\Merchant\Resources\Orders\Pages\ListOrders;
use App\Filament\Merchant\Widgets\MerchantRecentSales;
use App\Filament\Merchant\Widgets\MerchantSalesChart;
use App\Filament\Merchant\Widgets\MerchantStatsOverview;
use App\Filament\Merchant\Widgets\MerchantTopProducts;
use App\Filament\Merchant\Widgets\MerchantWelcome;
use App\Models\Merchant;
use App\Models\Product;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\Auth;
use Livewire\Livewire;

test('merchant dashboard registers operational widgets', function (): void {
    $widgets = array_values(Filament::getPanel('merchant')->getWidgets());

    expect($widgets)
        ->toContain(MerchantStatsOverview::class)
        ->toContain(MerchantSalesChart::class)
        ->toContain(MerchantTopProducts::class)
        ->toContain(MerchantRecentSales::class);
});

test('merchant dashboard widgets only show products from the signed in merchant', function (): void {
    $ownedMerchant = app(CreateMerchantAction::class)->handleWithNewOwner(
        [
            'name' => 'Owned Merchant',
            'email' => 'owned-dashboard@example.test',
            'password' => 'password',
        ],
        [
            'type' => MerchantType::LocalDeveloper,
            'display_name' => 'Owned Merchant',
            'status' => MerchantStatus::Approved,
        ],
    );
    $otherMerchant = Merchant::factory()->create([
        'display_name' => 'Other Merchant',
        'status' => MerchantStatus::Approved,
        'approved_at' => now(),
    ]);
    Product::factory()->forMerchant($ownedMerchant)->create([
        'name' => ['en' => 'Owned Product'],
    ]);
    Product::factory()->forMerchant($otherMerchant)->create([
        'name' => ['en' => 'Other Product'],
    ]);

    $owner = User::query()->where('email', 'owned-dashboard@example.test')->firstOrFail();
    Auth::guard('web')->login($owner);
    Filament::setCurrentPanel(Filament::getPanel('merchant'));
    Filament::bootCurrentPanel();

    Livewire::test(MerchantStatsOverview::class)
        ->assertSee('Published products')
        ->assertSee('1 total catalog products');

    Livewire::test(MerchantTopProducts::class)
        ->assertSee('Owned Product')
        ->assertDontSee('Other Product');

    Livewire::test(MerchantSalesChart::class)
        ->assertSee('Sales performance');

    Livewire::test(MerchantRecentSales::class)
        ->assertSee('Recent sales');
});

test('merchant panel navigation is organized around overview, sales, customers, and catalog', function (): void {
    app(CreateMerchantAction::class)->handleWithNewOwner(
        [
            'name' => 'Navigation Merchant',
            'email' => 'navigation-dashboard@example.test',
            'password' => 'password',
        ],
        [
            'type' => MerchantType::LocalDeveloper,
            'display_name' => 'Navigation Merchant',
            'status' => MerchantStatus::Approved,
        ],
    );
    $owner = User::query()->where('email', 'navigation-dashboard@example.test')->firstOrFail();
    Auth::guard('web')->login($owner);
    Filament::setCurrentPanel(Filament::getPanel('merchant'));
    Filament::bootCurrentPanel();

    $items = collect(Filament::getPanel('merchant')->getNavigation())
        ->flatMap(fn ($group): array => collect($group->getItems())->map(fn ($item): string => $item->getLabel())->all())
        ->values()
        ->all();

    expect($items)->toBe(['Overview', 'Orders', 'Customers', 'Products']);
});

test('the merchant dashboard shows the signed-in user name in the topbar', function (): void {
    $owner = User::factory()->create([
        'name' => 'Merchant Operator',
        'merchant_access' => true,
    ]);

    $merchant = Merchant::factory()->create([
        'status' => MerchantStatus::Approved,
        'approved_at' => now(),
    ]);

    $merchant->memberships()->create([
        'user_id' => $owner->id,
        'merchant_role' => 'owner',
        'status' => 'active',
    ]);

    $this->actingAs($owner, 'web')
        ->get('/merchant')
        ->assertSuccessful()
        ->assertSee('Merchant Operator');
});

test('merchant overview uses the custom dashboard and welcome widget', function (): void {
    app(CreateMerchantAction::class)->handleWithNewOwner(
        [
            'name' => 'Welcome Merchant',
            'email' => 'welcome-dashboard@example.test',
            'password' => 'password',
        ],
        [
            'type' => MerchantType::LocalDeveloper,
            'display_name' => 'Welcome Merchant',
            'status' => MerchantStatus::Approved,
        ],
    );
    $owner = User::query()->where('email', 'welcome-dashboard@example.test')->firstOrFail();
    Auth::guard('web')->login($owner);
    Filament::setCurrentPanel(Filament::getPanel('merchant'));
    Filament::bootCurrentPanel();

    $dashboard = app(Dashboard::class);

    expect($dashboard->getWidgets())->toBe([
        MerchantWelcome::class,
        MerchantStatsOverview::class,
        MerchantSalesChart::class,
        MerchantTopProducts::class,
        MerchantRecentSales::class,
    ])
        ->and($dashboard->getColumns())->toBe([
            'sm' => 1,
            'md' => 2,
            'lg' => 3,
        ]);

    Livewire::test(MerchantWelcome::class)
        ->assertSee('Keep your catalog moving')
        ->assertSee('Welcome Merchant');
});

test('merchant order and customer resources open for an approved merchant', function (): void {
    app(CreateMerchantAction::class)->handleWithNewOwner(
        [
            'name' => 'Resources Merchant',
            'email' => 'resources-dashboard@example.test',
            'password' => 'password',
        ],
        [
            'type' => MerchantType::LocalDeveloper,
            'display_name' => 'Resources Merchant',
            'status' => MerchantStatus::Approved,
        ],
    );
    $owner = User::query()->where('email', 'resources-dashboard@example.test')->firstOrFail();
    Auth::guard('web')->login($owner);
    Filament::setCurrentPanel(Filament::getPanel('merchant'));
    Filament::bootCurrentPanel();

    Livewire::test(ListOrders::class)
        ->assertSee('No sales yet');

    Livewire::test(ListCustomers::class)
        ->assertSee('No customers yet');
});
