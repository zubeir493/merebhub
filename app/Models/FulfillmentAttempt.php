<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FulfillmentAttempt extends Model
{
    protected $fillable = [
        'fulfillment_unit_id',
        'attempt_number',
        'status',
        'error_class',
        'error_message',
        'provider_request_id',
        'meta',
        'started_at',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'meta' => 'array',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    public function fulfillmentUnit(): BelongsTo
    {
        return $this->belongsTo(FulfillmentUnit::class);
    }
}
