<?php

namespace Database\Factories;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Order>
 */
class OrderFactory extends Factory
{
    public function definition(): array
    {
        return [
            'buyer_email' => fake()->safeEmail(),
            'buyer_user_id' => null,
            'product_id' => Product::factory(),
            'amount' => fake()->randomFloat(2, 5, 250),
            'currency' => 'ETB',
            'status' => OrderStatus::Pending,
            'paid_at' => null,
        ];
    }

    public function paid(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => OrderStatus::Paid,
            'paid_at' => now(),
        ]);
    }
}
