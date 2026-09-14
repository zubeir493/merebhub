<?php

namespace Database\Factories;

use App\Models\InvoiceSnapshot;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Lunar\Core\Models\Order;

/**
 * @extends Factory<InvoiceSnapshot>
 */
class InvoiceSnapshotFactory extends Factory
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
            'order_id' => Order::factory(),
            'invoice_number' => 'MH-'.now()->format('Y').'-'.fake()->unique()->numerify('########'),
            'currency_code' => 'ETB',
            'currency_factor' => 100,
            'currency_decimal_places' => 2,
            'payment_status' => 'paid',
            'subtotal' => 10000,
            'discount_total' => 0,
            'tax_total' => 0,
            'shipping_total' => 0,
            'total' => 10000,
            'billing_snapshot' => ['first_name' => fake()->firstName(), 'country_iso3' => 'ETH'],
            'line_items' => [['description' => fake()->words(3, true), 'quantity' => 1, 'total' => 10000]],
            'issued_at' => now(),
        ];
    }
}
