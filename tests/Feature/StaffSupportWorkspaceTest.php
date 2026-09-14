<?php

use App\Domain\Support\Enums\SupportTicketPriority;
use App\Domain\Support\Enums\SupportTicketStatus;
use App\Models\Staff;
use App\Models\SupportTicket;
use App\Models\User;
use App\Notifications\SupportTicketUpdatedNotification;
use Illuminate\Support\Facades\Notification;

test('admin staff can triage tickets and add private internal notes', function (): void {
    Notification::fake();
    $staff = Staff::factory()->create(['admin' => true]);
    $assignee = Staff::factory()->create(['admin' => true]);
    $customer = User::factory()->create();
    $ticket = SupportTicket::factory()->create([
        'user_id' => $customer->getKey(),
        'status' => SupportTicketStatus::Open,
    ]);

    $this->actingAs($staff, 'staff')
        ->get(route('staff.support.index'))
        ->assertSuccessful()
        ->assertSee($ticket->subject);

    $this->actingAs($staff, 'staff')
        ->get(route('filament.lunar.resources.support-tickets.index'))
        ->assertSuccessful()
        ->assertSee($ticket->subject);

    $this->actingAs($staff, 'staff')
        ->patch(route('staff.support.update', $ticket->public_id), [
            'status' => SupportTicketStatus::WaitingOnCustomer->value,
            'priority' => SupportTicketPriority::Urgent->value,
            'assigned_staff_id' => $assignee->getKey(),
        ])
        ->assertRedirect();

    $ticket->refresh();
    expect($ticket->status)->toBe(SupportTicketStatus::WaitingOnCustomer)
        ->and($ticket->priority)->toBe(SupportTicketPriority::Urgent)
        ->and($ticket->assigned_staff_id)->toBe($assignee->getKey());

    $this->actingAs($staff, 'staff')
        ->post(route('staff.support.reply', $ticket->public_id), [
            'body' => 'Review the attached diagnostic before replying.',
            'is_internal' => true,
        ])
        ->assertRedirect();

    $this->actingAs($customer)
        ->get(route('account.support.show', $ticket->public_id))
        ->assertSuccessful()
        ->assertDontSee('Review the attached diagnostic before replying.');

    Notification::assertNothingSent();
});

test('only admin staff can use the support workspace', function (): void {
    $nonAdmin = Staff::factory()->create(['admin' => false]);
    $this->actingAs($nonAdmin, 'staff')
        ->get(route('staff.support.index'))
        ->assertForbidden();

    $this->actingAs(User::factory()->create())
        ->get(route('staff.support.index'))
        ->assertForbidden();
});

test('staff replies notify customers without including private ticket content', function (): void {
    Notification::fake();
    $staff = Staff::factory()->create(['admin' => true]);
    $customer = User::factory()->create();
    $ticket = SupportTicket::factory()->create(['user_id' => $customer->getKey()]);

    $this->actingAs($staff, 'staff')
        ->post(route('staff.support.reply', $ticket->public_id), ['body' => 'Your license is now active.'])
        ->assertRedirect();

    Notification::assertSentTo($customer, SupportTicketUpdatedNotification::class);
});
