<?php

namespace Database\Factories;

use App\Domain\Merchants\Enums\MerchantApplicationStatus;
use App\Models\Merchant;
use App\Models\MerchantApplication;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MerchantApplication>
 */
class MerchantApplicationFactory extends Factory
{
    protected $model = MerchantApplication::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'merchant_id' => Merchant::factory(),
            'user_id' => User::factory(),
            'submitted_data' => ['display_name' => fake()->company()],
            'state' => MerchantApplicationStatus::Submitted,
            'submitted_at' => now(),
        ];
    }
}
