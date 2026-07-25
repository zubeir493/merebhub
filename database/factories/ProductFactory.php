<?php

namespace Database\Factories;

use App\Enums\ProductStatus;
use App\Models\Author;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    public function definition(): array
    {
        $name = fake()->unique()->words(2, true);

        return [
            'wc_product_id' => null,
            'author_id' => Author::factory(),
            'category' => fake()->randomElement(['Developer tools', 'Productivity', 'Business', 'Design', 'Marketing', 'Utilities']),
            'name' => Str::title($name),
            'slug' => Str::slug($name).'-'.fake()->unique()->numberBetween(1000, 9999),
            'tagline' => fake()->sentence(8),
            'description' => fake()->paragraphs(3, true),
            'price' => fake()->randomFloat(2, 5, 250),
            'compare_at_price' => fake()->optional()->randomFloat(2, 251, 350),
            'icon_path' => null,
            'cover_path' => null,
            'rating' => fake()->randomFloat(1, 3.8, 5),
            'ratings_count' => fake()->numberBetween(10, 5000),
            'weekly_sales' => fake()->numberBetween(0, 900),
            'is_featured' => false,
            'keygen_policy_id' => null,
            'status' => ProductStatus::Draft,
        ];
    }

    public function published(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ProductStatus::Published,
        ]);
    }
}
