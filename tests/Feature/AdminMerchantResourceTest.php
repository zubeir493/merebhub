<?php

use App\Domain\Merchants\Actions\CreateMerchantAction;
use App\Domain\Merchants\Enums\MerchantMembershipRole;
use App\Domain\Merchants\Enums\MerchantMembershipStatus;
use App\Domain\Merchants\Enums\MerchantStatus;
use App\Domain\Merchants\Enums\MerchantType;
use App\Filament\Admin\Resources\Merchants\MerchantResource;
use App\Filament\Admin\Resources\Merchants\Pages\CreateMerchant;
use App\Filament\Admin\Resources\Merchants\Pages\EditMerchant;
use App\Filament\Admin\Resources\Merchants\Pages\ListMerchants;
use App\Models\Merchant;
use App\Models\Staff;
use App\Models\User;
use Filament\Auth\Pages\Login;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;

beforeEach(function (): void {
    $this->staff = Staff::factory()->create(['admin' => true]);

    Filament::setCurrentPanel(Filament::getPanel('lunar'));
    Filament::bootCurrentPanel();

    $this->actingAs($this->staff, 'staff');
});

test('admin panel registers the merchant resource', function (): void {
    expect(Filament::getPanel('lunar')->getResources())
        ->toContain(MerchantResource::class);
});

test('staff can create an approved merchant and account from the admin resource', function (): void {
    Livewire::test(CreateMerchant::class)
        ->fillForm([
            'display_name' => 'Acme Tools',
            'account_email' => 'owner@acme.test',
            'account_password' => 'password',
            'legal_name' => 'Acme Tools Ltd',
            'slug' => 'acme-tools',
            'type' => MerchantType::LocalDeveloper->value,
        ])
        ->call('create')
        ->assertHasNoFormErrors()
        ->assertNotified()
        ->assertRedirect();

    $merchant = Merchant::query()->where('slug', 'acme-tools')->firstOrFail();
    $owner = User::query()->where('email', 'owner@acme.test')->firstOrFail();

    expect($merchant->status)->toBe(MerchantStatus::Approved)
        ->and($merchant->approved_at)->not->toBeNull()
        ->and($owner->name)->toBe('Acme Tools')
        ->and($owner->email_verified_at)->not->toBeNull()
        ->and(Hash::check('password', $owner->password))->toBeTrue()
        ->and(Auth::guard('web')->validate(['email' => 'owner@acme.test', 'password' => 'password']))->toBeTrue()
        ->and($owner->canAccessPanel(Filament::getPanel('merchant')))->toBeTrue()
        ->and($merchant->users()->whereKey($owner->getKey())->exists())->toBeTrue()
        ->and($merchant->memberships()->where('user_id', $owner->getKey())->value('status'))
        ->toBe(MerchantMembershipStatus::Active)
        ->and($owner->fresh()->merchant_access)->toBeTrue();
});

test('staff can approve a pending merchant from the admin table', function (): void {
    $merchant = Merchant::factory()->create([
        'status' => MerchantStatus::Pending,
        'approved_at' => null,
    ]);

    Livewire::test(ListMerchants::class)
        ->assertCanSeeTableRecords([$merchant])
        ->callTableAction('approve', $merchant);

    $merchant->refresh();

    expect($merchant->status)->toBe(MerchantStatus::Approved)
        ->and($merchant->approved_at)->not->toBeNull();
});

test('staff can suspend an approved merchant from the admin table', function (): void {
    $merchant = Merchant::factory()->create([
        'status' => MerchantStatus::Approved,
        'approved_at' => now(),
    ]);

    Livewire::test(ListMerchants::class)
        ->assertCanSeeTableRecords([$merchant])
        ->callTableAction('suspend', $merchant);

    expect($merchant->refresh()->status)->toBe(MerchantStatus::Suspended);
});

test('staff can edit the merchant and owner account from the edit form', function (): void {
    $owner = User::factory()->create([
        'name' => 'Original Merchant',
        'email' => 'original@merchant.test',
    ]);
    $merchant = Merchant::factory()->create([
        'display_name' => 'Original Merchant',
    ]);
    $merchant->memberships()->create([
        'user_id' => $owner->getKey(),
        'merchant_role' => MerchantMembershipRole::Owner,
        'status' => MerchantMembershipStatus::Active,
    ]);

    Livewire::test(EditMerchant::class, ['record' => $merchant->getRouteKey()])
        ->fillForm([
            'display_name' => 'Updated Merchant',
            'account_email' => 'updated@merchant.test',
            'account_password' => 'updated-password',
            'slug' => $merchant->slug,
            'type' => MerchantType::LocalDeveloper->value,
        ])
        ->call('save')
        ->assertHasNoFormErrors()
        ->assertNotified();

    expect($merchant->refresh()->display_name)->toBe('Updated Merchant')
        ->and($owner->refresh()->name)->toBe('Updated Merchant')
        ->and($owner->email)->toBe('updated@merchant.test')
        ->and(Hash::check('updated-password', $owner->password))->toBeTrue();
});

test('staff can reset a merchant owner password from the edit header', function (): void {
    $owner = User::factory()->create();
    $merchant = Merchant::factory()->create([
        'status' => MerchantStatus::Approved,
        'approved_at' => now(),
    ]);
    $merchant->memberships()->create([
        'user_id' => $owner->getKey(),
        'merchant_role' => MerchantMembershipRole::Owner,
        'status' => MerchantMembershipStatus::Active,
    ]);

    Livewire::test(EditMerchant::class, ['record' => $merchant->getRouteKey()])
        ->assertActionVisible('resetPassword')
        ->callAction('resetPassword', ['password' => 'reset-password'])
        ->assertNotified();

    expect(Hash::check('reset-password', $owner->refresh()->password))->toBeTrue();
});

test('staff can suspend an approved merchant from the edit header', function (): void {
    $merchant = Merchant::factory()->create([
        'status' => MerchantStatus::Approved,
        'approved_at' => now(),
    ]);

    Livewire::test(EditMerchant::class, ['record' => $merchant->getRouteKey()])
        ->assertActionVisible('toggleStatus')
        ->callAction('toggleStatus')
        ->assertNotified();

    expect($merchant->refresh()->status)->toBe(MerchantStatus::Suspended);
});

test('an admin-created merchant account can log in to the merchant panel', function (): void {
    $merchant = app(CreateMerchantAction::class)->handleWithNewOwner(
        [
            'name' => 'Login Merchant',
            'email' => 'login@merchant.test',
            'password' => 'password',
        ],
        [
            'type' => MerchantType::LocalDeveloper,
            'display_name' => 'Login Merchant',
            'status' => MerchantStatus::Approved,
        ],
    );

    $owner = User::query()->where('email', 'login@merchant.test')->firstOrFail();

    Auth::guard('staff')->logout();
    Filament::setCurrentPanel(Filament::getPanel('merchant'));
    Filament::bootCurrentPanel();

    Livewire::test(Login::class)
        ->fillForm([
            'email' => $owner->email,
            'password' => 'password',
        ])
        ->call('authenticate')
        ->assertRedirect();

    expect($merchant->refresh()->status)->toBe(MerchantStatus::Approved);
});
