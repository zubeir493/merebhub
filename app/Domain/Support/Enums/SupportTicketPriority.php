<?php

namespace App\Domain\Support\Enums;

enum SupportTicketPriority: string
{
    case Normal = 'normal';
    case High = 'high';
    case Urgent = 'urgent';
}
