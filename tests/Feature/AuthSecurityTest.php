<?php

use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\RateLimiter;

test('registration requires terms acceptance', function (): void {
    $this->post(route('register'), [
        'name' => 'Test Customer',
        'email' => 'customer@example.test',
        'password' => 'password',
        'password_confirmation' => 'password',
    ])->assertSessionHasErrors('terms');

    expect(User::query()->where('email', 'customer@example.test')->exists())->toBeFalse();
});

test('public forms reject a completed honeypot', function (string $route, array $payload): void {
    $this->post(route($route), [...$payload, '_website' => 'https://spam.example'])
        ->assertSessionHasErrors('_website');
})->with([
    'contact' => ['contact.store', [
        'name' => 'Spam Bot',
        'email' => 'spam@example.test',
        'topic' => 'other',
        'subject' => 'Spam',
        'message' => 'Spam message',
    ]],
    'developer application' => ['developers.apply', [
        'name' => 'Spam Bot',
        'email' => 'spam@example.test',
        'product_name' => 'Spam Product',
        'product_stage' => 'beta',
        'summary' => 'Spam summary',
    ]],
]);

test('password reset endpoints reject a completed honeypot', function (string $route, array $payload): void {
    $this->post(route($route), [...$payload, '_website' => 'https://spam.example'])
        ->assertSessionHasErrors('_website');
})->with([
    'send reset link' => ['password.email', ['email' => 'customer@example.test']],
    'reset password' => ['password.update', [
        'token' => 'invalid-token',
        'email' => 'customer@example.test',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]],
]);

test('password reset requests are rate limited', function (): void {
    RateLimiter::clear('customer@example.test|127.0.0.1');

    for ($attempt = 0; $attempt < 5; $attempt++) {
        $this->post(route('password.email'), ['email' => 'customer@example.test'])
            ->assertSessionHasNoErrors();
    }

    $this->post(route('password.email'), ['email' => 'customer@example.test'])
        ->assertTooManyRequests();
});

test('legal pages are available for registration consent', function (): void {
    $this->get(route('legal.terms'))->assertOk()->assertSee('Terms of Service');
    $this->get(route('legal.privacy'))->assertOk()->assertSee('Privacy Policy');
});

test('admin panel requires multi-factor authentication', function (): void {
    expect(Filament::getPanel('lunar')->isMultiFactorAuthenticationRequired())->toBeTrue();
});
