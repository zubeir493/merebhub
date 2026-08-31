<?php

namespace App\Domain\Merchants\Enums;

enum MerchantType: string
{
    case LocalDeveloper = 'local_developer';
    case GlobalPartner = 'global_partner';
}
