<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\ProcessExecutionStatus;
use App\Enums\ProcessStatus;
use App\Models\ProcessDefinition;
use App\Models\ProcessExecution;
use App\Models\Team;
use App\Models\User;
use App\Services\ProcessEngine;
use Illuminate\Database\Eloquent\Factories\Sequence;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

final class ProcessManagementSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('Creating process definitions, executions, and approvals...');

        $teams = Team::all();
        $users = User::all();

        if ($teams->isEmpty() || $users->isEmpty()) {
            $this->command->warn('No teams or users found.');

            return;
        }

        $engine = app(ProcessEngine::class);

        $definitions = collect();
        foreach ($teams as $team) {
            $creator = $users->random();

            $definitions = $definitions->merge(
                ProcessDefinition::factory()
                    ->count(3)
                    ->state(new Sequence(
                        fn (Sequence $sequence): array => [
                            'team_id' => $team->id,
                            'creator_id' => $creator->id,
                            'name' => "Onboarding Flow {$sequence->index}",
                            'slug' => Str::slug("onboarding-flow-{$team->id}-{$sequence->index}-".Str::random(4)),
                            'status' => ProcessStatus::ACTIVE,
                            'steps' => $this->sampleSteps(),
                            'business_rules' => ['sla_minutes' => 120],
                            'event_triggers' => ['lead.created'],
                            'sla_config' => ['duration_minutes' => 240],
                        ]
                    ))
                    ->create()
            );
        }

        $executionsCreated = 0;
        foreach ($definitions as $definition) {
            for ($i = 0; $i < 3; $i++) {
                $initiator = $users->random();
                $execution = $engine->startExecution(
                    $definition,
                    $initiator->id,
                    ['customer' => fake()->company(), 'deal_value' => fake()->randomFloat(2, 10_000, 150_000)]
                );

                // Kick off the first step
                $engine->executeNextStep($execution);

                $executionsCreated++;
            }
        }

        $this->command->info("✓ Created {$definitions->count()} process definitions and {$executionsCreated} executions");

        $this->seedMetrics($definitions);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function sampleSteps(): array
    {
        return [
            [
                'key' => 'intake',
                'name' => 'Intake',
                'requires_approval' => false,
                'sla_minutes' => 60,
            ],
            [
                'key' => 'review',
                'name' => 'Eligibility Review',
                'requires_approval' => true,
                'approver_id' => null,
                'sla_minutes' => 120,
            ],
            [
                'key' => 'finalize',
                'name' => 'Finalize & Notify',
                'requires_approval' => false,
                'sla_minutes' => 60,
            ],
        ];
    }

    private function seedMetrics(iterable $definitions): void
    {
        $definitions = collect($definitions);

        foreach ($definitions as $definition) {
            ProcessExecution::factory()
                ->count(2)
                ->create([
                    'team_id' => $definition->team_id,
                    'process_definition_id' => $definition->id,
                    'initiated_by_id' => null,
                    'status' => ProcessExecutionStatus::COMPLETED,
                    'process_version' => $definition->version,
                    'context_data' => ['seeded' => true],
                    'execution_state' => ['current_step' => 3],
                ]);

            $definition->analytics()->create([
                'team_id' => $definition->team_id,
                'metric_date' => now()->toDateString(),
                'executions_started' => 3,
                'executions_completed' => 2,
                'executions_failed' => 0,
                'sla_breaches' => 0,
                'escalations' => 0,
                'avg_completion_time_seconds' => 3600,
            ]);
        }
    }
}
