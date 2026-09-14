<?php

namespace App\Policies;

use App\Models\Staff;
use App\Models\SupportTicket;
use App\Models\User;

class SupportTicketPolicy
{
    public function viewAny(User|Staff $actor): bool
    {
        return $actor instanceof Staff && $actor->admin;
    }

    public function create(User|Staff $actor): bool
    {
        return $actor instanceof User;
    }

    public function view(User|Staff $actor, SupportTicket $supportTicket): bool
    {
        if ($actor instanceof Staff) {
            return $actor->admin;
        }

        return (int) $supportTicket->user_id === (int) $actor->getKey();
    }

    public function reply(User|Staff $actor, SupportTicket $supportTicket): bool
    {
        if ($actor instanceof Staff) {
            return $actor->admin;
        }

        return (int) $supportTicket->user_id === (int) $actor->getKey();
    }

    public function update(User|Staff $actor, SupportTicket $supportTicket): bool
    {
        return $actor instanceof Staff && $actor->admin;
    }
}
