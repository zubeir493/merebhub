<?php

namespace App\Domain\Fulfillment\Enums;

enum AssetScanStatus: string
{
    case Pending = 'pending';
    case Clean = 'clean';
    case Rejected = 'rejected';
}
