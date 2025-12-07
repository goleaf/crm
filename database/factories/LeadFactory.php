<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\LeadAssignmentStrategy;
use App\Enums\LeadGrade;
use App\Enums\LeadNurtureStatus;
use App\Enums\LeadSource;
use App\Enums\LeadStatus;
use App\Models\Lead;
use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\Sequence;

/**
 * @extends Factory<Lead>
 */
final class LeadFactory extends Factory
{
    protected $model = Lead::class;

    public function definition(): array
    {
        return [
            'team_id' => Team::factory(),
            'creator_id' => User::factory(),
            'name' => $this->faker->name(),
            'job_title' => $this->faker->jobTitle(),
            'company_name' => $this->faker->company(),
            'email' => $this->faker->unique()->safeEmail(),
            'phone' => $this->faker->e164PhoneNumber(),
            'mobile' => $this->faker->e164PhoneNumber(),
            'website' => $this->faker->url(),
            'source' => $this->faker->randomElement(LeadSource::cases())->value,
            'status' => $this->faker->randomElement(LeadStatus::cases())->value,
            'score' => $this->faker->numberBetween(0, 100),
            'grade' => $this->faker->randomElement(LeadGrade::cases())->value,
            'assignment_strategy' => $this->faker->randomElement(LeadAssignmentStrategy::cases())->value,
            'nurture_status' => $this->faker->randomElement(LeadNurtureStatus::cases())->value,
            'territory' => $this->faker->state(),
            'last_activity_at' => now(),
        ];
    }

    public function configure(): Factory
    {
        // Use minutes instead of seconds to ensure distinct timestamps
        // and avoid flaky sorting tests in fast CI environments
        return $this->sequence(fn (Sequence $sequence): array => [
            'created_at' => now()->subMinutes($sequence->index),
            'updated_at' => now()->subMinutes($sequence->index),
        ]);
    }
}
