<?php

use App\Models\User;
use Illuminate\Auth\Notifications\VerifyEmail;
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

    Notification::assertSentTo($user, VerifyEmail::class);
    expect($user->hasVerifiedEmail())->toBeFalse();
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
