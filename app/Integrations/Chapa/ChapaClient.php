<?php

namespace App\Integrations\Chapa;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

class ChapaClient
{
    /**
     * @return array<string, mixed>
     */
    public function initialize(array $payload): array
    {
        return $this->decode($this->request()->post('/transaction/initialize', $payload));
    }

    /**
     * @return array<string, mixed>
     */
    public function verify(string $transactionReference): array
    {
        return $this->decode($this->request()->get('/transaction/verify/'.urlencode($transactionReference)));
    }

    private function request(): PendingRequest
    {
        $secretKey = config('services.chapa.secret_key');

        if (blank($secretKey)) {
            throw new ChapaException('Chapa is not configured.');
        }

        return Http::acceptJson()
            ->asJson()
            ->withToken($secretKey)
            ->baseUrl((string) config('services.chapa.base_url', 'https://api.chapa.co/v1'))
            ->timeout((int) config('services.chapa.timeout', 10))
            ->connectTimeout((int) config('services.chapa.connect_timeout', 5))
            ->retry(2, 200);
    }

    /**
     * @return array<string, mixed>
     */
    private function decode(Response $response): array
    {
        if ($response->failed()) {
            throw new ChapaException('Chapa returned an unsuccessful response.');
        }

        $payload = $response->json();

        if (! is_array($payload)) {
            throw new ChapaException('Chapa returned an invalid response.');
        }

        return $payload;
    }
}
