<?php

namespace Database\Factories;

use App\Models\BillingProfile;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BillingProfile>
 */
class BillingProfileFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'billing_type' => 'personal',
            'company_name' => null,
            'tax_identifier' => null,
            'first_name' => fake()->firstName(),
            'last_name' => fake()->lastName(),
            'contact_email' => fake()->safeEmail(),
            'contact_phone' => fake()->phoneNumber(),
            'line_one' => fake()->streetAddress(),
            'line_two' => null,
            'city' => 'Addis Ababa',
            'state' => null,
            'postcode' => '1000',
            'country_iso3' => 'ETH',
        ];
    }
}
