<?php

namespace Database\Seeders;

use App\Enums\AuthorRole;
use App\Enums\AuthorStatus;
use App\Enums\BillingInterval;
use App\Enums\BillingModel;
use App\Enums\FulfillmentType;
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
            ['slug' => 'soko-labs', 'name' => 'Soko Labs', 'bio' => 'Retail software designed for the way Ethiopian shops actually work.', 'is_verified' => true, 'is_featured' => true],
            ['slug' => 'brightbooks', 'name' => 'Brightbooks', 'bio' => 'Clear financial tools for ambitious small businesses.', 'is_verified' => true],
            ['slug' => 'shipmate', 'name' => 'Shipmate', 'bio' => 'Developer infrastructure built in Addis Ababa.'],
            ['slug' => 'netsa-works', 'name' => 'Netsa Works', 'bio' => 'Creative tools for independent studios and designers.'],
            ['slug' => 'sentinel-systems', 'name' => 'Sentinel Systems', 'bio' => 'Practical security products for growing teams.'],
            ['slug' => 'rift-games', 'name' => 'Rift Games', 'bio' => 'Playful worlds and competitive games from East Africa.'],
            ['slug' => 'global-software', 'name' => 'Global Software Partners', 'bio' => 'Local reseller and marketplace partner for global productivity, design, and business tools.', 'is_verified' => true],
        ])->mapWithKeys(fn (array $author) => [
            $author['slug'] => Author::updateOrCreate(
                ['slug' => $author['slug']],
                $author + [
                    'tagline' => $author['bio'],
                    'status' => AuthorStatus::Active,
                    'member_since' => now()->subYears(2),
                    'is_public' => true,
                    'average_rating' => 4.7,
                ],
            ),
        ]);

        Author::updateOrCreate(
            ['slug' => 'pending-studio'],
            [
                'name' => 'Pending Studio',
                'bio' => 'A profile awaiting marketplace approval.',
                'status' => AuthorStatus::PendingApproval,
                'is_public' => false,
            ],
        );

        Author::updateOrCreate(
            ['slug' => 'new-maker'],
            [
                'name' => 'New Maker',
                'bio' => 'An active developer preparing a first release.',
                'status' => AuthorStatus::Active,
                'is_public' => true,
            ],
        );

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
            ['Windows 11 Pro', 'windows-11-pro', 'global-software', 'Business', 'Windows 11 Pro provides business security, productivity, and management capabilities for professional devices.', 7490, 8990, 'images/marketplace/hero-built-here.webp', 4.7, 1020, 56, true, ['windows']],
            ['Microsoft 365 Business Standard', 'microsoft-365-business-standard', 'global-software', 'Productivity', 'Microsoft 365 Business Standard combines Word, Excel, PowerPoint, Outlook, Teams, and OneDrive for growing organizations.', 1590, 1790, 'images/marketplace/hero-built-here.webp', 4.6, 880, 68, true, ['web', 'windows', 'macos']],
            ['Adobe Creative Cloud Pro', 'adobe-creative-cloud-pro', 'global-software', 'Design', 'Creative Cloud Pro brings together Photoshop, Illustrator, Premiere Pro, Acrobat Pro, and more creative applications.', 1990, 2490, 'images/marketplace/hero-built-here.webp', 4.8, 720, 82, false, ['web', 'windows', 'macos']],
            ['Adobe Photoshop', 'adobe-photoshop', 'global-software', 'Design', 'Adobe Photoshop provides advanced tools for photography, graphics, compositing, and digital art.', 790, 990, 'images/marketplace/hero-built-here.webp', 4.8, 940, 115, false, ['windows', 'macos']],
            ['Adobe Acrobat Pro', 'adobe-acrobat-pro', 'global-software', 'Productivity', 'Adobe Acrobat Pro helps teams create, edit, convert, sign, and securely share PDF documents.', 690, 890, 'images/marketplace/hero-built-here.webp', 4.7, 830, 98, false, ['web', 'windows', 'macos']],
            ['DaVinci Resolve Studio', 'davinci-resolve-studio', 'global-software', 'Design', 'DaVinci Resolve Studio combines professional editing, color correction, visual effects, motion graphics, and Fairlight audio tools.', 1890, 2390, 'images/marketplace/hero-built-here.webp', 4.9, 670, 74, false, ['windows', 'macos']],
            ['Slack Pro', 'slack-pro', 'global-software', 'Productivity', 'Slack Pro organizes conversations, files, calls, and connected tools for productive teams.', 690, 890, 'images/marketplace/hero-built-here.webp', 4.5, 760, 137, false, ['web', 'windows', 'macos']],
            ['Zoom Workplace Pro', 'zoom-workplace-pro', 'global-software', 'Productivity', 'Zoom Workplace Pro brings meetings, chat, scheduling, whiteboards, and productivity tools into one workplace platform.', 790, 990, 'images/marketplace/hero-built-here.webp', 4.4, 640, 91, false, ['web', 'windows', 'macos']],
            ['Notion Business', 'notion-business', 'global-software', 'Productivity', 'Notion Business gives teams one workspace for documentation, project management, knowledge, and AI-assisted work.', 490, 690, 'images/marketplace/hero-built-here.webp', 4.6, 830, 112, false, ['web', 'windows', 'macos']],
            ['Google Workspace Business Standard', 'google-workspace-business-standard', 'global-software', 'Productivity', 'Google Workspace Business Standard brings business email, cloud storage, meetings, documents, and collaboration together.', 1290, 1490, 'images/marketplace/hero-built-here.webp', 4.7, 780, 105, false, ['web', 'windows', 'macos']],
            ['Canva Pro', 'canva-pro', 'global-software', 'Design', 'Canva Pro helps individuals and teams create presentations, social content, videos, and branded designs.', 390, 490, 'images/marketplace/hero-built-here.webp', 4.5, 1020, 96, false, ['web', 'windows', 'macos']],
            ['Figma Professional', 'figma-professional', 'global-software', 'Design', 'Figma Professional supports collaborative interface design, prototyping, design systems, and developer handoff.', 890, 990, 'images/marketplace/hero-built-here.webp', 4.8, 720, 108, false, ['web', 'windows', 'macos']],
            ['Dropbox Essentials', 'dropbox-essentials', 'global-software', 'Productivity', 'Dropbox Essentials provides cloud storage, file recovery, sharing, signature, and document workflow features.', 450, 590, 'images/marketplace/hero-built-here.webp', 4.6, 690, 99, false, ['web', 'windows', 'macos']],
            ['Asana Starter', 'asana-starter', 'global-software', 'Productivity', 'Asana Starter helps teams plan projects, assign work, track progress, and coordinate deadlines.', 390, 490, 'images/marketplace/hero-built-here.webp', 4.5, 810, 88, false, ['web', 'windows', 'macos']],
            ['monday.com Standard', 'monday-com-standard', 'global-software', 'Productivity', 'monday.com Standard provides boards, automations, dashboards, integrations, and collaborative work management.', 690, 890, 'images/marketplace/hero-built-here.webp', 4.6, 760, 95, false, ['web', 'windows', 'macos']],
            ['Jira Standard', 'jira-standard', 'global-software', 'Productivity', 'Jira Standard helps software and business teams plan work, track issues, and deliver projects.', 690, 890, 'images/marketplace/hero-built-here.webp', 4.7, 710, 89, false, ['web', 'windows', 'macos']],
            ['Todoist Pro', 'todoist-pro', 'global-software', 'Productivity', 'Todoist Pro adds advanced reminders, calendar layouts, filters, and productivity history for organized work.', 290, 390, 'images/marketplace/hero-built-here.webp', 4.4, 890, 94, false, ['web', 'windows', 'macos']],
            ['GitHub Copilot Business', 'github-copilot-business', 'global-software', 'Developer tools', 'GitHub Copilot Business provides organization-managed AI coding assistance across supported editors and GitHub workflows.', 1490, 1790, 'images/marketplace/hero-built-here.webp', 4.8, 660, 72, false, ['web', 'windows', 'macos']],
            ['JetBrains All Products Pack', 'jetbrains-all-products-pack', 'global-software', 'Developer tools', 'JetBrains All Products Pack includes professional IDEs and development tools for many languages and platforms.', 2350, 2690, 'images/marketplace/hero-built-here.webp', 4.9, 560, 81, false, ['web', 'windows', 'macos']],
            ['Autodesk AutoCAD', 'autodesk-autocad', 'global-software', 'Design', 'Autodesk AutoCAD provides precise 2D drafting, 3D design, automation, and collaboration tools for professional workflows.', 5490, 6490, 'images/marketplace/hero-built-here.webp', 4.7, 420, 63, false, ['windows']],
            ['Packledge MIS', 'packledge-mis', 'global-software', 'Business', 'Track operations, reporting, users, and daily business activity from one practical management workspace.', 2390, 2990, 'images/marketplace/hero-built-here.webp', 4.6, 510, 71, false, ['web', 'windows']],
        ];

        $productPlans = [
            'windows-11-pro' => [
                [
                    'slug' => 'personal',
                    'name' => 'One-time',
                    'description' => 'One-time Windows 11 Pro license with business security, productivity, and management features.',
                    'price_minor' => 1099000,
                    'currency' => 'ETB',
                    'billing_model' => BillingModel::OneTime,
                    'license_type' => 'perpetual',
                    'activation_limit' => 1,
                    'support_duration_days' => 365,
                    'update_duration_days' => 365,
                    'fulfillment_type' => FulfillmentType::LicenseKey,
                    'is_active' => true,
                ],
                [
                    'slug' => 'monthly',
                    'name' => 'Monthly',
                    'description' => 'Manual monthly subscription for Windows 11 Pro.',
                    'price_minor' => 109000,
                    'currency' => 'ETB',
                    'billing_model' => BillingModel::ManualSubscription,
                    'billing_interval' => BillingInterval::Monthly,
                    'license_type' => 'subscription',
                    'activation_limit' => 1,
                    'support_duration_days' => 30,
                    'update_duration_days' => 30,
                    'fulfillment_type' => FulfillmentType::LicenseKey,
                    'is_active' => true,
                ],
            ],
            'microsoft-365-business-standard' => [
                [
                    'slug' => 'monthly',
                    'name' => 'Monthly',
                    'description' => 'Manual monthly subscription for Microsoft 365 Business Standard.',
                    'price_minor' => 159000,
                    'currency' => 'ETB',
                    'billing_model' => BillingModel::ManualSubscription,
                    'billing_interval' => BillingInterval::Monthly,
                    'license_type' => 'subscription',
                    'activation_limit' => 10,
                    'support_duration_days' => 30,
                    'update_duration_days' => 30,
                    'fulfillment_type' => FulfillmentType::LicenseKey,
                    'is_active' => true,
                ],
            ],
            'adobe-creative-cloud-pro' => [
                [
                    'slug' => 'monthly',
                    'name' => 'Monthly',
                    'description' => 'Manual monthly subscription for Adobe Creative Cloud Pro.',
                    'price_minor' => 229000,
                    'currency' => 'ETB',
                    'billing_model' => BillingModel::ManualSubscription,
                    'billing_interval' => BillingInterval::Monthly,
                    'license_type' => 'subscription',
                    'activation_limit' => 3,
                    'support_duration_days' => 30,
                    'update_duration_days' => 30,
                    'fulfillment_type' => FulfillmentType::LicenseKey,
                    'is_active' => true,
                ],
            ],
            'adobe-photoshop' => [
                [
                    'slug' => 'monthly',
                    'name' => 'Monthly',
                    'description' => 'Manual monthly subscription for Adobe Photoshop.',
                    'price_minor' => 99000,
                    'currency' => 'ETB',
                    'billing_model' => BillingModel::ManualSubscription,
                    'billing_interval' => BillingInterval::Monthly,
                    'license_type' => 'subscription',
                    'activation_limit' => 1,
                    'support_duration_days' => 30,
                    'update_duration_days' => 30,
                    'fulfillment_type' => FulfillmentType::LicenseKey,
                    'is_active' => true,
                ],
            ],
            'adobe-acrobat-pro' => [
                [
                    'slug' => 'monthly',
                    'name' => 'Monthly',
                    'description' => 'Manual monthly subscription for Adobe Acrobat Pro.',
                    'price_minor' => 69000,
                    'currency' => 'ETB',
                    'billing_model' => BillingModel::ManualSubscription,
                    'billing_interval' => BillingInterval::Monthly,
                    'license_type' => 'subscription',
                    'activation_limit' => 1,
                    'support_duration_days' => 30,
                    'update_duration_days' => 30,
                    'fulfillment_type' => FulfillmentType::LicenseKey,
                    'is_active' => true,
                ],
            ],
            'davinci-resolve-studio' => [
                [
                    'slug' => 'personal',
                    'name' => 'One-time',
                    'description' => 'One-time DaVinci Resolve Studio license for professional editing and color workflows.',
                    'price_minor' => 199000,
                    'currency' => 'ETB',
                    'billing_model' => BillingModel::OneTime,
                    'license_type' => 'perpetual',
                    'activation_limit' => 3,
                    'support_duration_days' => 365,
                    'update_duration_days' => 365,
                    'fulfillment_type' => FulfillmentType::LicenseKey,
                    'is_active' => true,
                ],
            ],
            'slack-pro' => [
                [
                    'slug' => 'monthly',
                    'name' => 'Monthly',
                    'description' => 'Manual monthly subscription for Slack Pro.',
                    'price_minor' => 59000,
                    'currency' => 'ETB',
                    'billing_model' => BillingModel::ManualSubscription,
                    'billing_interval' => BillingInterval::Monthly,
                    'license_type' => 'subscription',
                    'activation_limit' => 15,
                    'support_duration_days' => 30,
                    'update_duration_days' => 30,
                    'fulfillment_type' => FulfillmentType::LicenseKey,
                    'is_active' => true,
                ],
            ],
            'zoom-workplace-pro' => [
                [
                    'slug' => 'monthly',
                    'name' => 'Monthly',
                    'description' => 'Manual monthly subscription for Zoom Workplace Pro.',
                    'price_minor' => 79000,
                    'currency' => 'ETB',
                    'billing_model' => BillingModel::ManualSubscription,
                    'billing_interval' => BillingInterval::Monthly,
                    'license_type' => 'subscription',
                    'activation_limit' => 15,
                    'support_duration_days' => 30,
                    'update_duration_days' => 30,
                    'fulfillment_type' => FulfillmentType::LicenseKey,
                    'is_active' => true,
                ],
            ],
            'notion-business' => [
                [
                    'slug' => 'monthly',
                    'name' => 'Monthly',
                    'description' => 'Manual monthly subscription for Notion Business.',
                    'price_minor' => 59000,
                    'currency' => 'ETB',
                    'billing_model' => BillingModel::ManualSubscription,
                    'billing_interval' => BillingInterval::Monthly,
                    'license_type' => 'subscription',
                    'activation_limit' => 10,
                    'support_duration_days' => 30,
                    'update_duration_days' => 30,
                    'fulfillment_type' => FulfillmentType::LicenseKey,
                    'is_active' => true,
                ],
            ],
            'google-workspace-business-standard' => [
                [
                    'slug' => 'monthly',
                    'name' => 'Monthly',
                    'description' => 'Manual monthly subscription for Google Workspace Business Standard.',
                    'price_minor' => 129000,
                    'currency' => 'ETB',
                    'billing_model' => BillingModel::ManualSubscription,
                    'billing_interval' => BillingInterval::Monthly,
                    'license_type' => 'subscription',
                    'activation_limit' => 10,
                    'support_duration_days' => 30,
                    'update_duration_days' => 30,
                    'fulfillment_type' => FulfillmentType::LicenseKey,
                    'is_active' => true,
                ],
            ],
            'canva-pro' => [
                [
                    'slug' => 'monthly',
                    'name' => 'Monthly',
                    'description' => 'Manual monthly subscription for Canva Pro.',
                    'price_minor' => 49000,
                    'currency' => 'ETB',
                    'billing_model' => BillingModel::ManualSubscription,
                    'billing_interval' => BillingInterval::Monthly,
                    'license_type' => 'subscription',
                    'activation_limit' => 5,
                    'support_duration_days' => 30,
                    'update_duration_days' => 30,
                    'fulfillment_type' => FulfillmentType::LicenseKey,
                    'is_active' => true,
                ],
            ],
            'figma-professional' => [
                [
                    'slug' => 'monthly',
                    'name' => 'Monthly',
                    'description' => 'Manual monthly subscription for Figma Professional.',
                    'price_minor' => 89000,
                    'currency' => 'ETB',
                    'billing_model' => BillingModel::ManualSubscription,
                    'billing_interval' => BillingInterval::Monthly,
                    'license_type' => 'subscription',
                    'activation_limit' => 5,
                    'support_duration_days' => 30,
                    'update_duration_days' => 30,
                    'fulfillment_type' => FulfillmentType::LicenseKey,
                    'is_active' => true,
                ],
            ],
            'dropbox-essentials' => [
                [
                    'slug' => 'monthly',
                    'name' => 'Monthly',
                    'description' => 'Manual monthly subscription for Dropbox Essentials.',
                    'price_minor' => 49000,
                    'currency' => 'ETB',
                    'billing_model' => BillingModel::ManualSubscription,
                    'billing_interval' => BillingInterval::Monthly,
                    'license_type' => 'subscription',
                    'activation_limit' => 5,
                    'support_duration_days' => 30,
                    'update_duration_days' => 30,
                    'fulfillment_type' => FulfillmentType::LicenseKey,
                    'is_active' => true,
                ],
            ],
            'asana-starter' => [
                [
                    'slug' => 'monthly',
                    'name' => 'Monthly',
                    'description' => 'Manual monthly subscription for Asana Starter.',
                    'price_minor' => 39000,
                    'currency' => 'ETB',
                    'billing_model' => BillingModel::ManualSubscription,
                    'billing_interval' => BillingInterval::Monthly,
                    'license_type' => 'subscription',
                    'activation_limit' => 10,
                    'support_duration_days' => 30,
                    'update_duration_days' => 30,
                    'fulfillment_type' => FulfillmentType::LicenseKey,
                    'is_active' => true,
                ],
            ],
            'monday-com-standard' => [
                [
                    'slug' => 'monthly',
                    'name' => 'Monthly',
                    'description' => 'Manual monthly subscription for monday.com Standard.',
                    'price_minor' => 79000,
                    'currency' => 'ETB',
                    'billing_model' => BillingModel::ManualSubscription,
                    'billing_interval' => BillingInterval::Monthly,
                    'license_type' => 'subscription',
                    'activation_limit' => 10,
                    'support_duration_days' => 30,
                    'update_duration_days' => 30,
                    'fulfillment_type' => FulfillmentType::LicenseKey,
                    'is_active' => true,
                ],
            ],
            'jira-standard' => [
                [
                    'slug' => 'monthly',
                    'name' => 'Monthly',
                    'description' => 'Manual monthly subscription for Jira Standard.',
                    'price_minor' => 69000,
                    'currency' => 'ETB',
                    'billing_model' => BillingModel::ManualSubscription,
                    'billing_interval' => BillingInterval::Monthly,
                    'license_type' => 'subscription',
                    'activation_limit' => 10,
                    'support_duration_days' => 30,
                    'update_duration_days' => 30,
                    'fulfillment_type' => FulfillmentType::LicenseKey,
                    'is_active' => true,
                ],
            ],
            'todoist-pro' => [
                [
                    'slug' => 'monthly',
                    'name' => 'Monthly',
                    'description' => 'Manual monthly subscription for Todoist Pro.',
                    'price_minor' => 29000,
                    'currency' => 'ETB',
                    'billing_model' => BillingModel::ManualSubscription,
                    'billing_interval' => BillingInterval::Monthly,
                    'license_type' => 'subscription',
                    'activation_limit' => 5,
                    'support_duration_days' => 30,
                    'update_duration_days' => 30,
                    'fulfillment_type' => FulfillmentType::LicenseKey,
                    'is_active' => true,
                ],
            ],
            'github-copilot-business' => [
                [
                    'slug' => 'monthly',
                    'name' => 'Monthly',
                    'description' => 'Manual monthly subscription for GitHub Copilot Business.',
                    'price_minor' => 179000,
                    'currency' => 'ETB',
                    'billing_model' => BillingModel::ManualSubscription,
                    'billing_interval' => BillingInterval::Monthly,
                    'license_type' => 'subscription',
                    'activation_limit' => 20,
                    'support_duration_days' => 30,
                    'update_duration_days' => 30,
                    'fulfillment_type' => FulfillmentType::LicenseKey,
                    'is_active' => true,
                ],
            ],
            'jetbrains-all-products-pack' => [
                [
                    'slug' => 'monthly',
                    'name' => 'Monthly',
                    'description' => 'Manual monthly subscription for the JetBrains All Products Pack.',
                    'price_minor' => 239000,
                    'currency' => 'ETB',
                    'billing_model' => BillingModel::ManualSubscription,
                    'billing_interval' => BillingInterval::Monthly,
                    'license_type' => 'subscription',
                    'activation_limit' => 5,
                    'support_duration_days' => 30,
                    'update_duration_days' => 30,
                    'fulfillment_type' => FulfillmentType::LicenseKey,
                    'is_active' => true,
                ],
                [
                    'slug' => 'yearly',
                    'name' => 'Yearly',
                    'description' => 'Manual annual subscription for the JetBrains All Products Pack.',
                    'price_minor' => 2390000,
                    'currency' => 'ETB',
                    'billing_model' => BillingModel::ManualSubscription,
                    'billing_interval' => BillingInterval::Yearly,
                    'license_type' => 'subscription',
                    'activation_limit' => 5,
                    'support_duration_days' => 365,
                    'update_duration_days' => 365,
                    'fulfillment_type' => FulfillmentType::LicenseKey,
                    'is_active' => true,
                ],
            ],
            'autodesk-autocad' => [
                [
                    'slug' => 'personal',
                    'name' => 'One-time',
                    'description' => 'One-time Autodesk AutoCAD license for precise drafting and 3D design.',
                    'price_minor' => 549000,
                    'currency' => 'ETB',
                    'billing_model' => BillingModel::OneTime,
                    'license_type' => 'perpetual',
                    'activation_limit' => 1,
                    'support_duration_days' => 365,
                    'update_duration_days' => 365,
                    'fulfillment_type' => FulfillmentType::LicenseKey,
                    'is_active' => true,
                ],
            ],
            'packledge-mis' => [
                [
                    'slug' => 'personal',
                    'name' => 'One-time',
                    'description' => 'One-time Packledge MIS license for business operations and reporting.',
                    'price_minor' => 239000,
                    'currency' => 'ETB',
                    'billing_model' => BillingModel::OneTime,
                    'license_type' => 'perpetual',
                    'activation_limit' => 5,
                    'support_duration_days' => 365,
                    'update_duration_days' => 365,
                    'fulfillment_type' => FulfillmentType::LicenseKey,
                    'is_active' => true,
                ],
            ],
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
            $plans = $productPlans[$slug] ?? [[
                'slug' => 'personal',
                'name' => 'Personal',
                'description' => 'A single-user license with updates and support.',
                'price_minor' => $price * 100,
                'currency' => 'ETB',
                'billing_model' => BillingModel::OneTime,
                'license_type' => 'perpetual',
                'activation_limit' => 1,
                'support_duration_days' => 365,
                'update_duration_days' => 365,
                'fulfillment_type' => FulfillmentType::LicenseKey,
                'is_active' => true,
            ]];

            foreach ($plans as $plan) {
                $product->plans()->updateOrCreate(
                    ['slug' => $plan['slug']],
                    $plan,
                );
            }
            $product->authors()->syncWithoutDetaching([
                $authors[$author]->id => [
                    'role' => AuthorRole::PrimaryDeveloper->value,
                    'is_primary' => true,
                    'is_publicly_displayed' => true,
                    'can_manage_product' => true,
                    'revenue_share_basis_points' => 7000,
                    'sort_order' => 0,
                ],
            ]);
        }

        $productsBySlug = Product::whereIn('slug', ['netsa-studio'])->get()->keyBy('slug');
        $productsBySlug['netsa-studio']?->authors()->syncWithoutDetaching([
            $authors['brightbooks']->id => [
                'role' => AuthorRole::Contributor->value,
                'is_primary' => false,
                'is_publicly_displayed' => true,
                'can_manage_product' => false,
                'revenue_share_basis_points' => 0,
                'sort_order' => 1,
            ],
        ]);
    }
}
