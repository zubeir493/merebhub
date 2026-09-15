<?php

namespace App\Domain\Fulfillment\Providers;

use App\Domain\Fulfillment\Contracts\LicenseProvider;
use App\Domain\Fulfillment\Data\LicenseProvisioningRequest;
use App\Domain\Fulfillment\Data\LicenseProvisioningResult;
use App\Exceptions\Domain\Fulfillment\Exceptions\AmbiguousLicenseProvisioningException;
use App\Exceptions\Domain\Fulfillment\Exceptions\LicenseProviderException;
use App\Integrations\Keygen\KeygenClient;
use Illuminate\Http\Client\ConnectionException;
use Throwable;

class KeygenLicenseProvider implements LicenseProvider
{
    public function __construct(private readonly KeygenClient $client) {}

    public function provision(LicenseProvisioningRequest $request): LicenseProvisioningResult
    {
        if (blank($request->licensePolicyId)) {
            throw new LicenseProviderException('No active Keygen policy is mapped to this product or variant.');
        }

        try {
            $payload = $this->client->createLicense(
                policyId: $request->licensePolicyId,
                name: 'MerebHub '.$request->orderReference,
                metadata: [
                    config('marketplace.keygen.idempotency_metadata_key') => $request->idempotencyKey,
                    'merebhub_order_reference' => $request->orderReference,
                    'merebhub_product_identifier' => $request->productIdentifier,
                ],
            );
        } catch (ConnectionException $exception) {
            throw new AmbiguousLicenseProvisioningException('Keygen license creation timed out or was interrupted.', previous: $exception);
        } catch (Throwable $exception) {
            throw new LicenseProviderException('Keygen license provisioning failed.', previous: $exception);
        }

        return $this->toResult($payload);
    }

    public function findByIdempotencyKey(string $idempotencyKey): ?LicenseProvisioningResult
    {
        try {
            $licenses = $this->client->licenses($idempotencyKey);
        } catch (Throwable $exception) {
            throw new LicenseProviderException('Keygen license recovery failed.', previous: $exception);
        }

        $license = $licenses[0] ?? null;

        return is_array($license) ? $this->toResult(['data' => $license]) : null;
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function toResult(array $payload): LicenseProvisioningResult
    {
        $license = $payload['data'] ?? null;
        $attributes = is_array($license) ? ($license['attributes'] ?? []) : [];
        $externalId = is_array($license) ? ($license['id'] ?? null) : null;
        $licenseKey = is_array($attributes) ? ($attributes['key'] ?? null) : null;

        if (! is_string($externalId) || blank($externalId) || ! is_string($licenseKey) || blank($licenseKey)) {
            throw new LicenseProviderException('Keygen returned an incomplete license.');
        }

        return new LicenseProvisioningResult(
            externalId: $externalId,
            licenseKey: $licenseKey,
            metadata: [
                'provider' => 'keygen',
                'keygen_license_id' => $externalId,
                'keygen_product_id' => data_get($license, 'relationships.product.data.id'),
                'keygen_policy_id' => data_get($license, 'relationships.policy.data.id'),
                'status' => is_array($attributes) ? ($attributes['status'] ?? null) : null,
                'metadata' => is_array($attributes) ? ($attributes['metadata'] ?? []) : [],
            ],
        );
    }
}
