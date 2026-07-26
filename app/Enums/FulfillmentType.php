<?php

namespace App\Enums;

enum FulfillmentType: string
{
    case LicenseKey = 'license_key';
    case Download = 'download';
    case WebAccess = 'web_access';
    case External = 'external';

    public function label(): string
    {
        return match ($this) {
            self::LicenseKey => 'License key',
            self::Download => 'Digital download',
            self::WebAccess => 'Web app access',
            self::External => 'External fulfillment',
        };
    }
}
