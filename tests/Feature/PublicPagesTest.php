<?php

use App\Models\MerchantApplication;
use App\Models\Product;
use App\Models\ProductReview;
use App\Models\User;
use App\Notifications\ContactMessageReceivedNotification;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Support\Facades\Notification;

test('public editorial pages share the storefront shell and page header', function (string $route) {
    $this->get(route($route))
        ->assertSuccessful()
        ->assertSee('data-public-shell', false)
        ->assertSee('data-public-page-header', false);
})->with([
    'home' => 'home',
    'store' => 'store.index',
    'developers' => 'developers.index',
    'contact' => 'contact.index',
    'developer directory' => 'vendors.index',
]);

test('public navigation contains the same destinations on desktop and mobile', function () {
    $response = $this->get(route('developers.index'));

    $response
        ->assertSuccessful()
        ->assertSee('data-desktop-public-navigation', false)
        ->assertSee('data-mobile-public-navigation', false)
        ->assertSeeInOrder(['Store', 'Developers', 'Contact'])
        ->assertSee(route('store.index'))
        ->assertSee(route('developers.index'))
        ->assertSee(route('contact.index'));
});

test('home page includes the layered marketplace sections', function () {
    $this->get(route('home'))
        ->assertSuccessful()
        ->assertSee('data-home-local-build', false)
        ->assertSee('Built here doesn’t mean built small.')
        ->assertSee('data-home-marketplace-cta', false)
        ->assertSee('Your next essential tool is already here.');
});

test('home page hero uses featured products and published customer ratings', function () {
    $this->seed();
    Product::query()->get()->each(fn (Product $product) => $product->setFeatured(false));
    $product = Product::published()->with('defaultUrl')->firstOrFail();
    $product->setFeatured(true);
    $reviewer = User::factory()->create();
    ProductReview::query()->create([
        'product_id' => $product->getKey(),
        'user_id' => $reviewer->getKey(),
        'rating' => 3,
        'body' => 'A solid product with room to grow.',
        'status' => 'published',
    ]);
    ProductReview::query()->create([
        'product_id' => $product->getKey(),
        'user_id' => User::factory()->create()->getKey(),
        'rating' => 5,
        'body' => 'A polished product that works very well.',
        'status' => 'published',
    ]);

    $this->get(route('home'))
        ->assertSee('Featured products')
        ->assertSee($product->name)
        ->assertSee('4.0 rating');
});

test('developer and contact pages render their public forms', function (string $route, string $heading, string $action) {
    $this->get(route($route))
        ->assertSuccessful()
        ->assertSee($heading)
        ->assertSee('action="'.route($action).'"', false);
})->with([
    'developer application' => ['developers.index', 'Your software deserves a market.', 'developers.apply'],
    'contact form' => ['contact.index', 'Start the right conversation.', 'contact.store'],
]);

test('guests can submit a developer application', function () {
    $response = $this->post(route('developers.apply'), [
        'name' => 'Liya Tesfaye',
        'email' => 'liya@example.com',
        'studio' => 'North Studio',
        'website_url' => 'https://north.example.com',
        'product_name' => 'Kora Desk',
        'product_url' => 'https://north.example.com/kora',
        'product_stage' => 'beta',
        'summary' => 'A lightweight workspace for small creative teams.',
        'audience' => 'Independent studios and small agencies.',
    ]);

    $response
        ->assertRedirect(route('developers.index').'#apply')
        ->assertSessionHas('status', 'Application received. We’ll review your product and get back to you by email.');

    $application = MerchantApplication::query()->latest('id')->firstOrFail();

    expect($application->user_id)->toBeNull()
        ->and($application->submitted_data)->toMatchArray([
            'email' => 'liya@example.com',
            'product_name' => 'Kora Desk',
            'product_stage' => 'beta',
        ]);
});

test('developer applications are linked to a signed in account', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->post(route('developers.apply'), [
        'name' => $user->name,
        'email' => $user->email,
        'product_name' => 'Mela Notes',
        'product_stage' => 'live',
        'summary' => 'A structured notes app for research teams.',
    ])->assertRedirect(route('developers.index').'#apply');

    expect(MerchantApplication::query()->latest('id')->value('user_id'))->toBe($user->id);
});

test('developer applications reject incomplete product details', function () {
    $this->post(route('developers.apply'), [])
        ->assertSessionHasErrors(['name', 'email', 'product_name', 'product_stage', 'summary']);

    expect(MerchantApplication::query()->count())->toBe(0);
});

test('contact messages are sent to the configured support inbox', function () {
    Notification::fake();
    config()->set('support.inbox_email', 'team@merebhub.test');

    $response = $this->post(route('contact.store'), [
        'name' => 'Abel Girma',
        'email' => 'abel@example.com',
        'topic' => 'partnership',
        'subject' => 'University showcase',
        'message' => 'We would like to discuss a student software showcase.',
    ]);

    $response
        ->assertRedirect(route('contact.index').'#contact-form')
        ->assertSessionHas('status', 'Message sent. Thanks for reaching out.');

    Notification::assertSentOnDemand(
        ContactMessageReceivedNotification::class,
        function (ContactMessageReceivedNotification $notification, array $channels, AnonymousNotifiable $notifiable): bool {
            return $channels === ['mail']
                && $notifiable->routeNotificationFor('mail') === 'team@merebhub.test'
                && $notification->email === 'abel@example.com'
                && $notification->subject === 'University showcase';
        },
    );
});

test('invalid contact messages are not sent', function () {
    Notification::fake();

    $this->post(route('contact.store'), [
        'email' => 'not-an-email',
        'topic' => 'unknown',
    ])->assertSessionHasErrors(['name', 'email', 'topic', 'subject', 'message']);

    Notification::assertNothingSent();
});

test('contact notification escapes message content', function () {
    $notification = new ContactMessageReceivedNotification(
        name: 'Ada <script>alert(1)</script>',
        email: 'ada@example.com',
        topic: 'other',
        subject: 'Hello',
        message: '<script>alert(2)</script>',
    );

    $html = (string) $notification->toMail(new AnonymousNotifiable)->render();

    expect($html)
        ->not->toContain('<script>alert(1)</script>')
        ->not->toContain('<script>alert(2)</script>');
});
