<?php

namespace App\Models;

use Database\Factories\SupportTicketMessageFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class SupportTicketMessage extends Model
{
    /** @use HasFactory<SupportTicketMessageFactory> */
    use HasFactory;

    protected $fillable = [
        'public_id',
        'support_ticket_id',
        'user_id',
        'staff_id',
        'is_internal',
        'body',
    ];

    protected function casts(): array
    {
        return ['is_internal' => 'boolean'];
    }

    protected static function booted(): void
    {
        static::creating(function (SupportTicketMessage $message): void {
            $message->public_id ??= (string) Str::ulid();
        });
    }

    public function ticket(): BelongsTo
    {
        return $this->belongsTo(SupportTicket::class, 'support_ticket_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function staff(): BelongsTo
    {
        return $this->belongsTo(Staff::class);
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(SupportTicketAttachment::class, 'support_ticket_message_id');
    }
}
