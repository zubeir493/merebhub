<?php

namespace Database\Factories;

use App\Domain\Support\Enums\SupportAttachmentScanStatus;
use App\Models\SupportTicketAttachment;
use App\Models\SupportTicketMessage;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<SupportTicketAttachment>
 */
class SupportTicketAttachmentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'support_ticket_message_id' => SupportTicketMessage::factory(),
            'disk' => 'private',
            'path' => 'support/factory/'.Str::uuid().'.txt',
            'original_name' => 'support-note.txt',
            'mime_type' => 'text/plain',
            'size' => 128,
            'checksum' => hash('sha256', Str::random(32)),
            'scan_status' => SupportAttachmentScanStatus::Pending,
            'scanned_at' => null,
        ];
    }
}
