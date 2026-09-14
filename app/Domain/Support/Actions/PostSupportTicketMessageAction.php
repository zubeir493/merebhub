<?php

namespace App\Domain\Support\Actions;

use App\Domain\Shared\Actions\RecordAuditEventAction;
use App\Domain\Support\Enums\SupportTicketStatus;
use App\Models\Staff;
use App\Models\SupportTicket;
use App\Models\SupportTicketMessage;
use App\Models\User;
use App\Notifications\SupportTicketCreatedNotification;
use App\Notifications\SupportTicketUpdatedNotification;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Throwable;

class PostSupportTicketMessageAction
{
    public function __construct(
        private readonly StoreSupportTicketAttachmentsAction $storeAttachments,
        private readonly RecordAuditEventAction $audit,
    ) {}

    /**
     * @param  list<UploadedFile>  $files
     */
    public function handle(
        SupportTicket $ticket,
        User|Staff $author,
        string $body,
        bool $isInternal,
        array $files = [],
    ): SupportTicketMessage {
        if ($author instanceof User && $isInternal) {
            throw ValidationException::withMessages(['message' => 'Customers cannot add internal notes.']);
        }

        $storedPaths = [];

        try {
            $message = DB::transaction(function () use ($ticket, $author, $body, $isInternal, $files, &$storedPaths): SupportTicketMessage {
                $lockedTicket = SupportTicket::query()
                    ->whereKey($ticket->getKey())
                    ->lockForUpdate()
                    ->first();

                if (! $lockedTicket) {
                    throw (new ModelNotFoundException)->setModel(SupportTicket::class, [$ticket->getKey()]);
                }

                $message = $lockedTicket->messages()->create([
                    'user_id' => $author instanceof User ? $author->getKey() : null,
                    'staff_id' => $author instanceof Staff ? $author->getKey() : null,
                    'is_internal' => $isInternal,
                    'body' => $body,
                ]);
                $stored = $this->storeAttachments->handle($message, $files);
                $storedPaths = array_map(fn ($attachment): string => $attachment->path, $stored);

                if ($author instanceof User) {
                    $lockedTicket->forceFill([
                        'status' => SupportTicketStatus::Open,
                        'resolved_at' => null,
                        'closed_at' => null,
                        'last_message_at' => now(),
                    ])->save();
                } else {
                    $lockedTicket->forceFill([
                        'status' => $isInternal
                            ? $lockedTicket->status
                            : SupportTicketStatus::WaitingOnCustomer,
                        'assigned_staff_id' => $lockedTicket->assigned_staff_id ?? $author->getKey(),
                        'last_message_at' => now(),
                    ])->save();
                }

                $this->audit->handle(
                    'support.ticket.message_created',
                    actor: $author,
                    subject: $lockedTicket,
                    metadata: [
                        'ticket_id' => $lockedTicket->public_id,
                        'internal' => $isInternal,
                        'attachment_count' => count($stored),
                    ],
                );

                return $message;
            });
        } catch (Throwable $exception) {
            Storage::disk((string) config('support.attachments_disk', 'private'))->delete($storedPaths);

            throw $exception;
        }

        if ($author instanceof User) {
            $inboxEmail = config('support.inbox_email');

            if (is_string($inboxEmail) && filled($inboxEmail)) {
                Notification::route('mail', $inboxEmail)->notify(
                    (new SupportTicketCreatedNotification($ticket->public_id, isNew: false))->afterCommit(),
                );
            }
        } elseif (! $isInternal) {
            $ticket->loadMissing('user');
            $ticket->user->notify(
                (new SupportTicketUpdatedNotification($ticket->public_id))->afterCommit(),
            );
        }

        return $message;
    }
}
