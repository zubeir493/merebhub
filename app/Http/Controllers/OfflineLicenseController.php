<?php

namespace App\Http\Controllers;

use App\Domain\Shared\Actions\RecordAuditEventAction;
use App\Exceptions\Integrations\Keygen\KeygenException;
use App\Http\Requests\OfflineLicenseRequest;
use App\Integrations\Keygen\KeygenClient;
use App\Integrations\Keygen\KeygenMiddlewareClient;
use App\Models\Credential;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;

class OfflineLicenseController extends Controller
{
    public function __construct(
        private readonly KeygenClient $keygen,
        private readonly KeygenMiddlewareClient $middleware,
        private readonly RecordAuditEventAction $audit,
    ) {}

    public function generate(OfflineLicenseRequest $request): Response|RedirectResponse
    {
        $upload = $request->file('offline_request');
        $contents = $upload->get();

        if (! is_string($contents) || blank($contents) || strlen($contents) > 200000) {
            return back()->withErrors(['offline_request' => 'The uploaded request file is empty or too large.']);
        }

        $licenseKey = $this->extractLicenseKey($contents);
        $credential = $this->ownedCredential($request, $licenseKey);

        if ($credential === null) {
            return back()->withErrors(['offline_request' => 'That offline request does not match one of your purchased license keys.']);
        }

        try {
            $file = $this->middleware->generateOfflineLicense($contents);
        } catch (KeygenException $exception) {
            report($exception);

            return back()->withErrors(['offline_request' => 'Offline license generation is temporarily unavailable. Please try again later.']);
        }

        $this->audit->handle(
            'credential.offline_activation_generated',
            actor: $request->user(),
            subject: $credential,
            metadata: [
                'credential_id' => $credential->public_id,
                'entitlement_id' => $credential->entitlement_id,
                'provider' => $credential->entitlement?->provider,
            ],
        );

        return response($file['content'], 200, [
            'Cache-Control' => 'private, no-store, max-age=0',
            'Content-Disposition' => 'attachment; filename="'.$file['filename'].'"',
            'Content-Type' => 'text/plain; charset=utf-8',
            'Referrer-Policy' => 'no-referrer',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    public function download(Request $request, string $credential): Response
    {
        $credential = Credential::query()
            ->with('entitlement.product')
            ->where('public_id', $credential)
            ->firstOrFail();

        abort_unless(Gate::forUser($request->user())->allows('reveal', $credential), 404);

        $entitlement = $credential->entitlement;
        abort_unless($entitlement?->provider === 'keygen' && filled($entitlement->external_id), 404);

        try {
            $contents = $this->keygen->checkoutLicenseFile((string) $entitlement->external_id);
        } catch (KeygenException $exception) {
            report($exception);

            abort(503, 'The licensing service is temporarily unavailable. Please try again later.');
        }

        $this->audit->handle(
            'credential.offline_file_downloaded',
            actor: $request->user(),
            subject: $credential,
            metadata: [
                'credential_id' => $credential->public_id,
                'entitlement_id' => $entitlement->getKey(),
                'provider' => $entitlement->provider,
            ],
        );

        $productName = trim((string) ($entitlement->product?->name ?? 'merebhub-license'));
        $filename = Str::slug($productName).'-offline-license.lic';

        return response($contents, 200, [
            'Cache-Control' => 'private, no-store, max-age=0',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
            'Content-Type' => 'application/octet-stream',
            'Referrer-Policy' => 'no-referrer',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    private function extractLicenseKey(string $contents): string
    {
        if (! preg_match('/MRBREQ1\.([A-Za-z0-9_-]+)/', $contents, $matches)) {
            return '';
        }

        $payload = strtr($matches[1], '-_', '+/');
        $payload .= str_repeat('=', (4 - strlen($payload) % 4) % 4);
        $decoded = base64_decode($payload, true);

        if (! is_string($decoded) || blank($decoded)) {
            return '';
        }

        foreach (explode(';', $decoded) as $part) {
            [$key, $value] = array_pad(explode('=', $part, 2), 2, '');

            if ($key === 'key') {
                return trim(rawurldecode($value));
            }
        }

        return '';
    }

    private function ownedCredential(Request $request, string $licenseKey): ?Credential
    {
        if ($licenseKey === '') {
            return null;
        }

        $credentials = $request->user()->entitlements()
            ->where('status', 'active')
            ->with('credential.entitlement')
            ->get()
            ->pluck('credential')
            ->filter();

        foreach ($credentials as $credential) {
            if (hash_equals((string) $credential->secret, $licenseKey)) {
                return $credential;
            }
        }

        return null;
    }
}
