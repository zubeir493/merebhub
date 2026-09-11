<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProviderMirror extends Model
{
    protected $fillable = [
        'fulfillment_unit_id',
        'provider',
        'operation',
        'idempotency_key',
        'external_id',
        'status',
        'response',
    ];

    protected function casts(): array
    {
        return ['response' => 'array'];
    }

    public function fulfillmentUnit(): BelongsTo
    {
        return $this->belongsTo(FulfillmentUnit::class);
    }
}
