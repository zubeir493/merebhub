<?php

namespace App\Services;

use App\Contracts\LicensingProvider;
use App\Models\OrderItem;
use DateTimeInterface;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class KeygenService implements LicensingProvider
{
    public function createLicense(OrderItem $orderItem): array
    {
        $orderItem->loadMissing(['order', 'product', 'productPlan']);
        $policyId = $orderItem->license_configuration['keygen_policy_id']
            ?? $orderItem->productPlan?->keygen_policy_id
            ?? $orderItem->product->keygen_policy_id
            ?? config('services.keygen.policy_id');

        if (blank($policyId)) {
            throw new RuntimeException('KEYGEN_POLICY_ID is not configured.');
        }

        $response = $this->request()
            ->post($this->endpoint('licenses'), [
                'data' => [
                    'type' => 'licenses',
                    'attributes' => [
                        'name' => "{$orderItem->product_name} - {$orderItem->order->buyer_email}",
                        'metadata' => [
                            'merebhubOrderId' => $orderItem->order_id,
                            'merebhubOrderItemId' => $orderItem->id,
                            'merebhubOrderReference' => $orderItem->order->public_id,
                            'buyerEmail' => $orderItem->order->buyer_email,
                            'activationLimit' => $orderItem->license_configuration['activation_limit'] ?? 1,
                        ],
                    ],
                    'relationships' => [
                        'policy' => [
                            'data' => [
                                'type' => 'policies',
                                'id' => $policyId,
                            ],
                        ],
                    ],
                ],
            ])
            ->throw()
            ->json();

        return $this->licenseData($response);
    }

    public function validate(string $licenseKey): array
    {
        return $this->request(authenticated: false)
            ->post($this->endpoint('licenses/actions/validate-key'), [
                'meta' => ['key' => $licenseKey],
            ])
            ->throw()
            ->json();
    }

    public function suspendLicense(string $providerLicenseId): void
    {
        $this->request()
            ->post($this->endpoint("licenses/{$providerLicenseId}/actions/suspend"))
            ->throw();
    }

    public function reinstateLicense(string $providerLicenseId): void
    {
        $this->request()
            ->post($this->endpoint("licenses/{$providerLicenseId}/actions/reinstate"))
            ->throw();
    }

    public function revokeLicense(string $providerLicenseId): void
    {
        $this->request()
            ->delete($this->endpoint("licenses/{$providerLicenseId}/actions/revoke"))
            ->throw();
    }

    public function extendLicense(string $providerLicenseId, DateTimeInterface $expiresAt): void
    {
        $this->request()
            ->patch($this->endpoint("licenses/{$providerLicenseId}"), [
                'data' => [
                    'type' => 'licenses',
                    'id' => $providerLicenseId,
                    'attributes' => ['expiry' => $expiresAt->format(DATE_ATOM)],
                ],
            ])
            ->throw();
    }

    public function licenseData(array $response): array
    {
        return [
            'id' => Arr::get($response, 'data.id'),
            'key' => Arr::get($response, 'data.attributes.key'),
            'expires_at' => Arr::get($response, 'data.attributes.expiry'),
        ];
    }

    private function request(bool $authenticated = true): PendingRequest
    {
        $request = Http::accept('application/vnd.api+json')
            ->contentType('application/vnd.api+json')
            ->connectTimeout(5)
            ->timeout((int) config('services.keygen.timeout', 15))
            ->retry([200, 500, 1000], throw: false);

        if (! $authenticated) {
            return $request;
        }

        $token = config('services.keygen.api_token');

        if (blank($token)) {
            throw new RuntimeException('KEYGEN_API_TOKEN is not configured.');
        }

        return $request->withToken($token);
    }

    private function endpoint(string $path): string
    {
        $apiUrl = rtrim((string) config('services.keygen.api_url'), '/');
        $accountId = config('services.keygen.account_id');

        if (blank($apiUrl) || blank($accountId)) {
            throw new RuntimeException('KEYGEN_API_URL and KEYGEN_ACCOUNT_ID must be configured.');
        }

        if (str_ends_with($apiUrl, "/accounts/{$accountId}")) {
            return $apiUrl.'/'.ltrim($path, '/');
        }

        return "{$apiUrl}/v1/accounts/{$accountId}/".ltrim($path, '/');
    }
}
