<?php

namespace App\Models;

use Database\Factories\DownloadFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Download extends Model
{
    /** @use HasFactory<DownloadFactory> */
    use HasFactory;

    protected $fillable = [
        'user_id',
        'order_item_id',
        'license_id',
        'app_version_id',
        'ip_address',
        'user_agent',
        'downloaded_at',
        'revoked_at',
    ];

    protected function casts(): array
    {
        return [
            'downloaded_at' => 'datetime',
            'revoked_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function orderItem(): BelongsTo
    {
        return $this->belongsTo(OrderItem::class);
    }

    public function license(): BelongsTo
    {
        return $this->belongsTo(License::class);
    }

    public function appVersion(): BelongsTo
    {
        return $this->belongsTo(AppVersion::class);
    }
}
