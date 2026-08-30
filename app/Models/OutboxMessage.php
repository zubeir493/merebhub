<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class OutboxMessage extends Model
{
    protected $fillable = [
        'public_id',
        'event',
        'aggregate_type',
        'aggregate_id',
        'payload',
        'attempts',
        'available_at',
        'published_at',
        'last_error',
    ];

    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'available_at' => 'datetime',
            'published_at' => 'datetime',
        ];
    }

    public function aggregate(): MorphTo
    {
        return $this->morphTo();
    }
}
