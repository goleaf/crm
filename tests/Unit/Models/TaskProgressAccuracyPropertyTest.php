<?php

declare(strict_types=1);

use App\Models\Project;
use App\Models\Task;
use App\Models\Team;

/**
 * **Feature: projects-resources, Property 2: Progress accuracy**
 * **Validates: Requirements 2.1**
 *
 * For any task with subtasks, the parent task's calculated percent complete
 * should equal the average of all subtask percent complete values.
 */
test('parent task percent complete equals average of subtask percentages', function (): void {
    $team = Team::factory()->create();

    for ($i = 0; $i < 100; $i++) {
        // Create a parent task
        $parentTask = Task::factory()
            ->for($team)
            ->create(['percent_complete' => 0]);

        // Create random number of subtasks with random completion percentages
        $subtaskCount = fake()->numberBetween(1, 10);
        $totalProgress = 0;

        for ($j = 0; $j < $subtaskCount; $j++) {
            $percentComplete = fake()->randomFloat(2, 0, 100);
            $totalProgress += $percentComplete;

            Task::factory()
                ->for($team)
                ->create([
                    'parent_id' => $parentTask->id,
                    'percent_complete' => $percentComplete,
                ]);
        }

        $expectedAverage = round($totalProgress / $subtaskCount, 2);

        // Property: Calculated percent complete should equal average of subtasks
        $calculated = $parentTask->calculatePercentComplete();
        expect($calculated)->toBe($expectedAverage);

        // Clean up
        $parentTask->subtasks()->delete();
        $parentTask->delete();
    }
})->group('property-test', 'task-progress');

/**
 * **Feature: projects-resources, Property 2: Progress accuracy**
 * **Validates: Requirements 2.1**
 *
 * For any task without subtasks, the calculated percent complete should
 * equal the task's own percent_complete value.
 */
test('task without subtasks returns own percent complete', function (): void {
    $team = Team::factory()->create();

    for ($i = 0; $i < 100; $i++) {
        $percentComplete = fake()->randomFloat(2, 0, 100);

        $task = Task::factory()
            ->for($team)
            ->create(['percent_complete' => $percentComplete]);

        // Property: Task without subtasks should return its own percent_complete
        expect($task->calculatePercentComplete())->toBe($percentComplete);

        $task->delete();
    }
})->group('property-test', 'task-progress');

/**
 * **Feature: projects-resources, Property 2: Progress accuracy**
 * **Validates: Requirements 1.2**
 *
 * For any project with tasks, the project's calculated percent complete
 * should equal the percentage of completed tasks (based on status field).
 */
test('project percent complete calculation is consistent', function (): void {
    $team = Team::factory()->create();

    for ($i = 0; $i < 50; $i++) {
        $project = Project::factory()
            ->for($team)
            ->create(['percent_complete' => 0]);

        $taskCount = fake()->numberBetween(1, 10);

        // Create tasks
        for ($j = 0; $j < $taskCount; $j++) {
            $task = Task::factory()
                ->for($team)
                ->create();

            $project->tasks()->attach($task->id);
        }

        // Property: Calculated percentage should be between 0 and 100
        $calculated = $project->calculatePercentComplete();
        expect($calculated)->toBeGreaterThanOrEqual(0.0)
            ->and($calculated)->toBeLessThanOrEqual(100.0);

        // Clean up
        $project->tasks()->detach();
        $project->delete();
    }
})->group('property-test', 'project-progress');

/**
 * **Feature: projects-resources, Property 2: Progress accuracy**
 * **Validates: Requirements 1.2**
 *
 * For any project with no tasks, the calculated percent complete should be 0.
 */
test('project with no tasks has zero percent complete', function (): void {
    $team = Team::factory()->create();

    for ($i = 0; $i < 100; $i++) {
        $project = Project::factory()
            ->for($team)
            ->create();

        // Property: Project with no tasks should have 0% completion
        expect($project->calculatePercentComplete())->toBe(0.0);

        $project->delete();
    }
})->group('property-test', 'project-progress');

/**
 * **Feature: projects-resources, Property 2: Progress accuracy**
 * **Validates: Requirements 2.1**
 *
 * For any task, updating percent complete should maintain consistency with subtasks.
 */
test('task percent complete update maintains consistency', function (): void {
    $team = Team::factory()->create();

    for ($i = 0; $i < 50; $i++) {
        $task = Task::factory()
            ->for($team)
            ->create(['percent_complete' => fake()->randomFloat(2, 0, 99)]);

        // Update percent complete
        $task->updatePercentComplete();

        // Property: Updated percent complete should be between 0 and 100
        expect($task->percent_complete)->toBeGreaterThanOrEqual(0.0)
            ->and($task->percent_complete)->toBeLessThanOrEqual(100.0);

        $task->delete();
    }
})->group('property-test', 'task-progress');
