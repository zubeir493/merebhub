<?php

namespace App\Domain\Fulfillment\Data;

class LicenseProvisioningResult
{
    /**
     * @param  array<string, mixed>  $metadata
     */
    public function __construct(
        public readonly string $externalId,
        public readonly string $licenseKey,
        public readonly array $metadata = [],
    ) {}
}
