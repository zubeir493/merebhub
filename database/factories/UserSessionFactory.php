<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\UserSession;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<UserSession>
 */
class UserSessionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $sessionId = Str::random(40);

        return [
            'user_id' => User::factory(),
            'session_hash' => hash('sha256', $sessionId),
            'session_id' => $sessionId,
            'device_label' => 'Desktop · Chrome',
            'ip_hash' => hash('sha256', fake()->ipv4()),
            'last_active_at' => now(),
            'revoked_at' => null,
        ];
    }
}
