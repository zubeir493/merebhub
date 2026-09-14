<?php

namespace App\Policies;

use App\Models\User;
use App\Models\UserSession;

class UserSessionPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function delete(User $user, UserSession $userSession): bool
    {
        return (int) $userSession->user_id === (int) $user->getKey();
    }
}
