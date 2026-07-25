<?php

namespace Database\Factories;

use App\Enums\AppSubmissionStatus;
use App\Models\AppSubmission;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AppSubmission>
 */
class AppSubmissionFactory extends Factory
{
    public function definition(): array
    {
        return [
            'submitter_name' => fake()->name(),
            'submitter_email' => fake()->safeEmail(),
            'app_name' => fake()->words(2, true),
            'description' => fake()->paragraphs(2, true),
            'suggested_price' => fake()->optional()->randomFloat(2, 5, 200),
            'platform' => fake()->randomElement(['Windows', 'macOS', 'Linux', 'Android', 'iOS', 'Web']),
            'file_path' => 'submissions/'.fake()->uuid().'.zip',
            'status' => AppSubmissionStatus::Pending,
            'reviewed_by' => null,
            'linked_author_id' => null,
        ];
    }
}
