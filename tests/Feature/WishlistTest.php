<?php

use App\Models\Product;
use App\Models\User;
use App\Models\WishlistItem;

beforeEach(function (): void {
    $this->seed();
});

test('wishlist requires authentication and is available from the product page', function (): void {
    $product = Product::published()->with('defaultUrl')->firstOrFail();

    $this->get(route('account.wishlist'))->assertRedirect(route('login'));
    $this->post(route('wishlist.store', $product))->assertRedirect(route('login'));
    $this->get(route('products.show', $product))
        ->assertSuccessful()
        ->assertSee('Sign in to save to wishlist');
});

test('wishlist items persist per account and cannot be removed by another account', function (): void {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $product = Product::published()->with('defaultUrl')->firstOrFail();

    $this->actingAs($user)
        ->post(route('wishlist.store', $product))
        ->assertRedirect(route('products.show', $product));

    $wishlistItem = WishlistItem::query()->sole();

    $this->actingAs($user)
        ->get(route('account.wishlist'))
        ->assertSuccessful()
        ->assertSee($product->name)
        ->assertSee('Wishlist');

    $this->actingAs($otherUser)
        ->get(route('account.wishlist'))
        ->assertSuccessful()
        ->assertDontSee($product->name);

    $this->actingAs($otherUser)
        ->delete(route('wishlist.destroy', $wishlistItem))
        ->assertNotFound();

    $this->from(route('account.wishlist'))
        ->actingAs($user)
        ->delete(route('wishlist.destroy', $wishlistItem))
        ->assertRedirect(route('account.wishlist'));

    expect(WishlistItem::query()->count())->toBe(0);
});
