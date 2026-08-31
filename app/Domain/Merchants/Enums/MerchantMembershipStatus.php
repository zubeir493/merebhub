<?php

namespace App\Domain\Merchants\Enums;

enum MerchantMembershipStatus: string
{
    case Invited = 'invited';
    case Active = 'active';
    case Suspended = 'suspended';
}
