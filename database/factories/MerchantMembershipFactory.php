<?php

namespace Database\Factories;

use App\Domain\Merchants\Enums\MerchantMembershipRole;
use App\Domain\Merchants\Enums\MerchantMembershipStatus;
use App\Models\Merchant;
use App\Models\MerchantMembership;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MerchantMembership>
 */
class MerchantMembershipFactory extends Factory
{
    protected $model = MerchantMembership::class;

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
            'merchant_role' => MerchantMembershipRole::Owner,
            'status' => MerchantMembershipStatus::Active,
            'invited_at' => now(),
            'joined_at' => now(),
        ];
    }
}
