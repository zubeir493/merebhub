<?php

namespace App\Http\Controllers;

use App\Enums\LicenseStatus;
use App\Enums\MalwareScanStatus;
use App\Enums\OrderStatus;
use App\Enums\ReleaseStatus;
use App\Models\AppVersion;
use App\Models\Download;
use App\Models\License;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DownloadController extends Controller
{
    public function __invoke(Request $request, AppVersion $version, License $license): StreamedResponse
    {
        $version->loadMissing('product');
        $license->loadMissing(['order', 'orderItem.productPlan']);
        abort_unless($license->product_id === $version->product_id, 404);
        abort_unless(
            $license->customer_id === $request->user()->id
            || $license->order->buyer_user_id === $request->user()->id
            || $license->buyer_email === $request->user()->email,
            404,
        );
        abort_unless(in_array($license->order->status, [
            OrderStatus::Paid,
            OrderStatus::PartiallyFulfilled,
            OrderStatus::Fulfilled,
        ], true), 403);
        abort_unless($license->status === LicenseStatus::Active, 403);
        abort_if($license->expires_at?->isPast(), 403);
        abort_unless($version->release_status === ReleaseStatus::Published, 403);
        abort_unless($version->scan_status === MalwareScanStatus::Clean, 403);

        $updateDurationDays = $license->orderItem?->update_duration_days;

        if ($updateDurationDays && $version->published_at) {
            abort_if($version->published_at->isAfter($license->order->paid_at->addDays($updateDurationDays)), 403);
        }

        $downloadLimit = $license->orderItem?->productPlan?->download_limit;

        if ($downloadLimit) {
            abort_if(
                Download::whereBelongsTo($license->orderItem)->whereNull('revoked_at')->count() >= $downloadLimit,
                403,
                'Download limit reached.',
            );
        }

        $disk = Storage::disk(config('filesystems.builds_disk'));
        abort_unless($disk->exists($version->file_path), 404, 'Build file not found.');

        Download::create([
            'user_id' => $request->user()->id,
            'order_item_id' => $license->order_item_id,
            'license_id' => $license->id,
            'app_version_id' => $version->id,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'downloaded_at' => now(),
        ]);

        return $disk->download($version->file_path, "{$version->product->slug}-{$version->version_number}.".pathinfo($version->file_path, PATHINFO_EXTENSION));
    }
}
