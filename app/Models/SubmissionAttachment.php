<?php

namespace App\Models;

use Database\Factories\SubmissionAttachmentFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SubmissionAttachment extends Model
{
    /** @use HasFactory<SubmissionAttachmentFactory> */
    use HasFactory;

    protected $fillable = [
        'app_submission_id',
        'path',
        'original_name',
        'mime_type',
        'size',
    ];

    protected function casts(): array
    {
        return ['size' => 'integer'];
    }

    public function submission(): BelongsTo
    {
        return $this->belongsTo(AppSubmission::class, 'app_submission_id');
    }
}
