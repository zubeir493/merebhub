<?php

use App\Domain\Merchants\Enums\MerchantMembershipRole;
use App\Domain\Merchants\Enums\MerchantMembershipStatus;
use App\Filament\Admin\Extensions\OrderTableExtension;
use App\Filament\Admin\Resources\FulfillmentUnits\FulfillmentUnitResource;
use App\Filament\Admin\Resources\Products\Pages\ListProducts as AdminListProducts;
use App\Filament\Admin\Resources\Products\ProductResource as AdminProductResource;
use App\Filament\Merchant\Resources\Products\ProductResource as MerchantProductResource;
use App\Models\Merchant;
use App\Models\Product;
use App\Models\User;
use Filament\Facades\Filament;
use Livewire\Livewire;
use Lunar\Filament\Models\Staff as FilamentStaff;
use Lunar\Filament\Support\Facades\LunarFilament;
use Lunar\Filament\Tables\Order\OrderTable;

test('merchant panel registers a tenant-scoped product resource', function () {
    expect(Filament::getPanel('merchant')->getResources())
        ->toContain(MerchantProductResource::class);
});

test('admin panel registers the catalog review resource', function () {
    expect(Filament::getPanel('lunar')->getResources())
        ->toContain(AdminProductResource::class);
});

test('staff can feature a product from the admin product table', function () {
    $staff = FilamentStaff::forceCreate([
        'first_name' => 'Catalog',
        'last_name' => 'Admin',
        'email' => 'featured-products@example.test',
        'password' => 'password',
        'admin' => true,
    ]);
    $product = Product::factory()->create();

    Filament::setCurrentPanel(Filament::getPanel('lunar'));
    Filament::bootCurrentPanel();
    $this->actingAs($staff, 'staff');

    Livewire::test(AdminListProducts::class)
        ->call('updateTableColumnState', 'featured', (string) $product->getKey(), true)
        ->assertHasNoErrors();

    expect($product->refresh()->isFeatured())->toBeTrue();
});

test('admin panel registers the fulfillment recovery resource', function () {
    expect(Filament::getPanel('lunar')->getResources())
        ->toContain(FulfillmentUnitResource::class);
});

test('the admin order table registers license regeneration', function (): void {
    expect(collect(LunarFilament::for(OrderTable::class))
        ->first(fn (object $extension): bool => $extension instanceof OrderTableExtension))
        ->toBeInstanceOf(OrderTableExtension::class);
});

test('merchant product resource excludes products from other merchants', function () {
    $owner = User::factory()->create();
    $ownedMerchant = Merchant::factory()->create();
    $otherMerchant = Merchant::factory()->create();

    $ownedMerchant->memberships()->create([
        'user_id' => $owner->id,
        'merchant_role' => MerchantMembershipRole::Owner,
        'status' => MerchantMembershipStatus::Active,
    ]);

    $ownedProduct = Product::factory()->forMerchant($ownedMerchant)->create();
    $otherProduct = Product::factory()->forMerchant($otherMerchant)->create();

    $this->actingAs($owner);

    expect(MerchantProductResource::getEloquentQuery()->pluck('id')->all())
        ->toBe([$ownedProduct->id]);
});

test('catalog resources eager load product URLs for panel links', function () {
    $product = Product::factory()->create();

    $adminProduct = AdminProductResource::getEloquentQuery()
        ->whereKey($product->getKey())
        ->firstOrFail();
    $merchant = User::factory()->create();
    $ownedMerchant = Merchant::factory()->create();
    $ownedMerchant->memberships()->create([
        'user_id' => $merchant->id,
        'merchant_role' => MerchantMembershipRole::Owner,
        'status' => MerchantMembershipStatus::Active,
    ]);
    $merchantProduct = Product::factory()->forMerchant($ownedMerchant)->create();

    $this->actingAs($merchant);
    $scopedMerchantProduct = MerchantProductResource::getEloquentQuery()
        ->whereKey($merchantProduct->getKey())
        ->firstOrFail();

    expect($adminProduct->relationLoaded('defaultUrl'))->toBeTrue()
        ->and($scopedMerchantProduct->relationLoaded('defaultUrl'))->toBeTrue();
});
