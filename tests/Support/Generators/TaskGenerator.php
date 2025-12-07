<?php

declare(strict_types=1);

namespace Tests\Support\Generators;

use App\Enums\CreationSource;
use App\Models\Task;
use App\Models\TaskCategory;
use App\Models\Team;
use App\Models\User;
use Illuminate\Support\Carbon;

/**
 * Generator for creating random Task instances for property-based testing.
 */
final class TaskGenerator
{
    /**
     * Generate a random task with all fields populated.
     *
     * @param  array<string, mixed>  $overrides
     */
    public static function generate(Team $team, ?User $creator = null, array $overrides = []): Task
    {
        $creator = $creator ?? User::factory()->create();

        $data = array_merge([
            'team_id' => $team->id,
            'creator_id' => $creator->id,
            'title' => fake()->sentence(),
            'creation_source' => fake()->randomElement(CreationSource::cases()),
            'start_date' => fake()->optional()->dateTimeBetween('-1 month', '+1 month'),
            'end_date' => fake()->optional()->dateTimeBetween('+1 month', '+3 months'),
            'estimated_duration_minutes' => fake()->optional()->numberBetween(15, 480),
            'percent_complete' => fake()->randomFloat(2, 0, 100),
            'is_milestone' => fake()->boolean(20), // 20% chance of being a milestone
        ], $overrides);

        return Task::factory()->create($data);
    }

    /**
     * Generate a task with subtasks.
     *
     * @param  int  $subtaskCount  Number of subtasks to create
     */
    public static function generateWithSubtasks(Team $team, int $subtaskCount = 3): Task
    {
        $parent = self::generate($team);

        for ($i = 0; $i < $subtaskCount; $i++) {
            self::generate($team, overrides: ['parent_id' => $parent->id]);
        }

        return $parent->fresh(['subtasks']) ?? $parent;
    }

    /**
     * Generate a task with assignees.
     *
     * @param  int  $assigneeCount  Number of assignees
     */
    public static function generateWithAssignees(Team $team, int $assigneeCount = 2): Task
    {
        $task = self::generate($team);
        $users = User::factory()->count($assigneeCount)->create();

        foreach ($users as $user) {
            $user->teams()->attach($team);
        }

        $task->assignees()->attach($users);

        return $task->fresh(['assignees']) ?? $task;
    }

    /**
     * Generate a task with categories.
     *
     * @param  int  $categoryCount  Number of categories
     */
    public static function generateWithCategories(Team $team, int $categoryCount = 2): Task
    {
        $task = self::generate($team);
        $categories = TaskCategory::factory()->count($categoryCount)->create([
            'team_id' => $team->id,
        ]);

        $task->categories()->attach($categories);

        return $task->fresh(['categories']) ?? $task;
    }

    /**
     * Generate a task with dependencies.
     *
     * @param  int  $dependencyCount  Number of dependencies
     */
    public static function generateWithDependencies(Team $team, int $dependencyCount = 2): Task
    {
        $task = self::generate($team);
        $dependencies = [];

        for ($i = 0; $i < $dependencyCount; $i++) {
            $dependencies[] = self::generate($team);
        }

        $task->dependencies()->attach($dependencies);

        return $task->fresh(['dependencies']) ?? $task;
    }

    /**
     * Generate a random date range ensuring start is before end.
     *
     * @return array{start_date: Carbon, end_date: Carbon}
     */
    public static function generateDateRange(): array
    {
        $start = Carbon::parse(fake()->dateTimeBetween('-1 month', '+1 month'));
        $end = Carbon::parse(fake()->dateTimeBetween($start, '+3 months'));

        return [
            'start_date' => $start,
            'end_date' => $end,
        ];
    }

    /**
     * Generate random task data without creating a model.
     *
     * @return array<string, mixed>
     */
    public static function generateData(Team $team, ?User $creator = null): array
    {
        $creator = $creator ?? User::factory()->create();

        return [
            'team_id' => $team->id,
            'creator_id' => $creator->id,
            'title' => fake()->sentence(),
            'creation_source' => fake()->randomElement(CreationSource::cases()),
            'start_date' => fake()->optional()->dateTimeBetween('-1 month', '+1 month'),
            'end_date' => fake()->optional()->dateTimeBetween('+1 month', '+3 months'),
            'estimated_duration_minutes' => fake()->optional()->numberBetween(15, 480),
            'percent_complete' => fake()->randomFloat(2, 0, 100),
            'is_milestone' => fake()->boolean(20),
        ];
    }

    /**
     * Generate a completed task.
     */
    public static function generateCompleted(Team $team): Task
    {
        return self::generate($team, overrides: [
            'percent_complete' => 100,
        ]);
    }

    /**
     * Generate an incomplete task.
     */
    public static function generateIncomplete(Team $team): Task
    {
        return self::generate($team, overrides: [
            'percent_complete' => fake()->randomFloat(2, 0, 99),
        ]);
    }

    /**
     * Generate a milestone task.
     */
    public static function generateMilestone(Team $team): Task
    {
        return self::generate($team, overrides: [
            'is_milestone' => true,
        ]);
    }
}
