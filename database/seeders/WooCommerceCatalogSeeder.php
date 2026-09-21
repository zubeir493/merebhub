<?php

namespace Database\Seeders;

use App\Domain\Catalog\Actions\ImportWooCommerceCatalogAction;
use Illuminate\Database\Seeder;

class WooCommerceCatalogSeeder extends Seeder
{
    public function run(): void
    {
        $result = app(ImportWooCommerceCatalogAction::class)->handleRows(
            $this->rows(),
            [
                'download_remote_assets' => filter_var(env('MEREBHUB_IMPORT_REMOTE_ASSETS', true), FILTER_VALIDATE_BOOLEAN),
                'source_type' => 'local_developer',
            ],
        );

        $this->call(AdminUserSeeder::class);

        foreach ($result['warnings'] as $warning) {
            $this->command?->warn($warning);
        }

        $this->command?->info("Seeded {$result['products']} products with {$result['variants']} variants and {$result['downloads']} downloadable files.");
    }

    /** @return list<array<string, string>> */
    private function rows(): array
    {
        $products = [
            [
                'id' => '118', 'sku' => 'PIXELFOUNDRY-DESIGN', 'name' => 'PixelFoundry Design Suite',
                'image' => 'https://merebhub.com/wp-content/uploads/2026/07/netsa-studio.webp',
                'tiers' => [
                    ['id' => '127', 'sku' => 'PIXELFOUNDRY-DESIGN-STUDIO-TEAM', 'name' => 'Studio Team', 'price' => '199.00'],
                    ['id' => '128', 'sku' => 'PIXELFOUNDRY-DESIGN-CREATOR', 'name' => 'Creator', 'price' => '59.00'],
                ],
            ],
            [
                'id' => '119', 'sku' => 'DEPLOYDOCK-DEVOPS', 'name' => 'DeployDock DevOps Panel',
                'image' => 'https://merebhub.com/wp-content/uploads/2026/07/deploymate.webp',
                'tiers' => [
                    ['id' => '129', 'sku' => 'DEPLOYDOCK-DEVOPS-ENTERPRISE', 'name' => 'Enterprise', 'price' => '899.00'],
                    ['id' => '130', 'sku' => 'DEPLOYDOCK-DEVOPS-AGENCY', 'name' => 'Agency', 'price' => '299.00'],
                    ['id' => '131', 'sku' => 'DEPLOYDOCK-DEVOPS-DEVELOPER', 'name' => 'Developer', 'price' => '79.00'],
                ],
            ],
            [
                'id' => '120', 'sku' => 'WINCORE-11-PRO', 'name' => 'WinCore 11 Pro License',
                'image' => 'https://merebhub.com/wp-content/uploads/2026/07/win11.jpg',
                'tiers' => [
                    ['id' => '132', 'sku' => 'WINCORE-11-PRO-BUSINESS-PACK', 'name' => 'Business Pack', 'price' => '999.00'],
                    ['id' => '133', 'sku' => 'WINCORE-11-PRO-SINGLE-DEVICE', 'name' => 'Single Device', 'price' => '149.00'],
                ],
            ],
            [
                'id' => '121', 'sku' => 'OFFICENOVA-SUITE', 'name' => 'OfficeNova Productivity Suite',
                'image' => 'https://merebhub.com/wp-content/uploads/2026/07/ledgerly.webp',
                'tiers' => [
                    ['id' => '134', 'sku' => 'OFFICENOVA-SUITE-ENTERPRISE', 'name' => 'Enterprise', 'price' => '799.00'],
                    ['id' => '135', 'sku' => 'OFFICENOVA-SUITE-BUSINESS', 'name' => 'Business', 'price' => '249.00'],
                    ['id' => '136', 'sku' => 'OFFICENOVA-SUITE-HOME', 'name' => 'Home', 'price' => '79.00'],
                ],
            ],
            [
                'id' => '122', 'sku' => 'HELPPILOT-SUPPORT', 'name' => 'HelpPilot Remote Support',
                'image' => 'https://merebhub.com/wp-content/uploads/2026/07/flowboard.webp',
                'tiers' => [
                    ['id' => '137', 'sku' => 'HELPPILOT-SUPPORT-ENTERPRISE', 'name' => 'Enterprise', 'price' => '999.00', 'downloadable' => true],
                    ['id' => '138', 'sku' => 'HELPPILOT-SUPPORT-SUPPORT-TEAM', 'name' => 'Support Team', 'price' => '299.00', 'downloadable' => true],
                    ['id' => '139', 'sku' => 'HELPPILOT-SUPPORT-TECHNICIAN', 'name' => 'Technician', 'price' => '69.00', 'downloadable' => true],
                ],
            ],
        ];
        $rows = [];

        foreach ($products as $product) {
            $rows[] = [
                'ID' => $product['id'], 'Type' => 'variable', 'SKU' => $product['sku'], 'Name' => $product['name'],
                'Published' => '1', 'Categories' => 'Software', 'Images' => $product['image'],
                'Short description' => "Digital license for {$product['name']}.",
                'Description' => 'Software license with online activation, updates, and account delivery after purchase.',
            ];

            foreach ($product['tiers'] as $tier) {
                $downloadable = ! empty($tier['downloadable']);
                $rows[] = [
                    'ID' => $tier['id'], 'Type' => $downloadable ? 'variation, downloadable' : 'variation',
                    'SKU' => $tier['sku'], 'Name' => $product['name'].' - '.$tier['name'], 'Published' => '1',
                    'Parent' => $product['sku'], 'Attribute 1 value(s)' => $tier['name'],
                    'Regular price' => $tier['price'], 'Sale price' => '',
                    'Download 1 name' => $downloadable ? 'merebhub-demo-app-1-clock-re3gmy.zip' : '',
                    'Download 1 URL' => $downloadable
                        ? 'https://merebhub.com/wp-content/uploads/woocommerce_uploads/2026/07/merebhub-demo-app-1-clock-re3gmy.zip'
                        : '',
                ];
            }
        }

        return $rows;
    }
}
