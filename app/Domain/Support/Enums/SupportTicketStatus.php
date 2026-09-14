<?php

namespace App\Domain\Support\Enums;

enum SupportTicketStatus: string
{
    case Open = 'open';
    case WaitingOnCustomer = 'waiting_on_customer';
    case Resolved = 'resolved';
    case Closed = 'closed';
}
