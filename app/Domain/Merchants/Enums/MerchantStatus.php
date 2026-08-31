<?php

namespace App\Domain\Merchants\Enums;

enum MerchantStatus: string
{
    case Pending = 'pending';
    case Approved = 'approved';
    case Suspended = 'suspended';
    case Rejected = 'rejected';
}
