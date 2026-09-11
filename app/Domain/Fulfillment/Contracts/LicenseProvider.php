<?php

namespace App\Domain\Fulfillment\Contracts;

use App\Domain\Fulfillment\Data\LicenseProvisioningRequest;
use App\Domain\Fulfillment\Data\LicenseProvisioningResult;

interface LicenseProvider
{
    public function provision(LicenseProvisioningRequest $request): LicenseProvisioningResult;

    public function findByIdempotencyKey(string $idempotencyKey): ?LicenseProvisioningResult;
}
