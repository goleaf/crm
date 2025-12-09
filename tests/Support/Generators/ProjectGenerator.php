<?php

declare(strict_types=1);

namespace Tests\Support\Generators;

use App\Enums\ProjectStatus;
use App\Models\Project;
use App\Models\Team;
use App\Models\User;

/**
 * Generator for creating random Project instances for property-based testing.
 */
final class ProjectGenerator
{
    /**
     * Generate a random project.
     *
     * @param array<string, mixed> $overrides
     */
    public static function generate(
        Team $team,
        ?User $creator = null,
        array $overrides = [],
    ): Project {
        $creator ??= User::factory()->create();

        $startDate = fake()->optional(0.8)->dateTimeBetween('-1 year', '+1 month');
        $endDate = $startDate ? fake()->optional(0.7)->dateTimeBetween($startDate, '+1 year') : null;

        $budget = fake()->optional(0.7)->randomFloat(2, 1000, 100000);

        return Project::factory()->create(array_merge([
            'team_id' => $team->id,
            'creator_id' => $creator->id,
            'name' => fake()->words(3, true),
            'description' => fake()->optional(0.7)->paragraph(),
            'status' => fake()->randomElement(ProjectStatus::cases()),
            'start_date' => $startDate,
            'end_date' => $endDate,
            'budget' => $budget,
            'currency' => fake()->randomElement(['USD', 'EUR', 'GBP']),
            'is_template' => fake()->boolean(10), // 10% chance of being a template
        ], $overrides));
    }

    /**
     * Generate a project template.
     *
     * @param array<string, mixed> $overrides
     */
    public static function generateTemplate(
        Team $team,
        ?User $creator = null,
        array $overrides = [],
    ): Project {
        return self::generate($team, $creator, array_merge([
            'is_template' => true,
            'status' => ProjectStatus::PLANNING,
        ], $overrides));
    }

    /**
     * Generate a project with tasks.
     *
     * @param array<string, mixed> $overrides
     */
    public static function generateWithTasks(
        Team $team,
        ?User $creator = null,
        int $taskCount = 5,
        array $overrides = [],
    ): Project {
        $project = self::generate($team, $creator, $overrides);

        $tasks = [];
        for ($i = 0; $i < $taskCount; $i++) {
            $tasks[] = TaskGenerator::generate($team, $creator);
        }

        $project->tasks()->attach(collect($tasks)->pluck('id'));

        return $project->fresh(['tasks']);
    }
}
