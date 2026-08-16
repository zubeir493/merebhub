<?php

namespace Database\Seeders;

use App\Models\Author;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Lunar\Core\FieldTypes\TranslatedText;
use Lunar\Core\Models\Channel;
use Lunar\Core\Models\Currency;
use Lunar\Core\Models\Customer;
use Lunar\Core\Models\CustomerGroup;
use Lunar\Core\Models\Language;
use Lunar\Core\Models\ProductType;
use Lunar\Core\Models\TaxClass;
use Lunar\Core\Models\Url;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::updateOrCreate(
            ['email' => 'buyer@merebhub.test'],
            [
                'name' => 'Demo Buyer',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ],
        );
        $customer = Customer::firstOrCreate([
            'first_name' => 'Demo',
            'last_name' => 'Buyer',
        ]);
        $customer->users()->syncWithoutDetaching([$user->id]);

        $channel = Channel::query()->where('handle', 'webstore')->firstOrFail();
        $customerGroup = CustomerGroup::query()->where('handle', 'retail')->firstOrFail();

        if (Url::query()->where('slug', 'soko-inventory')->exists()) {
            Product::query()->each(function (Product $product) use ($channel, $customerGroup): void {
                $product->channels()->syncWithoutDetaching([$channel->id => ['enabled' => true]]);
                DB::table('lunar_channelables')
                    ->where('channelable_id', $product->id)
                    ->where('channelable_type', Product::class)
                    ->update(['channelable_type' => 'product']);
                $product->customerGroups()->syncWithoutDetaching([$customerGroup->id => [
                    'purchasable' => true,
                    'visible' => true,
                    'enabled' => true,
                ]]);
            });

            return;
        }

        $language = Language::query()->where('code', 'en')->firstOrFail();
        $currency = Currency::query()->where('code', 'ETB')->firstOrFail();
        $productType = ProductType::query()->where('name', 'Software')->firstOrFail();
        $taxClass = TaxClass::query()->where('default', true)->firstOrFail();
        $translated = fn (mixed $value): TranslatedText => new TranslatedText(collect(['en' => (string) $value]));
        $author = Author::create([
            'name' => 'Soko Labs',
            'attribute_data' => collect([
                'tagline' => $translated('Ethiopian software for practical businesses.'),
                'bio' => $translated('Retail software designed for the way Ethiopian shops work.'),
                'is_verified' => $translated(true),
                'average_rating' => $translated(4.8),
            ]),
        ]);
        Url::create([
            'language_id' => $language->id,
            'element_type' => (new Author)->getMorphClass(),
            'element_id' => $author->id,
            'slug' => 'soko-labs',
            'default' => true,
        ]);

        foreach ([
            ['Soko Inventory', 'soko-inventory', 'Business', 'Inventory that stays accurate from shelf to sale.', 349000, 449000, 'images/marketplace/soko-inventory.webp', 'Web, Windows'],
            ['Ledgerly', 'ledgerly', 'Business', 'Simple accounting, invoicing, and ETB reporting.', 259000, 329000, 'images/marketplace/ledgerly.webp', 'Web'],
            ['DeployMate', 'deploymate', 'Developer tools', 'Ship Laravel apps without deployment anxiety.', 179000, 239000, 'images/marketplace/deploymate.webp', 'Web, Linux'],
        ] as [$name, $slug, $category, $tagline, $price, $comparePrice, $cover, $platform]) {
            $product = Product::create([
                'product_type_id' => $productType->id,
                'brand_id' => $author->id,
                'status' => 'published',
                'name' => collect(['en' => $name]),
                'description' => collect(['en' => $tagline]),
                'attribute_data' => collect([
                    'tagline' => $translated($tagline),
                    'category' => $translated($category),
                    'platform' => $translated($platform),
                    'rating' => $translated(4.8),
                    'ratings_count' => $translated(120),
                    'is_featured' => $translated(true),
                    'cover_url' => $translated($cover),
                ]),
            ]);
            $product->channels()->syncWithoutDetaching([$channel->id => ['enabled' => true]]);
            DB::table('lunar_channelables')
                ->where('channelable_id', $product->id)
                ->where('channelable_type', Product::class)
                ->update(['channelable_type' => 'product']);
            $product->customerGroups()->syncWithoutDetaching([$customerGroup->id => [
                'purchasable' => true,
                'visible' => true,
                'enabled' => true,
            ]]);
            $variant = $product->variants()->create([
                'tax_class_id' => $taxClass->id,
                'sku' => strtoupper($slug),
                'shippable' => false,
                'selling_policy' => 'always',
            ]);
            $variant->prices()->create([
                'currency_id' => $currency->id,
                'price' => $price,
                'list_price' => $comparePrice,
                'min_quantity' => 1,
            ]);
            Url::create([
                'language_id' => $language->id,
                'element_type' => (new Product)->getMorphClass(),
                'element_id' => $product->id,
                'slug' => $slug,
                'default' => true,
            ]);
        }
    }
}
