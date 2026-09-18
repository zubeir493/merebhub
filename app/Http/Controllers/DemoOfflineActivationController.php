<?php

namespace App\Http\Controllers;

use App\Exceptions\Integrations\Keygen\KeygenException;
use App\Integrations\Keygen\KeygenClient;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\View\View;

class DemoOfflineActivationController extends Controller
{
    private const ACCOUNT_ID = '0a3fca5f-d038-4ac5-82af-83e98ffc604c';

    private const PRODUCT_ID = '168e3bd8-695c-4f9b-8915-e5d2ebb40f82';

    private const POLICY_ID = 'c5524189-8941-4fab-8020-c9c96f5ba711';

    public function __construct(private readonly KeygenClient $keygen) {}

    public function show(Request $request): View
    {
        return view('storefront.demo-offline-activation', [
            'activationRequest' => $this->decodeRequest((string) $request->query('request', '')),
            'machineFile' => null,
            'error' => null,
        ]);
    }

    public function generate(Request $request): View|RedirectResponse
    {
        $validated = Validator::make($request->all(), [
            'request' => ['required', 'string', 'max:20000'],
        ])->validate();
        $activationRequest = $this->decodeRequest($validated['request']);

        if ($activationRequest === null) {
            return back()->withErrors(['request' => 'The activation QR request is invalid or incomplete.']);
        }

        try {
            $validation = $this->keygen->validateLicenseKey($activationRequest['key'], [
                'product' => self::PRODUCT_ID,
                'policy' => self::POLICY_ID,
                'fingerprint' => $activationRequest['fingerprint'],
            ]);
            $license = $validation['data'] ?? [];
            $relationships = $license['relationships'] ?? [];
            $actualProduct = data_get($relationships, 'product.data.id');
            $actualPolicy = data_get($relationships, 'policy.data.id');
            $licenseId = $license['id'] ?? null;

            abort_unless(
                data_get($validation, 'meta.valid')
                    && $actualProduct === self::PRODUCT_ID
                    && $actualPolicy === self::POLICY_ID
                    && is_string($licenseId)
                    && $licenseId !== '',
                422,
                'This license is not valid for the MerebHub Clock Demo product and policy.',
            );

            $machine = $this->keygen->activateMachine(
                $licenseId,
                $activationRequest['fingerprint'],
                'MerebHub Clock Demo on '.$activationRequest['fingerprint'],
            );
            $machineId = data_get($machine, 'data.id');

            abort_unless(is_string($machineId) && $machineId !== '', 422, 'Keygen did not return a machine ID.');

            return view('storefront.demo-offline-activation', [
                'activationRequest' => $activationRequest,
                'machineFile' => $this->keygen->checkoutMachineFile($machineId),
                'error' => null,
            ]);
        } catch (KeygenException $exception) {
            report($exception);

            return view('storefront.demo-offline-activation', [
                'activationRequest' => $activationRequest,
                'machineFile' => null,
                'error' => 'Keygen could not issue the machine file. Check the license, policy machine limit, and server logs.',
            ]);
        }
    }

    /**
     * @return array{v: int, app: string, account: string, product: string, policy: string, key: string, fingerprint: string, nonce: string}|null
     */
    private function decodeRequest(string $encoded): ?array
    {
        if ($encoded === '') {
            return null;
        }

        $encoded .= str_repeat('=', (4 - strlen($encoded) % 4) % 4);
        $decoded = base64_decode(strtr($encoded, '-_', '+/'), true);
        $payload = is_string($decoded) ? json_decode($decoded, true) : null;

        if (! is_array($payload)
            || ($payload['v'] ?? null) !== 1
            || ($payload['app'] ?? null) !== 'merebhub-keygen-clock-demo'
            || ($payload['account'] ?? null) !== self::ACCOUNT_ID
            || ($payload['product'] ?? null) !== self::PRODUCT_ID
            || ($payload['policy'] ?? null) !== self::POLICY_ID
            || ! is_string($payload['key'] ?? null)
            || ! is_string($payload['fingerprint'] ?? null)
            || ! preg_match('/^[a-f0-9]{64}$/', $payload['fingerprint'])) {
            return null;
        }

        return [
            'v' => 1,
            'app' => 'merebhub-keygen-clock-demo',
            'account' => self::ACCOUNT_ID,
            'product' => self::PRODUCT_ID,
            'policy' => self::POLICY_ID,
            'key' => $payload['key'],
            'fingerprint' => $payload['fingerprint'],
            'nonce' => is_string($payload['nonce'] ?? null) ? $payload['nonce'] : '',
        ];
    }
}
