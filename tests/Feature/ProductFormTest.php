<?php

use App\Domain\Merchants\Enums\MerchantMembershipRole;
use App\Domain\Merchants\Enums\MerchantMembershipStatus;
use App\Domain\Merchants\Enums\MerchantStatus;
use App\Filament\Admin\Resources\Products\Pages\CreateProduct as AdminCreateProduct;
use App\Filament\Admin\Resources\Products\Pages\EditProduct as AdminEditProduct;
use App\Filament\Merchant\Resources\Products\Pages\CreateProduct as MerchantCreateProduct;
use App\Filament\Merchant\Resources\Products\Pages\EditProduct as MerchantEditProduct;
use App\Models\Merchant;
use App\Models\Product;
use App\Models\User;
use Filament\Facades\Filament;
use Livewire\Livewire;
use Lunar\Core\Models\ProductType;
use Lunar\Filament\Models\Staff as FilamentStaff;

test('staff can create a product with scalar translated form values', function () {
    $staff = FilamentStaff::forceCreate([
        'first_name' => 'Catalog',
        'last_name' => 'Admin',
        'email' => 'catalog-create@example.test',
        'password' => 'password',
        'admin' => true,
    ]);
    $productType = ProductType::factory()->create();

    Filament::setCurrentPanel(Filament::getPanel('lunar'));
    Filament::bootCurrentPanel();
    $this->actingAs($staff, 'staff');

    Livewire::test(AdminCreateProduct::class)
        ->fillForm([
            'name' => 'Created product',
            'product_type_id' => $productType->getKey(),
            'description' => 'A product created by staff.',
            'status' => 'draft',
            'publication_state' => 'draft',
            'source_type' => 'local_developer',
            'support_owner' => 'merebhub',
        ])
        ->call('create')
        ->assertHasNoFormErrors()
        ->assertNotified()
        ->assertRedirect();

    $createdProduct = Product::query()->latest('id')->firstOrFail();

    expect($createdProduct->name)->toBe('Created product')
        ->and($createdProduct->description)->toBe('A product created by staff.');
});

test('staff can update a product whose translated values are hydrated as locale maps', function () {
    $staff = FilamentStaff::forceCreate([
        'first_name' => 'Catalog',
        'last_name' => 'Admin',
        'email' => 'catalog-edit@example.test',
        'password' => 'password',
        'admin' => true,
    ]);
    $product = Product::factory()->create([
        'name' => collect(['en' => 'Original product']),
        'description' => collect(['en' => 'Original description']),
    ]);

    Filament::setCurrentPanel(Filament::getPanel('lunar'));
    Filament::bootCurrentPanel();
    $this->actingAs($staff, 'staff');

    Livewire::test(AdminEditProduct::class, ['record' => $product->getKey()])
        ->fillForm([
            'name' => 'Updated product',
            'description' => 'Updated description.',
        ])
        ->call('save')
        ->assertHasNoFormErrors()
        ->assertNotified();

    expect($product->refresh()->name)->toBe('Updated product')
        ->and($product->description)->toBe('Updated description.');
});

test('a merchant can create a product with translated form values', function () {
    $user = User::factory()->create();
    $merchant = Merchant::factory()->create(['status' => MerchantStatus::Approved]);
    $merchant->memberships()->create([
        'user_id' => $user->getKey(),
        'merchant_role' => MerchantMembershipRole::Owner,
        'status' => MerchantMembershipStatus::Active,
    ]);
    $productType = ProductType::factory()->create();

    Filament::setCurrentPanel(Filament::getPanel('merchant'));
    Filament::bootCurrentPanel();
    $this->actingAs($user);

    Livewire::test(MerchantCreateProduct::class)
        ->fillForm([
            'name' => 'Merchant product',
            'product_type_id' => $productType->getKey(),
            'merchant_id' => $merchant->getKey(),
            'description' => 'A product submitted by a merchant.',
            'source_type' => 'local_developer',
            'publication_state' => 'draft',
        ])
        ->call('create')
        ->assertHasNoFormErrors()
        ->assertNotified()
        ->assertRedirect();

    $createdProduct = Product::query()->latest('id')->firstOrFail();

    expect($createdProduct->name)->toBe('Merchant product')
        ->and($createdProduct->merchant_id)->toBe($merchant->getKey());
});

test('a merchant can update a product whose translated values are hydrated as locale maps', function () {
    $user = User::factory()->create();
    $merchant = Merchant::factory()->create(['status' => MerchantStatus::Approved]);
    $merchant->memberships()->create([
        'user_id' => $user->getKey(),
        'merchant_role' => MerchantMembershipRole::Owner,
        'status' => MerchantMembershipStatus::Active,
    ]);
    $product = Product::factory()->forMerchant($merchant)->create([
        'name' => collect(['en' => 'Original merchant product']),
        'description' => collect(['en' => 'Original merchant description']),
    ]);

    Filament::setCurrentPanel(Filament::getPanel('merchant'));
    Filament::bootCurrentPanel();
    $this->actingAs($user);

    Livewire::test(MerchantEditProduct::class, ['record' => $product->getKey()])
        ->fillForm([
            'name' => 'Updated merchant product',
            'description' => 'Updated merchant description.',
        ])
        ->call('save')
        ->assertHasNoFormErrors()
        ->assertNotified();

    expect($product->refresh()->name)->toBe('Updated merchant product')
        ->and($product->description)->toBe('Updated merchant description.');
});
