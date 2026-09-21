<?php

namespace App\Models;

use App\Domain\Fulfillment\Enums\AssetScanStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class DownloadableAsset extends Model
{
    protected $fillable = [
        'public_id',
        'product_id',
        'disk',
        'path',
        'filename',
        'checksum',
        'size',
        'scan_status',
        'scanned_at',
        'meta',
    ];

    protected $attributes = [
        'scan_status' => AssetScanStatus::Pending->value,
    ];

    protected function casts(): array
    {
        return [
            'scan_status' => AssetScanStatus::class,
            'scanned_at' => 'datetime',
            'meta' => 'array',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (DownloadableAsset $asset): void {
            $asset->public_id ??= (string) Str::ulid();
        });
    }

    public function getRouteKeyName(): string
    {
        return 'public_id';
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function hasCleanScan(): bool
    {
        return $this->scan_status === AssetScanStatus::Clean;
    }
}
