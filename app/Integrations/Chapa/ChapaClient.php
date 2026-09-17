<?php

namespace App\Integrations\Chapa;

use App\Support\IntegrationSettingsStore;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

class ChapaClient
{
    public function __construct(private ?IntegrationSettingsStore $settings = null) {}

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
        $secretKey = $this->setting('secret_key', config('services.chapa.secret_key'));

        if (blank($secretKey)) {
            throw new ChapaException('Chapa is not configured.');
        }

        return Http::acceptJson()
            ->asJson()
            ->withToken($secretKey)
            ->baseUrl((string) $this->setting('base_url', config('services.chapa.base_url', 'https://api.chapa.co/v1')))
            ->timeout((int) $this->setting('timeout', config('services.chapa.timeout', 10)))
            ->connectTimeout((int) $this->setting('connect_timeout', config('services.chapa.connect_timeout', 5)))
            ->retry(2, 200);
    }

    private function setting(string $key, mixed $default = null): mixed
    {
        return ($this->settings ??= app(IntegrationSettingsStore::class))->get('chapa', $key, $default);
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
