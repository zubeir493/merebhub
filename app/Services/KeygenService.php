<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Product;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class KeygenService
{
    public function createLicense(Order $order, Product $product, int $activationLimit = 1): array
    {
        $policyId = $product->keygen_policy_id ?: config('services.keygen.policy_id');

        if (blank($policyId)) {
            throw new RuntimeException('KEYGEN_POLICY_ID is not configured.');
        }

        return $this->request()
            ->post($this->endpoint('licenses'), [
                'data' => [
                    'type' => 'licenses',
                    'attributes' => [
                        'name' => "{$product->name} - {$order->buyer_email}",
                        'metadata' => [
                            'merebhubOrderId' => $order->id,
                            'wooCommerceOrderId' => $order->wc_order_id,
                            'buyerEmail' => $order->buyer_email,
                            'activationLimit' => $activationLimit,
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

    public function revoke(string $licenseId): void
    {
        $this->request()
            ->delete($this->endpoint("licenses/{$licenseId}/actions/revoke"))
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
