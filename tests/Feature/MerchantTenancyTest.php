<?php

use App\Domain\Merchants\Actions\CreateMerchantAction;
use App\Domain\Merchants\Enums\MerchantMembershipRole;
use App\Domain\Merchants\Enums\MerchantMembershipStatus;
use App\Domain\Merchants\Enums\MerchantStatus;
use App\Domain\Merchants\Enums\MerchantType;
use App\Models\Merchant;
use App\Models\MerchantPrivateProfile;
use App\Models\Product;
use App\Models\User;
use App\Policies\MerchantPolicy;
use App\Policies\ProductPolicy;

test('creating a merchant makes the owner an active member', function () {
    $owner = User::factory()->create();

    $merchant = app(CreateMerchantAction::class)->handle($owner, [
        'type' => MerchantType::LocalDeveloper,
        'display_name' => 'Acme Tools',
    ]);

    expect($merchant->status)->toBe(MerchantStatus::Pending)
        ->and($merchant->getRouteKeyName())->toBe('public_id')
        ->and($merchant->memberships)->toHaveCount(1)
        ->and($merchant->memberships->first()->merchant_role)->toBe(MerchantMembershipRole::Owner)
        ->and($merchant->memberships->first()->status)->toBe(MerchantMembershipStatus::Active)
        ->and($owner->fresh()->merchant_access)->toBeTrue();
});

test('merchant policy denies cross-tenant access', function () {
    $owner = User::factory()->create();
    $otherUser = User::factory()->create();
    $merchant = Merchant::factory()->create(['status' => MerchantStatus::Approved]);

    $merchant->memberships()->create([
        'user_id' => $owner->id,
        'merchant_role' => MerchantMembershipRole::Owner,
        'status' => MerchantMembershipStatus::Active,
    ]);

    $policy = app(MerchantPolicy::class);

    expect($policy->view($owner, $merchant))->toBeTrue()
        ->and($policy->view($otherUser, $merchant))->toBeFalse();
});

test('product policy only permits its merchant members to update it', function () {
    $owner = User::factory()->create();
    $otherUser = User::factory()->create();
    $merchant = Merchant::factory()->create(['status' => MerchantStatus::Approved]);

    $merchant->memberships()->create([
        'user_id' => $owner->id,
        'merchant_role' => MerchantMembershipRole::Owner,
        'status' => MerchantMembershipStatus::Active,
    ]);

    $product = (new Product)->forceFill(['merchant_id' => $merchant->id]);
    $policy = app(ProductPolicy::class);

    expect($policy->update($owner, $product))->toBeTrue()
        ->and($policy->update($otherUser, $product))->toBeFalse();
});

test('merchant private profile values are encrypted and hidden', function () {
    $profile = MerchantPrivateProfile::factory()->create([
        'registration_number' => 'REG-123',
        'tax_identifier' => 'TIN-456',
        'payout_account' => 'account-789',
    ]);

    expect($profile->registration_number)->toBe('REG-123')
        ->and($profile->tax_identifier)->toBe('TIN-456')
        ->and($profile->payout_account)->toBe('account-789')
        ->and($profile->getRawOriginal('tax_identifier'))->not->toBe('TIN-456')
        ->and($profile->toArray())->not->toHaveKeys(['tax_identifier', 'payout_account']);
});
