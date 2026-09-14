<?php

namespace App\Domain\Support\Actions;

use App\Domain\Shared\Actions\RecordAuditEventAction;
use App\Domain\Support\Enums\SupportTicketStatus;
use App\Models\SupportTicket;
use App\Models\User;
use App\Notifications\SupportTicketCreatedNotification;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Throwable;

class CreateSupportTicketAction
{
    public function __construct(
        private readonly StoreSupportTicketAttachmentsAction $storeAttachments,
        private readonly RecordAuditEventAction $audit,
    ) {}

    /**
     * @param  array{subject: string, body: string, attachments?: list<UploadedFile>}  $data
     */
    public function handle(User $user, array $data): SupportTicket
    {
        $storedPaths = [];
        $attachments = $data['attachments'] ?? [];

        try {
            $ticket = DB::transaction(function () use ($user, $data, $attachments, &$storedPaths): SupportTicket {
                $ticket = $user->supportTickets()->create([
                    'subject' => $data['subject'],
                    'status' => SupportTicketStatus::Open,
                    'last_message_at' => now(),
                ]);
                $message = $ticket->messages()->create([
                    'user_id' => $user->getKey(),
                    'body' => $data['body'],
                ]);

                $stored = $this->storeAttachments->handle($message, $attachments);
                $storedPaths = array_map(fn ($attachment): string => $attachment->path, $stored);

                $this->audit->handle(
                    'support.ticket.created',
                    actor: $user,
                    subject: $ticket,
                    metadata: [
                        'ticket_id' => $ticket->public_id,
                        'attachment_count' => count($stored),
                    ],
                );

                return $ticket;
            });
        } catch (Throwable $exception) {
            Storage::disk((string) config('support.attachments_disk', 'private'))->delete($storedPaths);

            throw $exception;
        }

        $inboxEmail = config('support.inbox_email');

        if (is_string($inboxEmail) && filled($inboxEmail)) {
            Notification::route('mail', $inboxEmail)->notify(
                (new SupportTicketCreatedNotification($ticket->public_id))->afterCommit(),
            );
        }

        return $ticket;
    }
}
