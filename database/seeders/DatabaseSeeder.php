<?php

namespace Database\Seeders;

use App\Enums\ProductStatus;
use App\Models\Author;
use App\Models\Platform;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => env('DEMO_ADMIN_EMAIL', 'admin@merebhub.test')],
            [
                'name' => 'MerebHub Admin',
                'password' => Hash::make(env('DEMO_ADMIN_PASSWORD', 'password')),
                'is_admin' => true,
                'email_verified_at' => now(),
            ],
        );

        User::updateOrCreate(
            ['email' => 'buyer@merebhub.test'],
            [
                'name' => 'Demo Buyer',
                'password' => Hash::make('password'),
                'is_admin' => false,
                'email_verified_at' => now(),
            ],
        );

        $platforms = collect(['Web', 'Windows', 'macOS', 'Linux', 'Android', 'iOS'])
            ->mapWithKeys(fn (string $name) => [
                str($name)->slug()->toString() => Platform::firstOrCreate(
                    ['slug' => str($name)->slug()],
                    ['name' => $name],
                ),
            ]);

        $authors = collect([
            ['slug' => 'soko-labs', 'name' => 'Soko Labs', 'bio' => 'Retail software designed for the way Ethiopian shops actually work.'],
            ['slug' => 'brightbooks', 'name' => 'Brightbooks', 'bio' => 'Clear financial tools for ambitious small businesses.'],
            ['slug' => 'shipmate', 'name' => 'Shipmate', 'bio' => 'Developer infrastructure built in Addis Ababa.'],
            ['slug' => 'netsa-works', 'name' => 'Netsa Works', 'bio' => 'Creative tools for independent studios and designers.'],
            ['slug' => 'sentinel-systems', 'name' => 'Sentinel Systems', 'bio' => 'Practical security products for growing teams.'],
            ['slug' => 'rift-games', 'name' => 'Rift Games', 'bio' => 'Playful worlds and competitive games from East Africa.'],
        ])->mapWithKeys(fn (array $author) => [
            $author['slug'] => Author::updateOrCreate(
                ['slug' => $author['slug']],
                $author + ['is_public' => true],
            ),
        ]);

        $products = [
            ['Soko Inventory', 'soko-inventory', 'soko-labs', 'Business', 'Inventory that stays accurate from shelf to sale.', 3490, 4490, 'images/marketplace/soko-inventory.webp', 4.8, 1240, 386, true, ['web', 'windows']],
            ['Ledgerly', 'ledgerly', 'brightbooks', 'Business', 'Simple accounting, invoicing, and ETB reporting.', 2590, 3290, 'images/marketplace/ledgerly.webp', 4.7, 890, 301, true, ['web']],
            ['DeployMate', 'deploymate', 'shipmate', 'Developer tools', 'Ship Laravel and Node apps without deployment anxiety.', 1790, 2390, 'images/marketplace/deploymate.webp', 4.9, 620, 276, true, ['web', 'linux']],
            ['Netsa Studio', 'netsa-studio', 'netsa-works', 'Design', 'A focused visual workspace for local creative teams.', 2190, null, 'images/marketplace/netsa-studio.webp', 4.6, 470, 198, true, ['windows', 'macos']],
            ['Sentinel ET', 'sentinel-et', 'sentinel-systems', 'Security', 'Device and account security for small organizations.', 3990, 4990, 'images/marketplace/sentinel-et.webp', 4.8, 316, 164, false, ['windows', 'linux']],
            ['Rift Rally', 'rift-rally', 'rift-games', 'Games', 'Fast arcade racing inspired by the Great Rift Valley.', 890, 1190, 'images/marketplace/rift-rally.webp', 4.9, 2140, 540, true, ['windows']],
            ['Flowboard', 'flowboard', 'netsa-works', 'Productivity', 'Plan campaigns, launches, and client work in one view.', 1490, null, 'images/marketplace/flowboard.webp', 4.5, 352, 142, false, ['web']],
            ['Addis CRM', 'addis-crm', 'soko-labs', 'Marketing', 'A lightweight sales workspace with local-first workflows.', 2890, 3490, 'images/marketplace/soko-inventory.webp', 4.6, 281, 129, false, ['web']],
            ['Qene Notes', 'qene-notes', 'netsa-works', 'Productivity', 'Private notes and knowledge management across devices.', 690, null, 'images/marketplace/netsa-studio.webp', 4.7, 760, 117, false, ['windows', 'macos', 'android']],
            ['Berhan Analytics', 'berhan-analytics', 'brightbooks', 'Data & analytics', 'Turn sales and operations data into clear decisions.', 4290, 5490, 'images/marketplace/ledgerly.webp', 4.8, 198, 109, false, ['web']],
            ['Arada POS', 'arada-pos', 'soko-labs', 'Business', 'A dependable point of sale for busy counters.', 3190, null, 'images/marketplace/soko-inventory.webp', 4.6, 540, 96, false, ['windows', 'android']],
            ['Gebeta Learn', 'gebeta-learn', 'brightbooks', 'Utilities', 'Build and deliver practical internal training.', 1290, 1690, 'images/marketplace/flowboard.webp', 4.4, 208, 84, false, ['web']],
        ];

        foreach ($products as [$name, $slug, $author, $category, $tagline, $price, $compareAt, $cover, $rating, $ratings, $sales, $featured, $productPlatforms]) {
            $product = Product::updateOrCreate(
                ['slug' => $slug],
                [
                    'author_id' => $authors[$author]->id,
                    'name' => $name,
                    'category' => $category,
                    'tagline' => $tagline,
                    'description' => $tagline."\n\nBuilt for real teams, with a clear interface, dependable performance, and practical support. Purchase through Chapa and receive your personal license automatically.",
                    'price' => $price,
                    'compare_at_price' => $compareAt,
                    'cover_path' => $cover,
                    'rating' => $rating,
                    'ratings_count' => $ratings,
                    'weekly_sales' => $sales,
                    'is_featured' => $featured,
                    'status' => ProductStatus::Published,
                ],
            );

            $product->platforms()->sync(collect($productPlatforms)->map(fn (string $platform) => $platforms[$platform]->id));
        }
    }
}
