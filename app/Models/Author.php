<?php

namespace App\Models;

use App\Enums\AuthorStatus;
use Database\Factories\AuthorFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class Author extends Model
{
    /** @use HasFactory<AuthorFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'user_id',
        'tagline',
        'bio',
        'avatar_path',
        'cover_path',
        'location',
        'website_url',
        'support_url',
        'social_links',
        'member_since',
        'status',
        'is_verified',
        'is_featured',
        'show_public_sales',
        'public_sales_count',
        'average_rating',
        'public_support_instructions',
        'is_public',
    ];

    protected $attributes = [
        'status' => AuthorStatus::Active,
        'is_public' => true,
        'is_verified' => false,
        'is_featured' => false,
        'show_public_sales' => true,
        'public_sales_count' => 0,
        'average_rating' => 0,
    ];

    protected function casts(): array
    {
        return [
            'status' => AuthorStatus::class,
            'is_public' => 'boolean',
            'is_verified' => 'boolean',
            'is_featured' => 'boolean',
            'show_public_sales' => 'boolean',
            'social_links' => 'array',
            'member_since' => 'date',
            'moderated_at' => 'datetime',
            'public_sales_count' => 'integer',
            'average_rating' => 'decimal:1',
        ];
    }

    public function scopePubliclyVisible(Builder $query): Builder
    {
        return $query
            ->where('status', AuthorStatus::Active)
            ->where('is_public', true);
    }

    public function avatarUrl(): ?string
    {
        return $this->mediaUrl($this->avatar_path);
    }

    public function coverUrl(): ?string
    {
        return $this->mediaUrl($this->cover_path);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function moderatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'moderated_by');
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    public function contributedProducts(): BelongsToMany
    {
        return $this->belongsToMany(Product::class)
            ->using(AuthorProduct::class)
            ->withPivot([
                'role',
                'is_primary',
                'is_publicly_displayed',
                'can_manage_product',
                'revenue_share_basis_points',
                'sort_order',
                'internal_notes',
            ])
            ->withTimestamps();
    }

    public function appSubmissions(): HasMany
    {
        return $this->hasMany(AppSubmission::class, 'linked_author_id');
    }

    public function earnings(): HasMany
    {
        return $this->hasMany(Earning::class);
    }

    private function mediaUrl(?string $path): ?string
    {
        if (blank($path)) {
            return null;
        }

        if (Str::startsWith($path, ['http://', 'https://'])) {
            return $path;
        }

        if (Str::startsWith($path, ['/images/', 'images/'])) {
            return asset(ltrim($path, '/'));
        }

        return Storage::disk('public')->url($path);
    }
}
