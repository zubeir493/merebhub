<?php

namespace App\Integrations\Chapa;

use App\Support\IntegrationSettingsStore;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Throwable;

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

        $baseUrl = rtrim((string) $this->setting('base_url', config('services.chapa.base_url', 'https://api.chapa.co/v1')), '/');

        if (! Str::endsWith($baseUrl, '/v1')) {
            $baseUrl .= '/v1';
        }

        return Http::acceptJson()
            ->asJson()
            ->withToken($secretKey)
            ->baseUrl($baseUrl)
            ->timeout((int) $this->setting('timeout', config('services.chapa.timeout', 10)))
            ->connectTimeout((int) $this->setting('connect_timeout', config('services.chapa.connect_timeout', 5)))
            ->retry([200, 500], 0, function (Throwable $exception): bool {
                return $exception instanceof ConnectionException
                    || ($exception instanceof RequestException && $exception->response?->serverError());
            }, throw: false);
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
            $message = data_get($response->json(), 'message')
                ?? data_get($response->json(), 'error')
                ?? data_get($response->json(), 'errors.0.detail');
            $message = collect([$message])
                ->flatten()
                ->filter(static fn (mixed $value): bool => is_scalar($value) && filled($value))
                ->map(static fn (mixed $value): string => (string) $value)
                ->implode('; ');

            throw new ChapaException(
                'Chapa request failed'.(filled($message) ? ': '.Str::limit($message, 240) : '.'),
                $response->status(),
            );
        }

        $payload = $response->json();

        if (! is_array($payload)) {
            throw new ChapaException('Chapa returned an invalid response.');
        }

        return $payload;
    }
}
