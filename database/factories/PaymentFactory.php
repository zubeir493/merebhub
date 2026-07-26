<?php

namespace Database\Factories;

use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Models\Payment;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Payment>
 */
class PaymentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'order_id' => Order::factory(),
            'provider' => 'chapa',
            'provider_reference' => 'MH-'.fake()->unique()->numerify('##########'),
            'amount_minor' => fake()->numberBetween(50000, 500000),
            'currency' => 'ETB',
            'status' => PaymentStatus::Pending,
        ];
    }
}
