<?php

namespace App\Http\Controllers;

use App\Domain\Shared\Actions\RecordAuditEventAction;
use App\Models\DownloadableAsset;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DownloadController extends Controller
{
    public function __construct(private readonly RecordAuditEventAction $audit) {}

    public function url(Request $request, string $asset): JsonResponse
    {
        $downloadableAsset = DownloadableAsset::query()->where('public_id', $asset)->firstOrFail();

        abort_unless(Gate::forUser($request->user())->allows('download', $downloadableAsset), 404);
        $expiresAt = now()->addMinutes(5);

        return response()->json([
            'url' => URL::temporarySignedRoute(
                'downloads.show',
                $expiresAt,
                ['downloadableAsset' => $downloadableAsset->public_id],
            ),
            'expires_at' => $expiresAt->toISOString(),
        ]);
    }

    public function download(Request $request, string $asset): StreamedResponse
    {
        $downloadableAsset = DownloadableAsset::query()->where('public_id', $asset)->firstOrFail();

        abort_unless(Gate::forUser($request->user())->allows('download', $downloadableAsset), 404);

        abort_unless(Storage::disk($downloadableAsset->disk)->exists($downloadableAsset->path), 404);

        $this->audit->handle(
            'entitlement.downloaded',
            actor: $request->user(),
            subject: $downloadableAsset,
            metadata: ['asset_id' => $downloadableAsset->public_id],
        );

        return Storage::disk($downloadableAsset->disk)->download($downloadableAsset->path, $downloadableAsset->filename);
    }
}
