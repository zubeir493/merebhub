<?php

namespace App\Policies;

use App\Models\Merchant;
use App\Models\User;
use Lunar\Core\Models\Staff as CoreStaff;

class MerchantPolicy
{
    public function viewAny(User|CoreStaff $actor): bool
    {
        return $actor instanceof CoreStaff ? $actor->admin : $actor->approvedMerchants()->exists();
    }

    public function view(User|CoreStaff $actor, Merchant $merchant): bool
    {
        if ($actor instanceof CoreStaff) {
            return $actor->admin;
        }

        return $actor->approvedMerchants()->whereKey($merchant->getKey())->exists();
    }

    public function create(User|CoreStaff $actor): bool
    {
        return $actor instanceof CoreStaff ? $actor->admin : false;
    }

    public function update(User|CoreStaff $actor, Merchant $merchant): bool
    {
        return $this->view($actor, $merchant);
    }

    public function delete(User|CoreStaff $actor, Merchant $merchant): bool
    {
        return $actor instanceof CoreStaff ? $actor->admin : false;
    }
}
