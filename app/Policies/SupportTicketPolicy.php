<?php

namespace App\Policies;

use App\Models\SupportTicket;
use App\Models\User;
use Lunar\Core\Models\Staff as CoreStaff;

class SupportTicketPolicy
{
    public function viewAny(User|CoreStaff $actor): bool
    {
        return $actor instanceof CoreStaff && $actor->admin;
    }

    public function create(User|CoreStaff $actor): bool
    {
        return $actor instanceof User;
    }

    public function view(User|CoreStaff $actor, SupportTicket $supportTicket): bool
    {
        if ($actor instanceof CoreStaff) {
            return $actor->admin;
        }

        return (int) $supportTicket->user_id === (int) $actor->getKey();
    }

    public function reply(User|CoreStaff $actor, SupportTicket $supportTicket): bool
    {
        if ($actor instanceof CoreStaff) {
            return $actor->admin;
        }

        return (int) $supportTicket->user_id === (int) $actor->getKey();
    }

    public function update(User|CoreStaff $actor, SupportTicket $supportTicket): bool
    {
        return $actor instanceof CoreStaff && $actor->admin;
    }
}
