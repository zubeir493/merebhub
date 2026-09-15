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
        return $this->decode($this->request()->get('/'));
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function products(): array
    {
        return $this->data($this->decode($this->request()->get('/products')));
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function policies(?string $productId = null): array
    {
        $query = $productId === null ? [] : ['product' => $productId];

        return $this->data($this->decode($this->request()->get('/policies', $query)));
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function licenses(?string $idempotencyKey = null): array
    {
        $query = ['limit' => 1];

        if ($idempotencyKey !== null) {
            $query['metadata['.config('marketplace.keygen.idempotency_metadata_key').']'] = $idempotencyKey;
        }

        return $this->data($this->decode($this->request()->get('/licenses', $query)));
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
        $payload = $this->decode($this->baseRequest()->withBasicAuth($email, $password)->post('/tokens'));
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
