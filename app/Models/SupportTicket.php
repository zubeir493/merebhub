<?php

namespace App\Models;

use App\Domain\Support\Enums\SupportTicketPriority;
use App\Domain\Support\Enums\SupportTicketStatus;
use Database\Factories\SupportTicketFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Str;

class SupportTicket extends Model
{
    /** @use HasFactory<SupportTicketFactory> */
    use HasFactory;

    protected $fillable = [
        'public_id',
        'user_id',
        'assigned_staff_id',
        'subject',
        'status',
        'priority',
        'last_message_at',
        'resolved_at',
        'closed_at',
    ];

    protected $attributes = [
        'status' => SupportTicketStatus::Open->value,
        'priority' => SupportTicketPriority::Normal->value,
    ];

    protected function casts(): array
    {
        return [
            'status' => SupportTicketStatus::class,
            'priority' => SupportTicketPriority::class,
            'last_message_at' => 'datetime',
            'resolved_at' => 'datetime',
            'closed_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (SupportTicket $ticket): void {
            $ticket->public_id ??= (string) Str::ulid();
        });
    }

    public function getRouteKeyName(): string
    {
        return 'public_id';
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'assigned_staff_id');
    }

    public function messages(): HasMany
    {
        return $this->hasMany(SupportTicketMessage::class);
    }

    public function latestMessage(): HasOne
    {
        return $this->hasOne(SupportTicketMessage::class)->latestOfMany();
    }

    public function attachments(): HasManyThrough
    {
        return $this->hasManyThrough(SupportTicketAttachment::class, SupportTicketMessage::class);
    }
}
