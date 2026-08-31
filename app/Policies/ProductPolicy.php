<?php

namespace App\Policies;

use App\Models\Product;
use App\Models\Staff;
use App\Models\User;

class ProductPolicy
{
    public function viewAny(User|Staff $actor): bool
    {
        return $actor instanceof Staff ? $actor->admin : $actor->approvedMerchants()->exists();
    }

    public function view(User|Staff $actor, Product $product): bool
    {
        return $this->canAccess($actor, $product);
    }

    public function create(User|Staff $actor): bool
    {
        return $actor instanceof Staff ? $actor->admin : $actor->approvedMerchants()->exists();
    }

    public function update(User|Staff $actor, Product $product): bool
    {
        return $this->canAccess($actor, $product);
    }

    public function delete(User|Staff $actor, Product $product): bool
    {
        return $this->canAccess($actor, $product);
    }

    private function canAccess(User|Staff $actor, Product $product): bool
    {
        if ($actor instanceof Staff) {
            return $actor->admin;
        }

        return $product->merchant_id !== null
            && $actor->approvedMerchants()->whereKey($product->merchant_id)->exists();
    }
}
