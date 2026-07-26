<?php

namespace Database\Factories;

use App\Enums\BillingModel;
use App\Enums\FulfillmentType;
use App\Models\Product;
use App\Models\ProductPlan;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProductPlan>
 */
class ProductPlanFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'product_id' => Product::factory(),
            'name' => 'Personal',
            'slug' => 'personal',
            'description' => fake()->sentence(),
            'price_minor' => fake()->numberBetween(50000, 500000),
            'currency' => 'ETB',
            'billing_model' => BillingModel::OneTime,
            'license_type' => 'perpetual',
            'activation_limit' => 1,
            'fulfillment_type' => FulfillmentType::LicenseKey,
            'is_active' => true,
            'sort_order' => 0,
        ];
    }
}
