<?php

namespace App\Support;

use App\Models\IntegrationSetting;
use Illuminate\Filesystem\FilesystemManager;
use Illuminate\Support\Facades\Schema;
use Throwable;

class S3StorageConfigurator
{
    public function __construct(private readonly FilesystemManager $filesystems) {}

    public function apply(): void
    {
        try {
            if (! Schema::hasTable((new IntegrationSetting)->getTable())) {
                return;
            }

            $settings = IntegrationSetting::query()
                ->where('provider', 's3')
                ->get()
                ->mapWithKeys(fn (IntegrationSetting $setting): array => [$setting->key => $setting->value]);
        } catch (Throwable) {
            return;
        }

        $enabled = filter_var(
            $settings->get('enabled', config('marketplace.object_storage.enabled', false)),
            FILTER_VALIDATE_BOOL,
        );

        if (! $enabled) {
            $this->useFallbackDisks();

            return;
        }

        $accessKeyId = $settings->get('access_key_id', config('filesystems.disks.s3.key'));
        $secretAccessKey = $settings->get('secret_access_key', config('filesystems.disks.s3.secret'));
        $publicBucket = $settings->get('public_bucket', config('filesystems.disks.s3.bucket'));
        $privateBucket = $settings->get('private_bucket', config('filesystems.disks.s3_private.bucket'));

        if (blank($accessKeyId) || blank($secretAccessKey) || blank($publicBucket) || blank($privateBucket)) {
            $this->useFallbackDisks();

            return;
        }

        $region = (string) $settings->get('region', config('filesystems.disks.s3.region', 'eu-central-003'));
        $endpoint = (string) $settings->get('endpoint', config('filesystems.disks.s3.endpoint'));
        $publicUrl = $settings->get('public_url', config('filesystems.disks.s3.url'));
        $usePathStyleEndpoint = filter_var(
            $settings->get('use_path_style_endpoint', config('filesystems.disks.s3.use_path_style_endpoint', true)),
            FILTER_VALIDATE_BOOL,
        );
        $verifySsl = filter_var(
            $settings->get('verify_ssl', data_get(config('filesystems.disks.s3'), 'http.verify', true)),
            FILTER_VALIDATE_BOOL,
        );

        $sharedConfiguration = [
            'key' => $accessKeyId,
            'secret' => $secretAccessKey,
            'region' => $region,
            'endpoint' => $endpoint,
            'use_path_style_endpoint' => $usePathStyleEndpoint,
            'http' => ['verify' => $verifySsl],
        ];

        config()->set([
            'filesystems.disks.s3' => [
                ...config('filesystems.disks.s3', []),
                ...$sharedConfiguration,
                'bucket' => $publicBucket,
                'url' => $publicUrl,
                'visibility' => 'public',
            ],
            'filesystems.disks.s3_private' => [
                ...config('filesystems.disks.s3_private', []),
                ...$sharedConfiguration,
                'bucket' => $privateBucket,
                'visibility' => 'private',
            ],
            'filament.default_filesystem_disk' => 's3',
            'media-library.disk_name' => 's3',
            'marketplace.public_media_disk' => 's3',
            'marketplace.private_files_disk' => 's3_private',
            'support.attachments_disk' => 's3_private',
        ]);

        $this->filesystems->forgetDisk(['s3', 's3_private']);
    }

    private function useFallbackDisks(): void
    {
        $publicMediaDisk = (string) config('marketplace.object_storage.fallback_disks.public_media', 'public');
        $privateFilesDisk = (string) config('marketplace.object_storage.fallback_disks.private_files', 'private');

        config()->set([
            'filament.default_filesystem_disk' => config('marketplace.object_storage.fallback_disks.filament', 'local'),
            'media-library.disk_name' => $publicMediaDisk,
            'marketplace.public_media_disk' => $publicMediaDisk,
            'marketplace.private_files_disk' => $privateFilesDisk,
            'support.attachments_disk' => config('marketplace.object_storage.fallback_disks.support_attachments', 'private'),
        ]);

        $this->filesystems->forgetDisk(['s3', 's3_private']);
    }
}
