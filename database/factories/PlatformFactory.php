<?php

namespace Database\Factories;

use App\Models\Platform;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Platform>
 */
class PlatformFactory extends Factory
{
    public function definition(): array
    {
        $name = fake()->unique()->randomElement(['Windows', 'macOS', 'Linux', 'Android', 'iOS', 'Web']);

        return [
            'name' => $name,
            'slug' => Str::slug($name),
            'icon_path' => null,
        ];
    }
}
