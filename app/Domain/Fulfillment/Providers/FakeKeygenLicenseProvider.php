<?php

namespace App\Domain\Fulfillment\Providers;

use App\Domain\Fulfillment\Contracts\LicenseProvider;
use App\Domain\Fulfillment\Data\LicenseProvisioningRequest;
use App\Domain\Fulfillment\Data\LicenseProvisioningResult;
use App\Exceptions\Domain\Fulfillment\Exceptions\AmbiguousLicenseProvisioningException;
use App\Exceptions\Domain\Fulfillment\Exceptions\LicenseProviderException;
use Illuminate\Support\Str;

class FakeKeygenLicenseProvider implements LicenseProvider
{
    /** @var array<string, LicenseProvisioningResult> */
    public array $provisioned = [];

    /** @var array<string, bool> */
    public array $ambiguousIdempotencyKeys = [];

    /** @var array<string, bool> */
    public array $failedIdempotencyKeys = [];

    public function provision(LicenseProvisioningRequest $request): LicenseProvisioningResult
    {
        if (isset($this->failedIdempotencyKeys[$request->idempotencyKey])) {
            throw new LicenseProviderException('The fake Keygen provider failed.');
        }

        $result = $this->provisioned[$request->idempotencyKey]
            ??= new LicenseProvisioningResult(
                externalId: 'fake-'.Str::lower(Str::random(16)),
                licenseKey: 'MH-'.Str::upper(Str::random(20)),
                metadata: ['provider' => 'fake-keygen'],
            );

        if (isset($this->ambiguousIdempotencyKeys[$request->idempotencyKey])) {
            unset($this->ambiguousIdempotencyKeys[$request->idempotencyKey]);

            throw new AmbiguousLicenseProvisioningException('The provider response was ambiguous.');
        }

        return $result;
    }

    public function findByIdempotencyKey(string $idempotencyKey): ?LicenseProvisioningResult
    {
        return $this->provisioned[$idempotencyKey] ?? null;
    }
}
