<?php

namespace App\Http\Controllers;

use App\Domain\Fulfillment\Actions\RevealCredentialAction;
use App\Models\Credential;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

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
}
