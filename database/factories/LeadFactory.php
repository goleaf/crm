<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\LeadAssignmentStrategy;
use App\Enums\LeadGrade;
use App\Enums\LeadNurtureStatus;
use App\Enums\LeadSource;
use App\Enums\LeadStatus;
<<<<<<< HEAD
use App\Enums\LeadType;
=======
use App\Models\Company;
>>>>>>> d03887dc78a6e1a0c2ed674137398a067503335e
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
            // Use null defaults - caller should provide these to avoid cascading factory creation
            'team_id' => null,
            'creator_id' => null,
            'company_id' => null,
            'assigned_to_id' => null,
            'name' => $this->faker->name(),
            'job_title' => $this->faker->jobTitle(),
            'company_name' => $this->faker->company(),
            'email' => $this->faker->unique()->safeEmail(),
            'phone' => $this->faker->e164PhoneNumber(),
            'mobile' => $this->faker->e164PhoneNumber(),
            'website' => $this->faker->url(),
            'description' => $this->faker->optional(0.7)->paragraph(),
            'lead_value' => $this->faker->optional(0.6)->randomFloat(2, 500, 25000),
            'lead_type' => $this->faker->randomElement(LeadType::cases())->value,
            'expected_close_date' => $this->faker->optional(0.5)->dateTimeBetween('+1 week', '+3 months'),
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

    /**
     * Create with all related factories (for standalone tests).
     * Use this when you need a fully populated Lead without providing relations.
     */
    public function withRelations(): static
    {
        return $this->state(fn (): array => [
            'team_id' => Team::factory(),
            'creator_id' => User::factory(),
            'company_id' => Company::factory(),
            'assigned_to_id' => User::factory(),
        ]);
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
