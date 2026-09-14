<?php

namespace App\Policies;

use App\Models\Credential;
use App\Models\User;

class CredentialPolicy
{
    public function reveal(User $user, Credential $credential): bool
    {
        return $credential->entitlement()
            ->whereBelongsTo($user)
            ->where('status', 'active')
            ->exists();
    }
}
