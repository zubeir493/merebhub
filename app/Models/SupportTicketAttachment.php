<?php

namespace App\Models;

use App\Domain\Support\Enums\SupportAttachmentScanStatus;
use Database\Factories\SupportTicketAttachmentFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class SupportTicketAttachment extends Model
{
    /** @use HasFactory<SupportTicketAttachmentFactory> */
    use HasFactory;

    protected $fillable = [
        'public_id',
        'support_ticket_message_id',
        'disk',
        'path',
        'original_name',
        'mime_type',
        'size',
        'checksum',
        'scan_status',
        'scanned_at',
    ];

    protected $hidden = [
        'disk',
        'path',
        'checksum',
    ];

    protected $attributes = [
        'scan_status' => SupportAttachmentScanStatus::Pending->value,
    ];

    protected function casts(): array
    {
        return [
            'scan_status' => SupportAttachmentScanStatus::class,
            'scanned_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (SupportTicketAttachment $attachment): void {
            $attachment->public_id ??= (string) Str::ulid();
        });
    }

    public function getRouteKeyName(): string
    {
        return 'public_id';
    }

    public function message(): BelongsTo
    {
        return $this->belongsTo(SupportTicketMessage::class, 'support_ticket_message_id');
    }
}
