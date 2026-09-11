<?php

namespace App\Domain\Fulfillment\Enums;

enum FulfillmentUnitStatus: string
{
    case Pending = 'pending';
    case Processing = 'processing';
    case Completed = 'completed';
    case NeedsAttention = 'needs_attention';
}
