<?php

use App\Domain\Support\Enums\SupportAttachmentScanStatus;
use App\Domain\Support\Enums\SupportTicketStatus;
use App\Models\SupportTicket;
use App\Models\SupportTicketAttachment;
use App\Models\SupportTicketMessage;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;

beforeEach(function (): void {
    config()->set('support.inbox_email', null);
    Storage::fake('private');
    Notification::fake();
});

test('customers can create tickets with private quarantined attachments', function (): void {
    $customer = User::factory()->create();

    $this->actingAs($customer)
        ->post(route('account.support.store'), [
            'subject' => 'Activation problem',
            'body' => 'Please help me activate my purchase.',
            'attachments' => [UploadedFile::fake()->createWithContent('activation.txt', 'activation log')],
        ])
        ->assertRedirect();

    $ticket = SupportTicket::query()->sole();
    $attachment = SupportTicketAttachment::query()->sole();
    $message = SupportTicketMessage::query()->sole();

    expect($ticket->status)->toBe(SupportTicketStatus::Open)
        ->and($message->body)->toBe('Please help me activate my purchase.')
        ->and($attachment->scan_status)->toBe(SupportAttachmentScanStatus::Pending)
        ->and($attachment->disk)->toBe('private')
        ->and($attachment->checksum)->toBe(hash('sha256', 'activation log'))
        ->and($attachment->toArray())->not->toHaveKey('path');
    Storage::disk('private')->assertExists($attachment->path);
});

test('customers cannot see internal notes or download unreviewed files', function (): void {
    $customer = User::factory()->create();
    $ticket = SupportTicket::factory()->create(['user_id' => $customer->getKey()]);
    $publicMessage = SupportTicketMessage::factory()->create([
        'support_ticket_id' => $ticket->getKey(),
        'user_id' => $customer->getKey(),
        'body' => 'Public customer message',
    ]);
    SupportTicketMessage::factory()->create([
        'support_ticket_id' => $ticket->getKey(),
        'user_id' => null,
        'staff_id' => null,
        'is_internal' => true,
        'body' => 'Confidential staff-only note',
    ]);
    $attachment = SupportTicketAttachment::factory()->create([
        'support_ticket_message_id' => $publicMessage->getKey(),
        'path' => 'support/test/pending.txt',
    ]);
    Storage::disk('private')->put($attachment->path, 'safe after review');

    $this->actingAs($customer)
        ->get(route('account.support.show', $ticket->public_id))
        ->assertSuccessful()
        ->assertSee('Public customer message')
        ->assertDontSee('Confidential staff-only note');

    $this->get(route('account.support.attachments.download', $attachment->public_id))->assertNotFound();

    $attachment->forceFill(['scan_status' => SupportAttachmentScanStatus::Clean])->save();
    $response = $this->get(route('account.support.attachments.download', $attachment->public_id));
    $response->assertSuccessful()
        ->assertHeader('Cache-Control', 'max-age=0, no-store, private')
        ->assertStreamedContent('safe after review');
});

test('support conversations are private and customer replies reopen a closed ticket', function (): void {
    $owner = User::factory()->create();
    $otherCustomer = User::factory()->create();
    $ticket = SupportTicket::factory()->create([
        'user_id' => $owner->getKey(),
        'status' => SupportTicketStatus::Closed,
    ]);

    $this->actingAs($otherCustomer)
        ->get(route('account.support.show', $ticket->public_id))
        ->assertNotFound();

    $this->actingAs($owner)
        ->post(route('account.support.reply', $ticket->public_id), ['body' => 'The issue is still happening.'])
        ->assertRedirect();

    expect($ticket->fresh()->status)->toBe(SupportTicketStatus::Open)
        ->and($ticket->messages()->where('body', 'The issue is still happening.')->exists())->toBeTrue();
});

test('support uploads reject disallowed file types before creating a ticket', function (): void {
    $this->actingAs(User::factory()->create())
        ->from(route('account.support.index'))
        ->post(route('account.support.store'), [
            'subject' => 'Activation problem',
            'body' => 'Please check this file.',
            'attachments' => [UploadedFile::fake()->createWithContent('script.html', '<script>alert(1)</script>')],
        ])
        ->assertSessionHasErrors('attachments.0');

    $this->assertDatabaseCount('support_tickets', 0);
    $this->assertDatabaseCount('support_ticket_attachments', 0);
});
