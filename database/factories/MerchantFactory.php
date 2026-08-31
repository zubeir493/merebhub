<?php

namespace Database\Factories;

use App\Domain\Merchants\Enums\MerchantStatus;
use App\Domain\Merchants\Enums\MerchantType;
use App\Models\Merchant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Merchant>
 */
class MerchantFactory extends Factory
{
    protected $model = Merchant::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'type' => MerchantType::LocalDeveloper,
            'legal_name' => fake()->company(),
            'display_name' => fake()->company(),
            'slug' => fake()->unique()->slug(),
            'profile' => fake()->paragraph(),
            'website_url' => fake()->url(),
            'status' => MerchantStatus::Approved,
            'approved_at' => now(),
        ];
    }
}
