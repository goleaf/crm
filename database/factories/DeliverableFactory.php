<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\DeliverableStatus;
use App\Models\Deliverable;
use App\Models\Milestone;
use App\Models\Project;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Date;

/**
 * @extends Factory<Deliverable>
 */
final class DeliverableFactory extends Factory
{
    protected $model = Deliverable::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'milestone_id' => Milestone::factory(),
            'owner_id' => function (array $attributes): int {
                $milestone = Milestone::query()->with('project')->find((int) $attributes['milestone_id']);
                $project = $milestone?->project;

                $user = User::factory()->create();

                if ($project instanceof Project) {
                    $user->teams()->attach($project->team_id);
                    $project->teamMembers()->syncWithoutDetaching([$user->getKey() => [
                        'role' => 'member',
                        'allocation_percentage' => 100,
                    ]]);
                }

                return (int) $user->getKey();
            },
            'name' => fake()->sentence(3),
            'description' => fake()->boolean(60) ? fake()->paragraph() : null,
            'due_date' => function (array $attributes): string {
                $milestone = Milestone::query()->find((int) $attributes['milestone_id']);
                if (! $milestone instanceof Milestone) {
                    return Date::today()->addDays(7)->toDateString();
                }

                return $milestone->target_date->copy()->subDays(fake()->numberBetween(0, 3))->toDateString();
            },
            'acceptance_criteria' => fake()->boolean(50) ? fake()->sentence() : null,
            'status' => DeliverableStatus::PENDING,
            'completion_evidence_url' => null,
            'completion_evidence_path' => null,
            'requires_approval' => false,
            'completed_at' => null,
        ];
    }
}

