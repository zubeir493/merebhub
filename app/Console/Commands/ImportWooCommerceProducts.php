<?php

namespace App\Console\Commands;

use App\Domain\Catalog\Actions\ImportWooCommerceCatalogAction;
use Database\Seeders\AdminUserSeeder;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Throwable;

#[Signature('merebhub:import-woocommerce
    {file : Path to the WooCommerce CSV export}
    {--admin-email= : Admin email to create or update}
    {--admin-password= : Admin password (minimum 12 characters)}
    {--admin-first-name=Admin : Admin first name}
    {--admin-last-name=User : Admin last name}
    {--assets-path= : Directory containing downloaded asset files}
    {--skip-remote-assets : Do not fetch remote assets; keep image URLs and leave downloads for a later import}
    {--source-type=local_developer : Product source type}')]
#[Description('Import products and variations from a WooCommerce CSV export.')]
class ImportWooCommerceProducts extends Command
{
    public function handle(ImportWooCommerceCatalogAction $importer, AdminUserSeeder $adminSeeder): int
    {
        try {
            $result = $importer->handle((string) $this->argument('file'), [
                'assets_path' => $this->option('assets-path'),
                'download_remote_assets' => ! (bool) $this->option('skip-remote-assets'),
                'source_type' => (string) $this->option('source-type'),
            ]);

            $adminEmail = trim((string) ($this->option('admin-email') ?: env('MEREBHUB_ADMIN_EMAIL', '')));
            $adminPassword = (string) ($this->option('admin-password') ?: env('MEREBHUB_ADMIN_PASSWORD', ''));

            if ($adminEmail !== '' || $adminPassword !== '') {
                if ($adminEmail === '' || $adminPassword === '') {
                    $this->error('Provide both --admin-email and --admin-password, or set both MEREBHUB_ADMIN_EMAIL and MEREBHUB_ADMIN_PASSWORD.');

                    return self::FAILURE;
                }

                $adminSeeder->upsert(
                    email: $adminEmail,
                    password: $adminPassword,
                    firstName: (string) $this->option('admin-first-name'),
                    lastName: (string) $this->option('admin-last-name'),
                );
                $this->info("Admin user {$adminEmail} is ready.");
            }

            $this->info("Imported {$result['products']} products with {$result['variants']} variants and {$result['downloads']} downloadable files.");

            foreach ($result['warnings'] as $warning) {
                $this->warn($warning);
            }

            return self::SUCCESS;
        } catch (Throwable $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }
    }
}
