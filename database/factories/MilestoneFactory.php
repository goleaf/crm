<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\MilestonePriority;
use App\Enums\MilestoneStatus;
use App\Enums\MilestoneType;
use App\Models\Milestone;
use App\Models\Project;
use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Date;

/**
 * @extends Factory<Milestone>
 */
final class MilestoneFactory extends Factory
{
    protected $model = Milestone::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'team_id' => Team::factory(),
            'project_id' => fn (array $attributes): int => Project::factory()->create([
                'team_id' => $attributes['team_id'],
                'start_date' => Date::today()->subDays(3),
                'end_date' => Date::today()->addDays(90),
            ])->getKey(),
            'owner_id' => function (array $attributes): int {
                $teamId = (int) $attributes['team_id'];
                $projectId = (int) $attributes['project_id'];

                $user = User::factory()->create();
                $user->teams()->attach($teamId);

                $project = Project::query()->find($projectId);
                $project?->teamMembers()->syncWithoutDetaching([$user->getKey() => [
                    'role' => 'member',
                    'allocation_percentage' => 100,
                ]]);

                return (int) $user->getKey();
            },
            'title' => fake()->sentence(4),
            'description' => fake()->boolean(60) ? fake()->paragraph() : null,
            'target_date' => fn (array $attributes): string => Project::query()
                ->find((int) $attributes['project_id'])
                ?->start_date
                ?->copy()
                ->addDays(fake()->numberBetween(1, 60))
                ->toDateString()
                ?? Date::today()->addDays(30)->toDateString(),
            'actual_completion_date' => null,
            'milestone_type' => fake()->randomElement(MilestoneType::cases()),
            'priority_level' => fake()->randomElement(MilestonePriority::cases()),
            'status' => MilestoneStatus::NOT_STARTED,
            'completion_percentage' => 0,
            'schedule_variance_days' => 0,
            'is_critical' => fake()->boolean(15),
            'is_at_risk' => false,
            'last_progress_threshold_notified' => 0,
            'reminders_sent' => [],
            'overdue_notified_at' => null,
            'stakeholder_ids' => [],
            'reference_links' => [],
            'notes' => fake()->boolean(30) ? fake()->sentence() : null,
            'requires_approval' => false,
            'submitted_for_approval_at' => null,
        ];
    }
}

