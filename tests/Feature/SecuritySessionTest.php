<?php

use App\Domain\Identity\Actions\RevokeUserSessionAction;
use App\Domain\Identity\Actions\TrackCustomerSessionAction;
use App\Models\User;
use App\Models\UserSession;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

test('the customer security page lists the current session without exposing its identifier', function (): void {
    $customer = User::factory()->create();

    $response = $this->actingAs($customer)->get(route('account.security'));

    $response->assertSuccessful()->assertSee('Current session');
    $current = UserSession::query()->whereBelongsTo($customer)->sole();
    $rawSessionId = $current->session_id;
    $storedSessionId = DB::table('user_sessions')->where('id', $current->getKey())->value('session_id');

    expect($storedSessionId)->not->toContain($rawSessionId)
        ->and($current->toArray())->not->toHaveKey('session_id');
    $response->assertDontSee($rawSessionId);
});

test('customers can revoke another session and its server-side session data', function (): void {
    $customer = User::factory()->create();
    $this->actingAs($customer)->get(route('account.security'))->assertSuccessful();

    $otherSessionId = 'other-session-'.str()->random(30);
    $otherSession = UserSession::factory()->create([
        'user_id' => $customer->getKey(),
        'session_hash' => hash('sha256', $otherSessionId),
        'session_id' => $otherSessionId,
    ]);
    $handler = app('session')->driver()->getHandler();
    $handler->write($otherSessionId, 'session-payload');
    expect($handler->read($otherSessionId))->toBe('session-payload');

    $this->post(route('account.security.sessions.revoke', $otherSession->public_id))
        ->assertRedirect(route('account.security'))
        ->assertSessionHas('status', 'The selected session has been signed out.');

    expect($otherSession->fresh()->revoked_at)->not->toBeNull()
        ->and($handler->read($otherSessionId))->toBe('');
    $this->assertDatabaseHas('audit_events', [
        'event' => 'security.session.revoked',
        'actor_id' => $customer->getKey(),
        'subject_id' => $otherSession->getKey(),
    ]);
    $this->assertDatabaseMissing('audit_events', ['metadata->session_id' => $otherSessionId]);
});

test('customers cannot revoke their current session or another customer session', function (): void {
    $owner = User::factory()->create();
    $this->actingAs($owner)->get(route('account.security'))->assertSuccessful();
    $currentSession = UserSession::query()->whereBelongsTo($owner)->sole();
    $otherSession = UserSession::factory()->create();

    expect(fn () => app(RevokeUserSessionAction::class)->handle(
        $owner,
        $currentSession,
        (string) $currentSession->session_id,
    ))->toThrow(ValidationException::class);

    $this->post(route('account.security.sessions.revoke', $otherSession->public_id))
        ->assertNotFound();

    expect($currentSession->fresh()->revoked_at)->toBeNull()
        ->and($otherSession->fresh()->revoked_at)->toBeNull();
});

test('revoked sessions cannot be refreshed back into an active session record', function (): void {
    $customer = User::factory()->create();
    $sessionId = 'revoked-session-'.str()->random(30);
    $lastActiveAt = now()->subHour();
    $session = UserSession::factory()->create([
        'user_id' => $customer->getKey(),
        'session_hash' => hash('sha256', $sessionId),
        'session_id' => $sessionId,
        'last_active_at' => $lastActiveAt,
        'revoked_at' => now(),
    ]);

    $trackedSession = app(TrackCustomerSessionAction::class)
        ->handle($customer, $sessionId, 'Mozilla/5.0 Chrome/120', '127.0.0.1');

    expect($trackedSession->is($session))->toBeTrue()
        ->and($trackedSession->revoked_at)->not->toBeNull()
        ->and($trackedSession->last_active_at->toDateTimeString())->toBe($lastActiveAt->toDateTimeString());
});
