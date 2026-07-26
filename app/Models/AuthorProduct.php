<?php

namespace App\Models;

use App\Enums\AuthorRole;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Relations\Pivot;

#[Table(incrementing: true)]
class AuthorProduct extends Pivot
{
    protected $fillable = [
        'author_id',
        'product_id',
        'role',
        'is_primary',
        'is_publicly_displayed',
        'can_manage_product',
        'revenue_share_basis_points',
        'sort_order',
        'internal_notes',
    ];

    protected function casts(): array
    {
        return [
            'role' => AuthorRole::class,
            'is_primary' => 'boolean',
            'is_publicly_displayed' => 'boolean',
            'can_manage_product' => 'boolean',
            'revenue_share_basis_points' => 'integer',
            'sort_order' => 'integer',
        ];
    }
}
