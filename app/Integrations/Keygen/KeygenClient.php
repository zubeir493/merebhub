<?php

namespace App\Integrations\Keygen;

use App\Exceptions\Integrations\Keygen\KeygenException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class KeygenClient
{
    /**
     * @return array<string, mixed>
     */
    public function account(): array
    {
        return $this->decode($this->request()->get('/me'));
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function products(int $limit = 100): array
    {
        return $this->data($this->decode($this->request()->get('/products', ['limit' => $limit])));
    }

    /**
     * @return array<string, mixed>
     */
    public function createProduct(
        string $name,
        string $code,
        ?string $url = null,
        string $distributionStrategy = 'LICENSED',
    ): array {
        return $this->decode($this->request()->post('/products', [
            'data' => [
                'type' => 'products',
                'attributes' => array_filter([
                    'name' => $name,
                    'code' => $code,
                    'url' => $url,
                    'distributionStrategy' => $distributionStrategy,
                ], static fn (mixed $value): bool => $value !== null && $value !== ''),
            ],
        ]));
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @return array<string, mixed>
     */
    public function updateProduct(string $productId, array $attributes): array
    {
        return $this->decode($this->request()->patch('/products/'.rawurlencode($productId), [
            'data' => [
                'type' => 'products',
                'id' => $productId,
                'attributes' => $attributes,
            ],
        ]));
    }

    public function deleteProduct(string $productId): void
    {
        $this->delete('/products/'.rawurlencode($productId));
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function policies(?string $productId = null): array
    {
        $query = ['limit' => 100];

        if ($productId !== null) {
            $query['product'] = $productId;
        }

        return $this->data($this->decode($this->request()->get('/policies', $query)));
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @return array<string, mixed>
     */
    public function createPolicy(string $name, string $productId, array $attributes = []): array
    {
        return $this->decode($this->request()->post('/policies', [
            'data' => [
                'type' => 'policies',
                'attributes' => array_merge(['name' => $name], $attributes),
                'relationships' => [
                    'product' => [
                        'data' => [
                            'type' => 'products',
                            'id' => $productId,
                        ],
                    ],
                ],
            ],
        ]));
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @return array<string, mixed>
     */
    public function updatePolicy(string $policyId, array $attributes): array
    {
        return $this->decode($this->request()->patch('/policies/'.rawurlencode($policyId), [
            'data' => [
                'type' => 'policies',
                'id' => $policyId,
                'attributes' => $attributes,
            ],
        ]));
    }

    public function deletePolicy(string $policyId): void
    {
        $this->delete('/policies/'.rawurlencode($policyId));
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function licenses(?string $idempotencyKey = null, int $limit = 100): array
    {
        $query = ['limit' => $limit];

        if ($idempotencyKey !== null) {
            $query['metadata['.config('marketplace.keygen.idempotency_metadata_key').']'] = $idempotencyKey;
        }

        return $this->data($this->decode($this->request()->get('/licenses', $query)));
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @return array<string, mixed>
     */
    public function updateLicense(string $licenseId, array $attributes): array
    {
        return $this->decode($this->request()->patch('/licenses/'.rawurlencode($licenseId), [
            'data' => [
                'type' => 'licenses',
                'id' => $licenseId,
                'attributes' => $attributes,
            ],
        ]));
    }

    public function deleteLicense(string $licenseId): void
    {
        $this->delete('/licenses/'.rawurlencode($licenseId));
    }

    /**
     * @param  array<string, mixed>  $meta
     * @return array<string, mixed>
     */
    public function licenseAction(string $licenseId, string $action, array $meta = []): array
    {
        $uri = '/licenses/'.rawurlencode($licenseId).'/'.$action;

        return $this->decode(blank($meta)
            ? $this->request()->post($uri)
            : $this->request()->post($uri, ['meta' => $meta]));
    }

    /**
     * @param  array<string, mixed>  $metadata
     * @return array<string, mixed>
     */
    public function createLicense(string $policyId, string $name, array $metadata = []): array
    {
        return $this->decode($this->request()->post('/licenses', [
            'data' => [
                'type' => 'licenses',
                'attributes' => [
                    'name' => $name,
                    'metadata' => $metadata,
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
        ]));
    }

    public function issueToken(string $email, string $password): string
    {
        $payload = $this->decode($this->baseRequest()->withBasicAuth($email, $password)->post('/tokens', [
            'data' => [
                'type' => 'tokens',
                'attributes' => [
                    'name' => 'MerebHub server integration',
                ],
            ],
        ]));
        $token = data_get($payload, 'data.attributes.token');

        if (! is_string($token) || blank($token)) {
            throw new KeygenException('Keygen did not return an API token.');
        }

        return $token;
    }

    private function request(): PendingRequest
    {
        $token = config('services.keygen.api_token');

        if (blank($token)) {
            throw new KeygenException('Keygen is not configured with an API token.');
        }

        return $this->baseRequest()->withToken((string) $token);
    }

    private function baseRequest(): PendingRequest
    {
        $accountId = config('services.keygen.account_id');

        if (blank($accountId)) {
            throw new KeygenException('Keygen is not configured with an account ID.');
        }

        $baseUrl = rtrim((string) config('services.keygen.url', 'https://keygen.localhost:8443'), '/');

        if (! Str::endsWith($baseUrl, '/v1')) {
            $baseUrl .= '/v1';
        }

        $request = Http::asJson()->withHeaders([
            'Accept' => 'application/vnd.api+json',
            'Content-Type' => 'application/vnd.api+json',
        ])
            ->baseUrl($baseUrl.'/accounts/'.rawurlencode((string) $accountId))
            ->timeout((int) config('services.keygen.timeout', 10))
            ->connectTimeout((int) config('services.keygen.connect_timeout', 5));

        if (filled($hostHeader = config('services.keygen.host_header'))) {
            $request = $request->withHeaders(['Host' => (string) $hostHeader]);
        }

        if (! config('services.keygen.verify', true)) {
            $request = $request->withoutVerifying();
        }

        return $request;
    }

    /**
     * @return array<string, mixed>
     */
    private function decode(Response $response): array
    {
        if ($response->failed()) {
            $detail = data_get($response->json(), 'errors.0.detail');

            throw new KeygenException(
                is_string($detail) && $detail !== '' ? 'Keygen request failed: '.$detail : 'Keygen request failed.',
                $response->status(),
            );
        }

        $payload = $response->json();

        if (! is_array($payload)) {
            throw new KeygenException('Keygen returned an invalid response.');
        }

        return $payload;
    }

    private function delete(string $uri): void
    {
        $response = $this->request()->delete($uri);

        if ($response->failed()) {
            $this->decode($response);
        }
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<int, array<string, mixed>>
     */
    private function data(array $payload): array
    {
        $data = $payload['data'] ?? [];

        return is_array($data) && array_is_list($data) ? $data : [];
    }
}
