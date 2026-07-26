<?php

use App\Enums\AuthorRole;
use App\Enums\AuthorStatus;
use App\Models\Author;
use App\Models\Product;
use App\Models\User;

test('only active visible vendor profiles are public', function (string $state) {
    $author = match ($state) {
        'pending' => Author::factory()->pending()->create(),
        'hidden' => Author::factory()->hidden()->create(),
        'suspended' => Author::factory()->suspended()->create(),
    };

    $this->get(route('vendors.show', $author))->assertNotFound();
})->with(['pending', 'hidden', 'suspended']);

test('active vendor profiles list only published products', function () {
    $author = Author::factory()->verified()->create();
    $published = Product::factory()->for($author)->published()->create(['name' => 'Public App']);
    Product::factory()->for($author)->create(['name' => 'Draft App']);

    $this->get(route('vendors.show', $author))
        ->assertSuccessful()
        ->assertSee('Public App')
        ->assertDontSee('Draft App')
        ->assertSee('Verified');

    expect($published->author->is($author))->toBeTrue();
});

test('vendor directory filters out ineligible profiles', function () {
    $visible = Author::factory()->verified()->create(['name' => 'Visible Studio']);
    Author::factory()->pending()->create(['name' => 'Pending Studio']);

    $this->get(route('vendors.index', ['q' => 'Studio', 'verified' => 1]))
        ->assertSuccessful()
        ->assertSee($visible->name)
        ->assertDontSee('Pending Studio');
});

test('products support public contributors without changing the primary vendor', function () {
    $primary = Author::factory()->create();
    $contributor = Author::factory()->create();
    $product = Product::factory()->for($primary)->published()->create();
    $product->authors()->attach($contributor, [
        'role' => AuthorRole::Contributor,
        'is_primary' => false,
        'is_publicly_displayed' => true,
        'can_manage_product' => false,
        'revenue_share_basis_points' => 0,
    ]);

    expect($product->fresh()->author->is($primary))->toBeTrue()
        ->and($product->fresh()->publicContributors)->toHaveCount(1)
        ->and($product->fresh()->publicContributors->first()->is($contributor))->toBeTrue();
});

test('only approved linked authors may access the author panel', function () {
    $authorUser = User::factory()->create();
    Author::factory()->for($authorUser)->create();
    $pendingUser = User::factory()->create();
    Author::factory()->for($pendingUser)->pending()->create();

    expect($authorUser->canAccessPanel(filament()->getPanel('author')))->toBeTrue()
        ->and($pendingUser->canAccessPanel(filament()->getPanel('author')))->toBeFalse();
});

test('vendor visibility scope returns active public profiles only', function () {
    $active = Author::factory()->create();
    Author::factory()->create(['status' => AuthorStatus::Active, 'is_public' => false]);
    Author::factory()->pending()->create();

    expect(Author::publiclyVisible()->pluck('id'))->toContain($active->id)->toHaveCount(1);
});
