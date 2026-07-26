<?php

namespace App\Enums;

enum BillingInterval: string
{
    case Weekly = 'weekly';
    case Monthly = 'monthly';
    case Quarterly = 'quarterly';
    case Biannual = 'biannual';
    case Yearly = 'yearly';

    public function label(): string
    {
        return ucfirst($this->value);
    }
}
