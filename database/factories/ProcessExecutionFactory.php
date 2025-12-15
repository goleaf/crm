<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\ProcessExecutionStatus;
use App\Models\ProcessDefinition;
use App\Models\ProcessExecution;
use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProcessExecution>
 */
final class ProcessExecutionFactory extends Factory
{
    protected $model = ProcessExecution::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'team_id' => Team::factory(),
            'process_definition_id' => ProcessDefinition::factory(),
            'initiated_by_id' => User::factory(),
            'status' => ProcessExecutionStatus::PENDING,
            'process_version' => 1,
            'context_data' => [
                'request_id' => fake()->uuid(),
                'priority' => 'medium',
            ],
            'execution_state' => [
                'current_step' => 0,
            ],
            'started_at' => null,
            'completed_at' => null,
            'sla_due_at' => now()->addDays(4),
        ];
    }

    public function inProgress(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => ProcessExecutionStatus::IN_PROGRESS,
            'started_at' => now(),
        ]);
    }

    public function completed(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => ProcessExecutionStatus::COMPLETED,
            'started_at' => now()->subDays(2),
            'completed_at' => now(),
        ]);
    }

    public function failed(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => ProcessExecutionStatus::FAILED,
            'started_at' => now()->subDays(1),
            'error_message' => 'Process execution failed',
        ]);
    }
}
