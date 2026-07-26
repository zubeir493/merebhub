<?php

namespace Database\Factories;

use App\Enums\AuthorStatus;
use App\Models\Author;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Author>
 */
class AuthorFactory extends Factory
{
    public function definition(): array
    {
        $name = fake()->name();

        return [
            'name' => $name,
            'slug' => Str::slug($name).'-'.fake()->unique()->numberBetween(1000, 9999),
            'tagline' => fake()->sentence(7),
            'bio' => fake()->paragraph(),
            'avatar_path' => null,
            'website_url' => fake()->optional()->url(),
            'member_since' => fake()->dateTimeBetween('-5 years', 'now'),
            'status' => AuthorStatus::Active,
            'is_verified' => false,
            'is_featured' => false,
            'show_public_sales' => true,
            'public_sales_count' => fake()->numberBetween(0, 5000),
            'average_rating' => fake()->randomFloat(1, 0, 5),
            'is_public' => true,
        ];
    }

    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => AuthorStatus::PendingApproval,
            'is_public' => false,
        ]);
    }

    public function hidden(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => AuthorStatus::Hidden,
        ]);
    }

    public function suspended(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => AuthorStatus::Suspended,
        ]);
    }

    public function verified(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_verified' => true,
        ]);
    }
}
