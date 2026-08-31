<?php

namespace Database\Factories;

use App\Models\Merchant;
use App\Models\MerchantPrivateProfile;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MerchantPrivateProfile>
 */
class MerchantPrivateProfileFactory extends Factory
{
    protected $model = MerchantPrivateProfile::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'merchant_id' => Merchant::factory(),
            'registration_number' => fake()->numerify('REG-########'),
            'tax_identifier' => fake()->numerify('TIN-########'),
            'payout_account' => fake()->iban(),
            'verification_status' => 'pending',
        ];
    }
}
