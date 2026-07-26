<?php

namespace App\Models;

use App\Enums\AppSubmissionStatus;
use App\Enums\BillingInterval;
use App\Enums\BillingModel;
use App\Enums\FulfillmentType;
use Database\Factories\AppSubmissionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

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
        'category',
        'demo_url',
        'fulfillment_type',
        'payment_model',
        'billing_interval',
        'trial_days',
        'status',
        'reviewed_by',
        'linked_author_id',
        'rejection_reason',
    ];

    protected function casts(): array
    {
        return [
            'suggested_price' => 'decimal:2',
            'fulfillment_type' => FulfillmentType::class,
            'payment_model' => BillingModel::class,
            'billing_interval' => BillingInterval::class,
            'trial_days' => 'integer',
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

    public function attachments(): HasMany
    {
        return $this->hasMany(SubmissionAttachment::class);
    }
}
