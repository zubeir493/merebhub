<?php

use App\Models\User;
use App\Notifications\VerifyEmailNotification;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;

test('registration sends a verification notification and redirects to the notice page', function () {
    Notification::fake();

    $response = $this->post('/register', [
        'name' => 'New Buyer',
        'email' => 'new-buyer@example.com',
        'password' => 'strong-password',
        'password_confirmation' => 'strong-password',
    ]);

    $response->assertRedirect(route('verification.notice'));
    $this->assertAuthenticated();

    $user = User::query()->where('email', 'new-buyer@example.com')->firstOrFail();

    Notification::assertSentTo($user, VerifyEmailNotification::class);
    expect($user->hasVerifiedEmail())->toBeFalse();
});

test('registration returns the customer to the requested local destination', function (): void {
    Notification::fake();
    $destination = route('account.wishlist');

    $this->get(route('login', ['intent' => 'wishlist', 'redirect' => $destination]))
        ->assertSee(route('register', ['intent' => 'wishlist', 'redirect' => $destination]));

    $response = $this->post(route('register', ['intent' => 'wishlist', 'redirect' => $destination]), [
        'name' => 'Wishlist Buyer',
        'email' => 'wishlist-buyer@example.com',
        'password' => 'strong-password',
        'password_confirmation' => 'strong-password',
    ]);

    $response->assertRedirect($destination);
});

test('registration ignores external redirect destinations', function (): void {
    Notification::fake();

    $response = $this->post(route('register', ['redirect' => 'https://example.com/account']), [
        'name' => 'Safe Buyer',
        'email' => 'safe-buyer@example.com',
        'password' => 'strong-password',
        'password_confirmation' => 'strong-password',
    ]);

    $response->assertRedirect(route('verification.notice'));
});

test('a valid signed verification link marks the email as verified', function () {
    $user = User::factory()->unverified()->create([
        'email' => 'verify-me@example.com',
    ]);

    $url = URL::temporarySignedRoute(
        'verification.verify',
        now()->addMinutes(60),
        [
            'id' => $user->getKey(),
            'hash' => sha1($user->getEmailForVerification()),
        ],
    );

    $this->actingAs($user)
        ->get($url)
        ->assertRedirect(route('account.purchases'))
        ->assertSessionHas('status', 'Email verified.');

    expect($user->fresh()->hasVerifiedEmail())->toBeTrue();
});

test('email verification returns the customer to the original local destination', function (): void {
    $user = User::factory()->unverified()->create([
        'email' => 'verify-destination@example.com',
    ]);
    $destination = route('account.wishlist');

    $url = URL::temporarySignedRoute(
        'verification.verify',
        now()->addMinutes(60),
        [
            'id' => $user->getKey(),
            'hash' => sha1($user->getEmailForVerification()),
            'redirect' => $destination,
        ],
    );

    $this->actingAs($user)
        ->get($url)
        ->assertRedirect($destination)
        ->assertSessionHas('status', 'Email verified.');
});

test('an html-encoded verification signature is rejected with 403', function () {
    $user = User::factory()->unverified()->create([
        'email' => 'encoded-link@example.com',
    ]);

    $url = URL::temporarySignedRoute(
        'verification.verify',
        now()->addMinutes(60),
        [
            'id' => $user->getKey(),
            'hash' => sha1($user->getEmailForVerification()),
        ],
    );

    $brokenUrl = str_replace('&signature=', '&amp;signature=', $url);

    $this->actingAs($user)
        ->get($brokenUrl)
        ->assertForbidden();

    expect($user->fresh()->hasVerifiedEmail())->toBeFalse();
});

test('the verification notice shows a plain local link when mail uses the log driver', function () {
    config(['mail.default' => 'log', 'app.env' => 'local']);

    $user = User::factory()->unverified()->create();

    $this->actingAs($user)
        ->get(route('verification.notice'))
        ->assertSuccessful()
        ->assertSee('Local development')
        ->assertSee('/email/verify/'.$user->getKey().'/', false)
        ->assertSee('signature=', false);
});

test('updating account email resets verification and triggers verification notification', function () {
    Notification::fake();

    $user = User::factory()->create([
        'email' => 'old-email@example.com',
        'email_verified_at' => now(),
    ]);

    $this->actingAs($user)
        ->patch(route('account.settings.update'), [
            'name' => $user->name,
            'email' => 'new-email@example.com',
        ])
        ->assertRedirect();

    $user->refresh();

    expect($user->email)->toBe('new-email@example.com')
        ->and($user->hasVerifiedEmail())->toBeFalse();

    Notification::assertSentTo($user, VerifyEmailNotification::class);
});
