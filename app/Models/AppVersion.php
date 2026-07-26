<?php

namespace App\Models;

use App\Enums\MalwareScanStatus;
use App\Enums\ReleaseStatus;
use Database\Factories\AppVersionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AppVersion extends Model
{
    /** @use HasFactory<AppVersionFactory> */
    use HasFactory;

    protected $fillable = [
        'product_id',
        'version_number',
        'file_path',
        'file_size',
        'changelog',
        'sha256_checksum',
        'release_notes',
        'release_status',
        'published_at',
        'minimum_supported_version',
        'scan_status',
        'scanned_at',
    ];

    protected function casts(): array
    {
        return [
            'file_size' => 'integer',
            'release_status' => ReleaseStatus::class,
            'published_at' => 'datetime',
            'scan_status' => MalwareScanStatus::class,
            'scanned_at' => 'datetime',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function downloads(): HasMany
    {
        return $this->hasMany(Download::class);
    }
}
