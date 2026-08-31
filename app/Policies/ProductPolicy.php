<?php

namespace App\Policies;

use App\Models\Product;
use App\Models\User;

class ProductPolicy
{
    public function update(User $user, Product $product): bool
    {
        return $product->merchant_id !== null
            && $user->activeMerchants()->whereKey($product->merchant_id)->exists();
    }
}
