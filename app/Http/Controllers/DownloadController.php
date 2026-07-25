<?php

namespace App\Http\Controllers;

use App\Enums\LicenseStatus;
use App\Models\AppVersion;
use App\Models\License;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DownloadController extends Controller
{
    public function __invoke(AppVersion $version, License $license): StreamedResponse
    {
        $version->loadMissing('product');
        abort_unless($license->product_id === $version->product_id, 404);
        abort_unless($license->status === LicenseStatus::Active, 403);
        abort_if($license->expires_at?->isPast(), 403);

        $disk = Storage::disk(config('filesystems.builds_disk'));
        abort_unless($disk->exists($version->file_path), 404, 'Build file not found.');

        return $disk->download($version->file_path, "{$version->product->slug}-{$version->version_number}.".pathinfo($version->file_path, PATHINFO_EXTENSION));
    }
}
