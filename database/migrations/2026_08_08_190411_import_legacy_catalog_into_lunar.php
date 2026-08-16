<?php

use App\Models\Author;
use App\Models\Product;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Lunar\Core\FieldTypes\TranslatedText;
use Lunar\Core\Models\Attribute;
use Lunar\Core\Models\AttributeGroup;
use Lunar\Core\Models\Channel;
use Lunar\Core\Models\Collection;
use Lunar\Core\Models\CollectionGroup;
use Lunar\Core\Models\Country;
use Lunar\Core\Models\Currency;
use Lunar\Core\Models\CustomerGroup;
use Lunar\Core\Models\Language;
use Lunar\Core\Models\ProductType;
use Lunar\Core\Models\TaxClass;
use Lunar\Core\Models\TaxZone;
use Lunar\Core\Models\Url;

return new class extends Migration
{
    public function up(): void
    {
        $language = Language::firstOrCreate(['code' => 'en'], [
            'name' => 'English',
            'default' => true,
        ]);
        $currency = Currency::firstOrCreate(['code' => 'ETB'], [
            'name' => 'Ethiopian Birr',
            'exchange_rate' => 1,
            'decimal_places' => 2,
            'default' => true,
            'enabled' => true,
        ]);
        Channel::firstOrCreate(['handle' => 'webstore'], [
            'name' => 'MerebHub',
            'default' => true,
            'url' => config('app.url'),
        ]);
        CustomerGroup::firstOrCreate(['handle' => 'retail'], [
            'name' => 'Retail',
            'default' => true,
        ]);
        $collectionGroup = CollectionGroup::firstOrCreate(['handle' => 'main'], [
            'name' => 'Main',
        ]);
        $taxClass = TaxClass::firstOrCreate(['name' => 'Digital products'], [
            'default' => true,
        ]);
        $country = Country::firstOrCreate(['iso3' => 'ETH'], [
            'name' => 'Ethiopia',
            'iso2' => 'ET',
            'phonecode' => '251',
            'capital' => 'Addis Ababa',
            'currency' => 'ETB',
            'native' => 'Ethiopia',
            'emoji' => '🇪🇹',
            'emoji_u' => 'U+1F1EA U+1F1F9',
        ]);
        $taxZone = TaxZone::firstOrCreate(['name' => 'Ethiopia'], [
            'zone_type' => 'country',
            'default' => true,
            'active' => true,
        ]);
        $taxZone->countries()->firstOrCreate(['country_id' => $country->id]);

        $productGroup = AttributeGroup::firstOrCreate([
            'handle' => 'storefront',
        ], [
            'name' => 'Storefront',
            'position' => 1,
        ]);

        $attributeHandles = [
            'name' => ['Name', true],
            'tagline' => ['Tagline', false],
            'description' => ['Description', false],
            'category' => ['Category', false],
            'platform' => ['Platform', false],
            'rating' => ['Rating', false],
            'ratings_count' => ['Ratings count', false],
            'is_featured' => ['Featured', false],
            'cover_url' => ['Cover image', false],
            'legacy_id' => ['Legacy ID', false],
        ];
        $attributes = collect();

        foreach ($attributeHandles as $handle => [$name, $required]) {
            $attributes->push(Attribute::firstOrCreate([
                'handle' => $handle,
            ], [
                'attribute_group_id' => $productGroup->id,
                'position' => $attributes->count() + 1,
                'name' => $name,
                'type' => TranslatedText::class,
                'required' => $required,
                'configuration' => ['richtext' => $handle === 'description'],
                'system' => in_array($handle, ['name', 'description'], true),
            ]));
        }

        foreach ($attributes as $attribute) {
            $attribute->models()->firstOrCreate(['model_type' => Product::morphName()]);
        }

        $productType = ProductType::firstOrCreate(['name' => 'Software']);
        $productType->mappedAttributes()->syncWithoutDetaching($attributes->pluck('id'));

        if (! Schema::hasTable('products') || Product::query()->exists()) {
            return;
        }

        $translated = fn (mixed $value): TranslatedText => new TranslatedText(collect([
            'en' => (string) ($value ?? ''),
        ]));
        $brands = [];

        foreach (DB::table('authors')->orderBy('id')->get() as $legacyAuthor) {
            $author = Author::create([
                'name' => $legacyAuthor->name,
                'attribute_data' => collect([
                    'tagline' => $translated($legacyAuthor->tagline ?? ''),
                    'bio' => $translated($legacyAuthor->bio ?? ''),
                    'is_verified' => $translated($legacyAuthor->is_verified ?? false),
                    'average_rating' => $translated($legacyAuthor->average_rating ?? 0),
                    'public_sales_count' => $translated($legacyAuthor->public_sales_count ?? 0),
                    'website_url' => $translated($legacyAuthor->website_url ?? ''),
                    'support_url' => $translated($legacyAuthor->support_url ?? ''),
                    'location' => $translated($legacyAuthor->location ?? ''),
                    'avatar_url' => $translated($legacyAuthor->avatar_path ?? ''),
                    'cover_url' => $translated($legacyAuthor->cover_path ?? ''),
                ]),
            ]);
            $brands[$legacyAuthor->id] = $author->id;
            Url::create([
                'language_id' => $language->id,
                'element_type' => (new Author)->getMorphClass(),
                'element_id' => $author->id,
                'slug' => $legacyAuthor->slug,
                'default' => true,
            ]);
        }

        $collections = [];

        foreach (DB::table('products')->orderBy('id')->get() as $legacyProduct) {
            $platforms = DB::table('platform_product')
                ->join('platforms', 'platforms.id', '=', 'platform_product.platform_id')
                ->where('platform_product.product_id', $legacyProduct->id)
                ->orderBy('platforms.name')
                ->pluck('platforms.name')
                ->join(', ');
            $product = Product::create([
                'product_type_id' => $productType->id,
                'brand_id' => $brands[$legacyProduct->author_id] ?? null,
                'status' => $legacyProduct->status === 'published' ? 'published' : 'draft',
                'name' => collect(['en' => $legacyProduct->name]),
                'description' => collect(['en' => $legacyProduct->description]),
                'attribute_data' => collect([
                    'tagline' => $translated($legacyProduct->tagline ?? ''),
                    'category' => $translated($legacyProduct->category),
                    'platform' => $translated($platforms),
                    'rating' => $translated($legacyProduct->rating ?? 0),
                    'ratings_count' => $translated($legacyProduct->ratings_count ?? 0),
                    'is_featured' => $translated($legacyProduct->is_featured ?? false),
                    'cover_url' => $translated($legacyProduct->cover_path ?? ''),
                    'legacy_id' => $translated($legacyProduct->id),
                ]),
            ]);
            $variant = $product->variants()->create([
                'tax_class_id' => $taxClass->id,
                'sku' => 'MEREB-'.$legacyProduct->id,
                'unit_quantity' => 1,
                'shippable' => false,
                'stock' => 0,
                'selling_policy' => 'always',
            ]);
            $variant->prices()->create([
                'currency_id' => $currency->id,
                'price' => (int) round(((float) $legacyProduct->price) * 100),
                'list_price' => $legacyProduct->compare_at_price
                    ? (int) round(((float) $legacyProduct->compare_at_price) * 100)
                    : null,
                'min_quantity' => 1,
            ]);
            Url::create([
                'language_id' => $language->id,
                'element_type' => (new Product)->getMorphClass(),
                'element_id' => $product->id,
                'slug' => $legacyProduct->slug,
                'default' => true,
            ]);

            $category = $legacyProduct->category ?: 'Software';
            $collection = $collections[$category] ??= Collection::create([
                'collection_group_id' => $collectionGroup->id,
                'attribute_data' => collect(['name' => $translated($category)]),
            ]);
            $product->collections()->syncWithoutDetaching([$collection->id]);

            if (! $collection->defaultUrl) {
                Url::create([
                    'language_id' => $language->id,
                    'element_type' => Collection::morphName(),
                    'element_id' => $collection->id,
                    'slug' => Str::slug($category),
                    'default' => true,
                ]);
            }
        }
    }

    public function down(): void
    {
        // Imported commerce records are intentionally retained on rollback.
    }
};
