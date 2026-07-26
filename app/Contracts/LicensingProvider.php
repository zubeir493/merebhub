<?php

namespace App\Contracts;

use App\Models\OrderItem;

interface LicensingProvider
{
    /**
     * @return array{id: string, key: string, expires_at: ?string}
     */
    public function createLicense(OrderItem $orderItem): array;

    public function suspendLicense(string $providerLicenseId): void;

    public function reinstateLicense(string $providerLicenseId): void;

    public function revokeLicense(string $providerLicenseId): void;

    public function extendLicense(string $providerLicenseId, \DateTimeInterface $expiresAt): void;
}
