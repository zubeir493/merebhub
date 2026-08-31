<?php

namespace App\Policies;

use App\Models\Product;
use App\Models\User;
use Lunar\Core\Models\Staff as CoreStaff;

class ProductPolicy
{
    public function viewAny(User|CoreStaff $actor): bool
    {
        return $actor instanceof CoreStaff ? $actor->admin : $actor->approvedMerchants()->exists();
    }

    public function view(User|CoreStaff $actor, Product $product): bool
    {
        return $this->canAccess($actor, $product);
    }

    public function create(User|CoreStaff $actor): bool
    {
        return $actor instanceof CoreStaff ? $actor->admin : $actor->approvedMerchants()->exists();
    }

    public function update(User|CoreStaff $actor, Product $product): bool
    {
        return $this->canAccess($actor, $product);
    }

    public function delete(User|CoreStaff $actor, Product $product): bool
    {
        return $this->canAccess($actor, $product);
    }

    private function canAccess(User|CoreStaff $actor, Product $product): bool
    {
        if ($actor instanceof CoreStaff) {
            return $actor->admin;
        }

        return $product->merchant_id !== null
            && $actor->approvedMerchants()->whereKey($product->merchant_id)->exists();
    }
}
