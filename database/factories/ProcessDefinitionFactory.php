<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\ProcessStatus;
use App\Models\ProcessDefinition;
use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProcessDefinition>
 */
final class ProcessDefinitionFactory extends Factory
{
    protected $model = ProcessDefinition::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'team_id' => Team::factory(),
            'creator_id' => User::factory(),
            'name' => fake()->words(3, true),
            'slug' => fake()->unique()->slug(),
            'description' => fake()->sentence(),
            'status' => ProcessStatus::DRAFT,
            'version' => 1,
            'steps' => [
                [
                    'key' => 'step_1',
                    'name' => 'Initial Review',
                    'requires_approval' => false,
                    'sla_hours' => 24,
                ],
                [
                    'key' => 'step_2',
                    'name' => 'Manager Approval',
                    'requires_approval' => true,
                    'approval_sla_hours' => 48,
                    'sla_hours' => 48,
                ],
                [
                    'key' => 'step_3',
                    'name' => 'Final Processing',
                    'requires_approval' => false,
                    'sla_hours' => 24,
                ],
            ],
            'business_rules' => [
                'auto_escalate_on_sla_breach' => true,
                'allow_parallel_approvals' => false,
            ],
            'event_triggers' => [
                'on_start' => ['notify_initiator'],
                'on_complete' => ['notify_stakeholders'],
            ],
            'sla_config' => [
                'hours' => 96,
                'business_hours_only' => false,
            ],
            'escalation_rules' => [
                'sla_breach' => [
                    'escalate_to_role' => 'manager',
                    'notify_after_hours' => 24,
                ],
            ],
            'metadata' => [
                'category' => 'approval',
                'priority' => 'medium',
            ],
            'documentation' => fake()->paragraph(),
        ];
    }

    public function active(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => ProcessStatus::ACTIVE,
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => ProcessStatus::INACTIVE,
        ]);
    }

    public function withSimpleSteps(): static
    {
        return $this->state(fn (array $attributes): array => [
            'steps' => [
                [
                    'key' => 'step_1',
                    'name' => 'Step 1',
                    'requires_approval' => false,
                ],
                [
                    'key' => 'step_2',
                    'name' => 'Step 2',
                    'requires_approval' => false,
                ],
            ],
        ]);
    }
}
