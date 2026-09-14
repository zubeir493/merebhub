<?php

namespace App\Policies;

use App\Domain\Support\Enums\SupportAttachmentScanStatus;
use App\Models\Staff;
use App\Models\SupportTicketAttachment;
use App\Models\User;

class SupportTicketAttachmentPolicy
{
    public function download(User|Staff $actor, SupportTicketAttachment $attachment): bool
    {
        if ($actor instanceof Staff) {
            return $actor->admin && $attachment->scan_status !== SupportAttachmentScanStatus::Rejected;
        }

        return $attachment->scan_status === SupportAttachmentScanStatus::Clean
            && (int) $attachment->message->ticket->user_id === (int) $actor->getKey();
    }
}
