<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum OrderStatus: string implements HasColor, HasLabel
{
    case Pending = 'pending';
    case AwaitingPayment = 'awaiting_payment';
    case Paid = 'paid';
    case PaymentFailed = 'payment_failed';
    case PartiallyFulfilled = 'partially_fulfilled';
    case Fulfilled = 'fulfilled';
    case Refunded = 'refunded';
    case PartiallyRefunded = 'partially_refunded';
    case Cancelled = 'cancelled';

    public function getLabel(): string
    {
        return str($this->value)->headline();
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Pending, self::AwaitingPayment => 'warning',
            self::Paid, self::Fulfilled => 'success',
            self::PaymentFailed, self::Cancelled => 'danger',
            self::PartiallyFulfilled, self::PartiallyRefunded => 'info',
            self::Refunded => 'gray',
        };
    }
}
