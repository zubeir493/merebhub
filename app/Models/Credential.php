<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class Credential extends Model
{
    protected $fillable = [
        'public_id',
        'entitlement_id',
        'type',
        'secret',
        'revealed_at',
    ];

    protected $hidden = ['secret'];

    protected function casts(): array
    {
        return [
            'secret' => 'encrypted',
            'revealed_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Credential $credential): void {
            $credential->public_id ??= (string) Str::ulid();
        });
    }

    public function entitlement(): BelongsTo
    {
        return $this->belongsTo(Entitlement::class);
    }
}
