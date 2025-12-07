<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\ProjectStatus;
use App\Models\Project;
use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Project>
 */
final class ProjectFactory extends Factory
{
    protected $model = Project::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'team_id' => Team::factory(),
            'creator_id' => User::factory(),
            'name' => fake()->words(3, true),
            'description' => fake()->paragraph(),
            'status' => fake()->randomElement(ProjectStatus::cases()),
            'start_date' => fake()->dateTimeBetween('-1 month', '+1 month'),
            'end_date' => fake()->dateTimeBetween('+1 month', '+6 months'),
            'budget' => fake()->randomFloat(2, 10000, 500000),
            'actual_cost' => fake()->randomFloat(2, 0, 100000),
            'currency' => fake()->randomElement(['USD', 'EUR', 'GBP']),
            'percent_complete' => fake()->randomFloat(2, 0, 100),
            'is_template' => false,
        ];
    }

    public function template(): self
    {
        return $this->state(fn (array $attributes): array => [
            'is_template' => true,
            'status' => ProjectStatus::PLANNING,
        ]);
    }

    public function active(): self
    {
        return $this->state(fn (array $attributes): array => [
            'status' => ProjectStatus::ACTIVE,
        ]);
    }

    public function completed(): self
    {
        return $this->state(fn (array $attributes): array => [
            'status' => ProjectStatus::COMPLETED,
            'percent_complete' => 100,
        ]);
    }

    public function withPhases(): self
    {
        return $this->state(fn (array $attributes): array => [
            'phases' => [
                [
                    'name' => 'Planning',
                    'start_date' => now()->format('Y-m-d'),
                    'end_date' => now()->addWeeks(2)->format('Y-m-d'),
                    'status' => 'completed',
                ],
                [
                    'name' => 'Development',
                    'start_date' => now()->addWeeks(2)->format('Y-m-d'),
                    'end_date' => now()->addWeeks(8)->format('Y-m-d'),
                    'status' => 'active',
                ],
                [
                    'name' => 'Testing',
                    'start_date' => now()->addWeeks(8)->format('Y-m-d'),
                    'end_date' => now()->addWeeks(10)->format('Y-m-d'),
                    'status' => 'pending',
                ],
            ],
        ]);
    }

    public function withMilestones(): self
    {
        return $this->state(fn (array $attributes): array => [
            'milestones' => [
                [
                    'name' => 'Project Kickoff',
                    'date' => now()->format('Y-m-d'),
                    'completed' => true,
                ],
                [
                    'name' => 'MVP Release',
                    'date' => now()->addMonths(2)->format('Y-m-d'),
                    'completed' => false,
                ],
                [
                    'name' => 'Final Delivery',
                    'date' => now()->addMonths(4)->format('Y-m-d'),
                    'completed' => false,
                ],
            ],
        ]);
    }

    public function withRisks(): self
    {
        return $this->state(fn (array $attributes): array => [
            'risks' => [
                [
                    'title' => 'Resource Availability',
                    'description' => 'Key team members may not be available',
                    'probability' => 'medium',
                    'impact' => 'high',
                    'mitigation' => 'Cross-train team members',
                ],
                [
                    'title' => 'Technical Complexity',
                    'description' => 'Integration challenges with legacy systems',
                    'probability' => 'high',
                    'impact' => 'medium',
                    'mitigation' => 'Conduct proof of concept early',
                ],
            ],
        ]);
    }

    public function withIssues(): self
    {
        return $this->state(fn (array $attributes): array => [
            'issues' => [
                [
                    'title' => 'Delayed Approval',
                    'description' => 'Waiting for stakeholder sign-off',
                    'status' => 'open',
                    'priority' => 'high',
                    'reported_at' => now()->subDays(3)->format('Y-m-d'),
                ],
            ],
        ]);
    }
}
