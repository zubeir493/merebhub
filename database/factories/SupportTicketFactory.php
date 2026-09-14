<?php

namespace Database\Factories;

use App\Domain\Support\Enums\SupportTicketPriority;
use App\Domain\Support\Enums\SupportTicketStatus;
use App\Models\SupportTicket;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SupportTicket>
 */
class SupportTicketFactory extends Factory
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
            'assigned_staff_id' => null,
            'subject' => fake()->sentence(5),
            'status' => SupportTicketStatus::Open,
            'priority' => SupportTicketPriority::Normal,
            'last_message_at' => now(),
            'resolved_at' => null,
            'closed_at' => null,
        ];
    }
}
