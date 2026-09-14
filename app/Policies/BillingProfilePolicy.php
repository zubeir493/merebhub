<?php

namespace App\Policies;

use App\Models\BillingProfile;
use App\Models\User;

class BillingProfilePolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function view(User $user, BillingProfile $billingProfile): bool
    {
        return (int) $billingProfile->user_id === (int) $user->getKey();
    }

    public function update(User $user, BillingProfile $billingProfile): bool
    {
        return (int) $billingProfile->user_id === (int) $user->getKey();
    }
}
