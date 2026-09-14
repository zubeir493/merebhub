<?php

namespace App\Domain\Support\Actions;

use App\Domain\Shared\Actions\RecordAuditEventAction;
use App\Domain\Support\Enums\SupportAttachmentScanStatus;
use App\Models\Staff;
use App\Models\SupportTicketAttachment;
use Illuminate\Support\Facades\DB;

class ReviewSupportTicketAttachmentAction
{
    public function __construct(private readonly RecordAuditEventAction $audit) {}

    public function handle(
        SupportTicketAttachment $attachment,
        Staff $staff,
        SupportAttachmentScanStatus $status,
    ): SupportTicketAttachment {
        return DB::transaction(function () use ($attachment, $staff, $status): SupportTicketAttachment {
            $lockedAttachment = SupportTicketAttachment::query()
                ->whereKey($attachment->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            $lockedAttachment->forceFill([
                'scan_status' => $status,
                'scanned_at' => now(),
            ])->save();

            $this->audit->handle(
                'support.attachment.reviewed',
                actor: $staff,
                subject: $lockedAttachment,
                metadata: [
                    'attachment_id' => $lockedAttachment->public_id,
                    'scan_status' => $status->value,
                ],
            );

            return $lockedAttachment->refresh();
        });
    }
}
