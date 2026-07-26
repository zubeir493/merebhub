<?php

namespace App\Enums;

enum BillingModel: string
{
    case Free = 'free';
    case OneTime = 'one_time';
    case ManualSubscription = 'manual_subscription';

    public function label(): string
    {
        return match ($this) {
            self::Free => 'Free',
            self::OneTime => 'One-time payment',
            self::ManualSubscription => 'Subscription (manual renewal)',
        };
    }
}
