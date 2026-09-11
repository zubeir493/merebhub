<?php

namespace App\Domain\Fulfillment\Data;

class LicenseProvisioningRequest
{
    /**
     * @param  array<string, mixed>  $metadata
     */
    public function __construct(
        public readonly string $idempotencyKey,
        public readonly string $productIdentifier,
        public readonly string $orderReference,
        public readonly string $customerEmail,
        public readonly array $metadata = [],
    ) {}
}
