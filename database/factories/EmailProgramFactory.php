<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\EmailProgramStatus;
use App\Enums\EmailProgramType;
use App\Models\EmailProgram;
use App\Models\Team;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EmailProgram>
 */
final class EmailProgramFactory extends Factory
{
    protected $model = EmailProgram::class;

    public function definition(): array
    {
        return [
            'team_id' => Team::factory(),
            'name' => fake()->sentence(3),
            'description' => fake()->paragraph(),
            'type' => fake()->randomElement(EmailProgramType::cases()),
            'status' => EmailProgramStatus::DRAFT,
            'audience_filters' => null,
            'estimated_audience_size' => 0,
            'scheduled_start_at' => null,
            'scheduled_end_at' => null,
            'is_ab_test' => false,
            'personalization_rules' => null,
            'dynamic_content_blocks' => null,
            'scoring_rules' => null,
            'min_engagement_score' => 0,
            'throttle_rate_per_hour' => null,
            'respect_quiet_hours' => true,
            'quiet_hours_start' => '22:00:00',
            'quiet_hours_end' => '08:00:00',
            'total_recipients' => 0,
            'total_sent' => 0,
            'total_delivered' => 0,
            'total_opened' => 0,
            'total_clicked' => 0,
            'total_bounced' => 0,
            'total_unsubscribed' => 0,
            'total_complained' => 0,
        ];
    }

    public function active(): self
    {
        return $this->state(fn (array $attributes): array => [
            'status' => EmailProgramStatus::ACTIVE,
            'started_at' => now()->subDays(1),
            'scheduled_start_at' => now()->subDays(1),
        ]);
    }

    public function scheduled(): self
    {
        return $this->state(fn (array $attributes): array => [
            'status' => EmailProgramStatus::SCHEDULED,
            'scheduled_start_at' => now()->addDays(1),
        ]);
    }

    public function withAbTest(): self
    {
        return $this->state(fn (array $attributes): array => [
            'is_ab_test' => true,
            'ab_test_sample_size_percent' => 20,
            'ab_test_winner_metric' => 'open_rate',
        ]);
    }

    public function withThrottling(): self
    {
        return $this->state(fn (array $attributes): array => [
            'throttle_rate_per_hour' => 100,
        ]);
    }
}
