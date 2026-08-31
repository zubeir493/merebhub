<?php

namespace Database\Factories;

use App\Models\Merchant;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Lunar\Core\Models\Brand;
use Lunar\Core\Models\ProductType;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    protected $model = Product::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'public_id' => (string) Str::ulid(),
            'product_type_id' => ProductType::factory(),
            'brand_id' => Brand::factory(),
            'status' => 'draft',
            'name' => collect(['en' => fake()->words(3, true)]),
            'description' => collect(['en' => fake()->paragraph()]),
            'short_description' => collect(['en' => fake()->sentence()]),
            'attribute_data' => collect(),
            'merchant_id' => null,
            'source_type' => 'local_developer',
            'publication_state' => 'draft',
            'support_owner' => 'merebhub',
            'official_partner' => false,
        ];
    }

    public function forMerchant(Merchant $merchant): static
    {
        return $this->state([
            'merchant_id' => $merchant->getKey(),
        ]);
    }
}
