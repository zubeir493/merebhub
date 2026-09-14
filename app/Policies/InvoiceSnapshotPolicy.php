<?php

namespace App\Policies;

use App\Models\InvoiceSnapshot;
use App\Models\User;

class InvoiceSnapshotPolicy
{
    public function view(User $user, InvoiceSnapshot $invoiceSnapshot): bool
    {
        return (int) $invoiceSnapshot->user_id === (int) $user->getKey();
    }
}
