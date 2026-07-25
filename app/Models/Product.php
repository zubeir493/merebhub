<?php

namespace App\Models;

use App\Enums\ProductStatus;
use Database\Factories\ProductFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class Product extends Model
{
    /** @use HasFactory<ProductFactory> */
    use HasFactory;

    protected $fillable = [
        'wc_product_id',
        'author_id',
        'category',
        'name',
        'slug',
        'tagline',
        'description',
        'price',
        'compare_at_price',
        'icon_path',
        'cover_path',
        'rating',
        'ratings_count',
        'weekly_sales',
        'is_featured',
        'keygen_policy_id',
        'status',
    ];

    protected $attributes = [
        'rating' => 0,
        'ratings_count' => 0,
        'weekly_sales' => 0,
        'is_featured' => false,
        'status' => ProductStatus::Draft,
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'compare_at_price' => 'decimal:2',
            'rating' => 'decimal:1',
            'ratings_count' => 'integer',
            'weekly_sales' => 'integer',
            'is_featured' => 'boolean',
            'status' => ProductStatus::class,
        ];
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', ProductStatus::Published);
    }

    public function coverUrl(): ?string
    {
        if (blank($this->cover_path)) {
            return null;
        }

        if (Str::startsWith($this->cover_path, ['http://', 'https://'])) {
            return $this->cover_path;
        }

        if (Str::startsWith($this->cover_path, ['/images/', 'images/'])) {
            return asset(ltrim($this->cover_path, '/'));
        }

        return Storage::disk('public')->url($this->cover_path);
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(Author::class);
    }

    public function platforms(): BelongsToMany
    {
        return $this->belongsToMany(Platform::class)->withTimestamps();
    }

    public function versions(): HasMany
    {
        return $this->hasMany(AppVersion::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function licenses(): HasMany
    {
        return $this->hasMany(License::class);
    }
}
