<?php

namespace App\Domain\Support\Enums;

enum SupportAttachmentScanStatus: string
{
    case Pending = 'pending';
    case Clean = 'clean';
    case Rejected = 'rejected';
}
