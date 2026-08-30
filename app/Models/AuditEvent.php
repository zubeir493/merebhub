<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class AuditEvent extends Model
{
    protected $fillable = [
        'public_id',
        'event',
        'actor_type',
        'actor_id',
        'subject_type',
        'subject_id',
        'metadata',
        'ip_hash',
        'user_agent_summary',
    ];

    protected $hidden = [
        'ip_hash',
        'user_agent_summary',
    ];

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
        ];
    }

    public function actor(): MorphTo
    {
        return $this->morphTo();
    }

    public function subject(): MorphTo
    {
        return $this->morphTo();
    }
}
