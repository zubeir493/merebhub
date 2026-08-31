<?php

namespace App\Domain\Merchants\Enums;

enum MerchantMembershipRole: string
{
    case Owner = 'owner';
    case Manager = 'manager';
    case Catalog = 'catalog';
    case Analyst = 'analyst';
}
