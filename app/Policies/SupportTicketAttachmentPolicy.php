<?php

namespace App\Policies;

use App\Domain\Support\Enums\SupportAttachmentScanStatus;
use App\Models\SupportTicketAttachment;
use App\Models\User;
use Lunar\Core\Models\Staff as CoreStaff;

class SupportTicketAttachmentPolicy
{
    public function download(User|CoreStaff $actor, SupportTicketAttachment $attachment): bool
    {
        if ($actor instanceof CoreStaff) {
            return $actor->admin && $attachment->scan_status !== SupportAttachmentScanStatus::Rejected;
        }

        return $attachment->scan_status === SupportAttachmentScanStatus::Clean
            && (int) $attachment->message->ticket->user_id === (int) $actor->getKey();
    }
}
