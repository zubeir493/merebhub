<?php

namespace App\Domain\Support\Actions;

use App\Domain\Shared\Actions\RecordAuditEventAction;
use App\Domain\Support\Enums\SupportTicketStatus;
use App\Models\Staff;
use App\Models\SupportTicket;
use Illuminate\Support\Facades\DB;

class UpdateSupportTicketAction
{
    public function __construct(private readonly RecordAuditEventAction $audit) {}

    /**
     * @param  array{status: string, priority: string, assigned_staff_id: int|null}  $attributes
     */
    public function handle(SupportTicket $ticket, Staff $staff, array $attributes): SupportTicket
    {
        return DB::transaction(function () use ($ticket, $staff, $attributes): SupportTicket {
            $lockedTicket = SupportTicket::query()
                ->whereKey($ticket->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            $previous = [
                'status' => $lockedTicket->status->value,
                'priority' => $lockedTicket->priority->value,
                'assigned_staff_id' => $lockedTicket->assigned_staff_id,
            ];
            $status = SupportTicketStatus::from($attributes['status']);

            $lockedTicket->forceFill([
                'status' => $status,
                'priority' => $attributes['priority'],
                'assigned_staff_id' => $attributes['assigned_staff_id'],
                'resolved_at' => $status === SupportTicketStatus::Resolved
                    ? ($lockedTicket->resolved_at ?? now())
                    : null,
                'closed_at' => $status === SupportTicketStatus::Closed
                    ? ($lockedTicket->closed_at ?? now())
                    : null,
            ])->save();

            $this->audit->handle(
                'support.ticket.updated',
                actor: $staff,
                subject: $lockedTicket,
                metadata: [
                    'ticket_id' => $lockedTicket->public_id,
                    'previous' => $previous,
                    'current' => [
                        'status' => $status->value,
                        'priority' => $lockedTicket->priority->value,
                        'assigned_staff_id' => $lockedTicket->assigned_staff_id,
                    ],
                ],
            );

            return $lockedTicket->refresh();
        });
    }
}
