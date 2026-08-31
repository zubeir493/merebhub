<?php

namespace App\Policies;

use App\Models\Merchant;
use App\Models\User;

class MerchantPolicy
{
    public function view(User $user, Merchant $merchant): bool
    {
        return $user->approvedMerchants()->whereKey($merchant->getKey())->exists();
    }

    public function update(User $user, Merchant $merchant): bool
    {
        return $this->view($user, $merchant);
    }
}
