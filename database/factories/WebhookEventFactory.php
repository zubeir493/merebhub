<?php

namespace Database\Factories;

use App\Models\WebhookEvent;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<WebhookEvent>
 */
class WebhookEventFactory extends Factory
{
    public function definition(): array
    {
        return [
            'provider' => 'woocommerce',
            'event_type' => 'order.completed',
            'payload' => [
                'id' => fake()->unique()->numberBetween(1000, 999999),
            ],
            'processed_at' => null,
        ];
    }
}
