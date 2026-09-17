<?php

namespace App\Integrations\Keygen;

use App\Exceptions\Integrations\Keygen\KeygenException;
use App\Support\IntegrationSettingsStore;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class KeygenMiddlewareClient
{
    public function __construct(private ?IntegrationSettingsStore $settings = null) {}

    /**
     * @return array{filename: string, content: string}
     */
    public function generateOfflineLicense(string $requestFileContents): array
    {
        $response = $this->request()->post('/v1/offline/license-files', [
            'request_file_contents' => $requestFileContents,
        ]);

        if ($response->failed()) {
            $detail = data_get($response->json(), 'detail');

            throw new KeygenException(
                is_string($detail) && $detail !== '' ? 'Offline license generation failed: '.$detail : 'Offline license generation failed.',
                $response->status(),
            );
        }

        $payload = $response->json();
        $content = is_array($payload) ? (string) ($payload['lic_content'] ?? '') : '';

        if (blank($content)) {
            throw new KeygenException('The licensing middleware returned an empty .lic file.');
        }

        $filename = is_array($payload) && filled($payload['filename'] ?? null)
            ? basename((string) $payload['filename'])
            : 'merebhub-offline-license.lic';
        $filename = preg_replace('/[^A-Za-z0-9._-]/', '-', $filename) ?: 'merebhub-offline-license.lic';

        return [
            'filename' => Str::endsWith($filename, '.lic') ? $filename : $filename.'.lic',
            'content' => $content,
        ];
    }

    private function request(): PendingRequest
    {
        $baseUrl = rtrim((string) $this->setting('middleware_url', config('services.keygen.middleware_url')), '/');

        if (blank($baseUrl)) {
            throw new KeygenException('Offline activation middleware is not configured.');
        }

        $request = Http::asJson()
            ->acceptJson()
            ->timeout((int) config('services.keygen.timeout', 20))
            ->connectTimeout((int) config('services.keygen.connect_timeout', 5));
        $token = $this->setting('admin_token', config('services.keygen.admin_token'));

        if (filled($token)) {
            $request = $request->withHeaders([
                'Authorization' => 'Bearer '.(string) $token,
                'X-MerebHub-Admin-Token' => (string) $token,
            ]);
        }

        return $request->baseUrl($baseUrl);
    }

    private function setting(string $key, mixed $default = null): mixed
    {
        return ($this->settings ??= app(IntegrationSettingsStore::class))->get('keygen', $key, $default);
    }
}
