<?php

use App\Models\Product;
use App\Models\ProductReview;
use App\Models\User;
use Lunar\Core\Models\Order;
use Lunar\Core\Models\OrderLine;

beforeEach(function (): void {
    $this->seed();
});

test('published product details show the gallery and description and reviews tabs', function (): void {
    $product = Product::published()->with('defaultUrl')->firstOrFail();

    $this->get(route('products.show', $product))
        ->assertSee('data-product-gallery', false)
        ->assertSee('data-product-tabs', false)
        ->assertSee('About this software')
        ->assertSee('Customer reviews')
        ->assertSee('Sign in to write a review');
});

test('an authenticated customer can publish and update one product review', function (): void {
    $user = User::factory()->create();
    $product = Product::published()->with('defaultUrl')->firstOrFail();
    $variant = $product->variants->firstOrFail();
    $order = Order::factory()->placed()->create(['user_id' => $user->getKey()]);
    OrderLine::factory()->create([
        'order_id' => $order->getKey(),
        'purchasable_type' => $variant->getMorphClass(),
        'purchasable_id' => $variant->getKey(),
        'type' => 'digital',
        'requires_shipping' => false,
        'requires_fulfilment' => false,
    ]);
    $reviewUrl = route('products.reviews.store', $product);

    $response = $this->actingAs($user)->post($reviewUrl, [
        'rating' => 5,
        'title' => 'A dependable product',
        'body' => 'This product is clear, useful, and works exactly as described.',
    ]);

    $response
        ->assertRedirect(route('products.show', $product).'#product-reviews')
        ->assertSessionHas('status', 'Your review has been published.');

    $review = ProductReview::query()->firstOrFail();

    $this->assertModelExists($review);

    $this->actingAs($user)->post($reviewUrl, [
        'rating' => 4,
        'title' => 'Still a strong choice',
        'body' => 'The product remains useful and the overall experience is very good.',
    ]);

    expect($review->refresh()->rating)->toBe(4)
        ->and($review->title)->toBe('Still a strong choice')
        ->and(ProductReview::query()->count())->toBe(1);

    $this->actingAs($user)
        ->get(route('products.show', $product))
        ->assertSee('4.0')
        ->assertSee('1 ratings');
});

test('a customer can only review a product they purchased on the same account', function (): void {
    $user = User::factory()->create();
    $product = Product::published()->with('defaultUrl')->firstOrFail();
    $reviewUrl = route('products.reviews.store', $product);

    $this->actingAs($user)
        ->get(route('products.show', $product))
        ->assertSee('Purchase required to review')
        ->assertDontSee('Publish review');

    $this->actingAs($user)
        ->from(route('products.show', $product))
        ->post($reviewUrl, [
            'rating' => 5,
            'body' => 'This review should not be stored without a purchase.',
        ])
        ->assertRedirect(route('products.show', $product).'#product-reviews')
        ->assertSessionHasErrors('review');

    expect(ProductReview::query()->count())->toBe(0);
});

test('a product review requires authentication and valid review content', function (): void {
    $product = Product::published()->with('defaultUrl')->firstOrFail();
    $reviewUrl = route('products.reviews.store', $product);

    $this->post($reviewUrl, [
        'rating' => 5,
        'body' => 'This review should not be stored.',
    ])->assertRedirect(route('login'));

    $this->actingAs(User::factory()->create())
        ->from(route('products.show', $product))
        ->post($reviewUrl, [
            'rating' => 0,
            'body' => 'Too short',
        ])
        ->assertRedirect(route('products.show', $product))
        ->assertSessionHasErrors(['rating', 'body']);

    expect(ProductReview::query()->count())->toBe(0);
});
