<?php

namespace App\Models;

use App\Enums\AppSubmissionStatus;
use Database\Factories\AppSubmissionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AppSubmission extends Model
{
    /** @use HasFactory<AppSubmissionFactory> */
    use HasFactory;

    protected $fillable = [
        'submitter_name',
        'submitter_email',
        'app_name',
        'description',
        'suggested_price',
        'platform',
        'file_path',
        'status',
        'reviewed_by',
        'linked_author_id',
        'rejection_reason',
    ];

    protected function casts(): array
    {
        return [
            'suggested_price' => 'decimal:2',
            'status' => AppSubmissionStatus::class,
        ];
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function linkedAuthor(): BelongsTo
    {
        return $this->belongsTo(Author::class, 'linked_author_id');
    }
}
