<?php

use App\Domain\Catalog\Actions\ReviewProductAction;
use App\Domain\Catalog\Actions\SubmitProductForReviewAction;
use App\Domain\Catalog\Enums\ProductPublicationState;
use App\Models\AuditEvent;
use App\Models\Merchant;
use App\Models\Product;
use App\Models\Staff;
use App\Policies\ProductPolicy;
use Lunar\Filament\Models\Staff as FilamentStaff;

test('a merchant can submit a draft product for review', function () {
    $merchant = Merchant::factory()->create();
    $product = Product::factory()->forMerchant($merchant)->create();

    $submitted = app(SubmitProductForReviewAction::class)->handle($product);

    expect($submitted->publication_state)->toBe(ProductPublicationState::Submitted->value);
});

test('a product cannot be submitted after it is published', function () {
    $product = Product::factory()->create([
        'publication_state' => ProductPublicationState::Published->value,
    ]);

    expect(fn () => app(SubmitProductForReviewAction::class)->handle($product))
        ->toThrow(DomainException::class);
});

test('an admin review records a decision and audit note', function () {
    $product = Product::factory()->create([
        'publication_state' => ProductPublicationState::Submitted->value,
    ]);
    $reviewer = FilamentStaff::forceCreate([
        'first_name' => 'Review',
        'last_name' => 'Admin',
        'email' => 'reviewer@example.test',
        'password' => 'password',
        'admin' => true,
    ]);

    $reviewed = app(ReviewProductAction::class)->handle(
        $product,
        $reviewer,
        ProductPublicationState::Published,
        'All required catalog information is present.',
    );

    expect($reviewed->publication_state)->toBe(ProductPublicationState::Published->value)
        ->and($reviewed->published_at)->not->toBeNull()
        ->and(AuditEvent::query()->where('event', 'catalog.product.reviewed')->value('metadata'))->toMatchArray([
            'state' => ProductPublicationState::Published->value,
            'reason' => 'All required catalog information is present.',
        ]);
});

test('only an admin staff member can access the admin product policy', function () {
    $admin = Staff::forceCreate([
        'first_name' => 'Admin',
        'last_name' => 'Staff',
        'email' => 'admin-policy@example.test',
        'password' => 'password',
        'admin' => true,
    ]);
    $staff = Staff::forceCreate([
        'first_name' => 'Regular',
        'last_name' => 'Staff',
        'email' => 'staff-policy@example.test',
        'password' => 'password',
        'admin' => false,
    ]);
    $product = Product::factory()->create();

    $policy = app(ProductPolicy::class);

    expect($policy->view($admin, $product))->toBeTrue()
        ->and($policy->view($staff, $product))->toBeFalse();
});

test('the Lunar Filament staff model can access the admin product policy', function () {
    $admin = FilamentStaff::forceCreate([
        'first_name' => 'Lunar',
        'last_name' => 'Admin',
        'email' => 'lunar-admin-policy@example.test',
        'password' => 'password',
        'admin' => true,
    ]);
    $product = Product::factory()->create();

    expect(app(ProductPolicy::class)->view($admin, $product))->toBeTrue();
});
