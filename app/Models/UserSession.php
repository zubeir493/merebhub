<?php

namespace App\Models;

use Database\Factories\UserSessionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class UserSession extends Model
{
    /** @use HasFactory<UserSessionFactory> */
    use HasFactory;

    protected $fillable = [
        'public_id',
        'user_id',
        'session_hash',
        'session_id',
        'device_label',
        'ip_hash',
        'last_active_at',
        'revoked_at',
    ];

    protected $hidden = [
        'session_hash',
        'session_id',
        'ip_hash',
    ];

    protected function casts(): array
    {
        return [
            'session_id' => 'encrypted',
            'last_active_at' => 'datetime',
            'revoked_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (UserSession $session): void {
            $session->public_id ??= (string) Str::ulid();
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

    public function isCurrent(string $sessionId): bool
    {
        return hash_equals($this->session_hash, hash('sha256', $sessionId));
    }
}
