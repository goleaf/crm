<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\EmailProgramUnsubscribe;
use App\Models\Team;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EmailProgramUnsubscribe>
 */
final class EmailProgramUnsubscribeFactory extends Factory
{
    protected $model = EmailProgramUnsubscribe::class;

    public function definition(): array
    {
        return [
            'team_id' => Team::factory(),
            'email' => fake()->safeEmail(),
            'email_program_id' => null,
            'reason' => fake()->randomElement(['not_interested', 'too_frequent', 'irrelevant', 'spam']),
            'feedback' => fake()->optional()->sentence(),
            'ip_address' => fake()->ipv4(),
            'user_agent' => fake()->userAgent(),
        ];
    }
}
