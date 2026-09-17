<?php

namespace App\Http\Controllers;

use App\Domain\Fulfillment\Actions\RevealCredentialAction;
use App\Models\Credential;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;

class CredentialController extends Controller
{
    public function __construct(private readonly RevealCredentialAction $revealCredential) {}

    public function reveal(Request $request, string $credential): JsonResponse
    {
        $credential = Credential::query()->where('public_id', $credential)->firstOrFail();

        abort_unless(Gate::forUser($request->user())->allows('reveal', $credential), 404);

        $secret = $this->revealCredential->handle($credential, $request->user());

        return response()->json([
            'credential' => [
                'id' => $credential->public_id,
                'type' => $credential->type,
                'secret' => $secret,
            ],
        ])->withHeaders([
            'Cache-Control' => 'private, no-store, max-age=0',
            'Pragma' => 'no-cache',
            'Referrer-Policy' => 'no-referrer',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    public function downloadText(Request $request, string $credential): Response
    {
        $credential = Credential::query()
            ->with(['entitlement.product', 'entitlement.order', 'entitlement.orderLine.purchasable'])
            ->where('public_id', $credential)
            ->firstOrFail();

        abort_unless(Gate::forUser($request->user())->allows('reveal', $credential), 404);

        $secret = $this->revealCredential->handle($credential, $request->user());
        $entitlement = $credential->entitlement;
        $productName = (string) ($entitlement?->product?->name ?? 'Digital product');
        $variantName = $entitlement?->variantDisplayName() ?? 'Standard license';
        $orderReference = (string) ($entitlement?->order?->reference ?? '—');
        $content = implode("\n", [
            'MerebHub license',
            'Product: '.$productName,
            'Variant: '.$variantName,
            'Order: '.$orderReference,
            'License key: '.$secret,
            '',
        ]);

        return response($content, 200, [
            'Cache-Control' => 'private, no-store, max-age=0',
            'Content-Disposition' => 'attachment; filename="'.Str::slug($productName).'-license.txt"',
            'Content-Type' => 'text/plain; charset=utf-8',
            'Referrer-Policy' => 'no-referrer',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
