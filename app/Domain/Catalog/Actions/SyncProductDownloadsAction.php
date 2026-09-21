<?php

namespace App\Domain\Catalog\Actions;

use App\Domain\Fulfillment\Enums\AssetScanStatus;
use App\Models\Product;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class SyncProductDownloadsAction
{
    /**
     * @param  array<int, mixed>  $paths
     * @param  array<string, mixed>  $filenames
     */
    public function handle(Product $product, array $paths, array $filenames, ?string $disk = null): void
    {
        $disk ??= (string) config('marketplace.private_files_disk', 'private');

        $normalizedPaths = collect($paths)
            ->filter(fn (mixed $path): bool => is_string($path) && filled($path))
            ->unique()
            ->values();

        foreach ($normalizedPaths as $path) {
            if (! Str::startsWith($path, 'downloads/products/') || Str::contains($path, '..')) {
                throw ValidationException::withMessages([
                    'downloadable_files' => 'One of the selected downloadable files is not permitted.',
                ]);
            }

            if (! Storage::disk($disk)->exists($path)) {
                throw ValidationException::withMessages([
                    'downloadable_files' => 'One of the selected downloadable files is no longer available.',
                ]);
            }
        }

        $removedAssets = DB::transaction(function () use ($product, $normalizedPaths, $filenames, $disk) {
            $existingAssets = $product->downloadableAssets()->get()->keyBy('path');

            foreach ($normalizedPaths as $path) {
                $filename = $this->safeFilename($filenames[$path] ?? basename($path), basename($path));
                $asset = $existingAssets->get($path);

                if ($asset !== null) {
                    $asset->update(['filename' => $filename]);

                    continue;
                }

                $product->downloadableAssets()->create([
                    'disk' => $disk,
                    'path' => $path,
                    'filename' => $filename,
                    'checksum' => $this->checksum($disk, $path),
                    'size' => Storage::disk($disk)->size($path),
                    'scan_status' => AssetScanStatus::Clean,
                    'scanned_at' => now(),
                ]);
            }

            $removedAssets = $existingAssets
                ->reject(fn ($asset, string $path): bool => $normalizedPaths->contains($path))
                ->values();

            $product->downloadableAssets()
                ->whereIn('id', $removedAssets->pluck('id'))
                ->delete();

            return $removedAssets;
        });

        DB::afterCommit(function () use ($removedAssets): void {
            foreach ($removedAssets as $asset) {
                Storage::disk($asset->disk)->delete($asset->path);
            }
        });
    }

    private function safeFilename(mixed $filename, string $fallback): string
    {
        $filename = basename(str_replace('\\', '/', trim((string) $filename)));

        return $filename !== '' ? $filename : $fallback;
    }

    private function checksum(string $disk, string $path): ?string
    {
        $stream = Storage::disk($disk)->readStream($path);

        if ($stream === false) {
            return null;
        }

        try {
            $hash = hash_init('sha256');
            hash_update_stream($hash, $stream);

            return hash_final($hash);
        } finally {
            fclose($stream);
        }
    }
}
