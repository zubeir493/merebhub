<?php

namespace Database\Factories;

use App\Models\AppVersion;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AppVersion>
 */
class AppVersionFactory extends Factory
{
    public function definition(): array
    {
        return [
            'product_id' => Product::factory(),
            'version_number' => fake()->semver(),
            'file_path' => 'builds/'.fake()->uuid().'.zip',
            'file_size' => fake()->numberBetween(1024, 500000000),
            'changelog' => fake()->optional()->paragraph(),
        ];
    }
}
