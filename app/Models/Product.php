<?php

namespace App\Models;

use App\Enums\BillingInterval;
use App\Enums\BillingModel;
use App\Enums\FulfillmentType;
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
        'fulfillment_type',
        'billing_model',
        'billing_interval',
        'trial_days',
        'app_url',
        'status',
    ];

    protected $attributes = [
        'rating' => 0,
        'ratings_count' => 0,
        'weekly_sales' => 0,
        'is_featured' => false,
        'fulfillment_type' => FulfillmentType::LicenseKey,
        'billing_model' => BillingModel::OneTime,
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
            'fulfillment_type' => FulfillmentType::class,
            'billing_model' => BillingModel::class,
            'billing_interval' => BillingInterval::class,
            'trial_days' => 'integer',
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

    public function authors(): BelongsToMany
    {
        return $this->belongsToMany(Author::class)
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
            ->withTimestamps()
            ->orderByPivot('sort_order');
    }

    public function publicContributors(): BelongsToMany
    {
        return $this->authors()
            ->wherePivot('is_publicly_displayed', true)
            ->where('authors.is_public', true);
    }

    public function platforms(): BelongsToMany
    {
        return $this->belongsToMany(Platform::class)->withTimestamps();
    }

    public function versions(): HasMany
    {
        return $this->hasMany(AppVersion::class);
    }

    public function plans(): HasMany
    {
        return $this->hasMany(ProductPlan::class)->orderBy('sort_order');
    }

    public function activePlans(): HasMany
    {
        return $this->plans()->where('is_active', true);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function licenses(): HasMany
    {
        return $this->hasMany(License::class);
    }

    public function cartItems(): HasMany
    {
        return $this->hasMany(CartItem::class);
    }

    public function wishlistItems(): HasMany
    {
        return $this->hasMany(WishlistItem::class);
    }

    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }
}
