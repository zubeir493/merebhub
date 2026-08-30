<?php

use App\Domain\Shared\Actions\RecordAuditEventAction;
use App\Domain\Shared\Actions\StoreOutboxMessageAction;
use App\Models\AuditEvent;
use App\Models\OutboxMessage;
use App\Models\Staff;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Support\Str;

test('public health endpoint returns a correlation id', function () {
    $response = $this->getJson(route('health'));

    $response->assertOk()
        ->assertJsonPath('status', 'ok')
        ->assertHeader('X-Correlation-ID');

    expect(Str::isUuid($response->headers->get('X-Correlation-ID')))->toBeTrue();
});

test('correlation middleware preserves a valid incoming id', function () {
    $correlationId = (string) Str::uuid();

    $this->getJson(route('health'), ['X-Correlation-ID' => $correlationId])
        ->assertHeader('X-Correlation-ID', $correlationId);
});

test('audit action redacts sensitive metadata and stores only a hash of the ip', function () {
    $auditEvent = app(RecordAuditEventAction::class)->handle(
        'identity.login',
        metadata: [
            'email' => 'customer@example.com',
            'nested' => ['token' => 'do-not-store'],
            'attempt' => 1,
        ],
    );

    expect($auditEvent)->toBeInstanceOf(AuditEvent::class)
        ->and($auditEvent->metadata)->toMatchArray([
            'email' => '[REDACTED]',
            'nested' => ['token' => '[REDACTED]'],
            'attempt' => 1,
        ]);

    if ($auditEvent->getRawOriginal('ip_hash') !== null) {
        expect($auditEvent->getRawOriginal('ip_hash'))->toHaveLength(64);
    }
});

test('outbox action records a durable event with its aggregate reference', function () {
    $auditEvent = AuditEvent::query()->create([
        'public_id' => (string) Str::uuid(),
        'event' => 'test.subject',
    ]);

    $message = app(StoreOutboxMessageAction::class)->handle(
        'test.subject.created',
        ['reference' => $auditEvent->public_id],
        $auditEvent,
    );

    expect($message)->toBeInstanceOf(OutboxMessage::class)
        ->and($message->aggregate_type)->toBe($auditEvent->getMorphClass())
        ->and($message->aggregate_id)->toBe((string) $auditEvent->getKey())
        ->and($message->payload)->toBe(['reference' => $auditEvent->public_id]);
});

test('deep health is protected by authentication', function () {
    $this->get(route('health.deep'))->assertRedirect(route('login'));
});

test('the Inertia application shell is available', function () {
    $this->get(route('app-shell'))
        ->assertOk()
        ->assertSee('PlatformShell');
});

test('panel access is separated by account type', function () {
    $staff = new Staff(['admin' => true]);
    $user = new User;

    expect($staff->canAccessPanel(Filament::getPanel('admin')))->toBeTrue()
        ->and($staff->canAccessPanel(Filament::getPanel('merchant')))->toBeFalse()
        ->and($user->canAccessPanel(Filament::getPanel('admin')))->toBeFalse()
        ->and($user->canAccessPanel(Filament::getPanel('merchant')))->toBeFalse();
});
