<?php

declare(strict_types=1);

use App\Models\Task;
use App\Models\Team;
use App\Models\User;

/**
 * **Feature: projects-resources, Property 1: Dependency enforcement**
 * **Validates: Requirements 2.3**
 *
 * For any task with dependencies, the task's start date must not be earlier
 * than the latest end date of all its dependencies.
 */
test('task start dates respect dependency end dates', function (): void {
    $team = Team::factory()->create();
    $user = User::factory()->create();
    $team->users()->attach($user);

    // Run 100 iterations to test various scenarios
    for ($i = 0; $i < 100; $i++) {
        // Create a dependency task with a random end date
        $dependency = Task::factory()
            ->for($team)
            ->create([
                'end_date' => now()->addDays(fake()->numberBetween(1, 30)),
            ]);

        // Create a dependent task with a start date
        $task = Task::factory()
            ->for($team)
            ->create([
                'start_date' => now()->addDays(fake()->numberBetween(1, 60)),
            ]);

        // Attach the dependency
        $task->dependencies()->attach($dependency->id);

        // Refresh to get the latest data
        $task->refresh();
        $dependency->refresh();

        // Property: If both dates exist, start date should not be before dependency end date
        if ($task->start_date !== null && $dependency->end_date !== null) {
            expect($task->violatesDependencyConstraints())
                ->toBe($task->start_date->lessThan($dependency->end_date))
                ->and($task->getEarliestStartDate())
                ->toBeInstanceOf(\Illuminate\Support\Carbon::class);

            // If there's a violation, the earliest start date should be the dependency's end date
            if ($task->start_date->lessThan($dependency->end_date)) {
                expect($task->getEarliestStartDate()->equalTo($dependency->end_date))->toBeTrue();
            }
        }

        // Clean up for next iteration
        $task->dependencies()->detach();
        $task->delete();
        $dependency->delete();
    }
})->group('property-test', 'task-dependencies');

/**
 * **Feature: projects-resources, Property 1: Dependency enforcement**
 * **Validates: Requirements 2.3**
 *
 * For any task with multiple dependencies, the earliest start date should be
 * the maximum of all dependency end dates.
 */
test('task earliest start date is maximum of all dependency end dates', function (): void {
    $team = Team::factory()->create();
    $user = User::factory()->create();
    $team->users()->attach($user);

    for ($i = 0; $i < 100; $i++) {
        $dependencyCount = fake()->numberBetween(2, 5);
        $dependencies = [];
        $maxEndDate = null;

        // Create multiple dependencies with different end dates
        for ($j = 0; $j < $dependencyCount; $j++) {
            $endDate = now()->addDays(fake()->numberBetween(1, 30));
            $dependency = Task::factory()
                ->for($team)
                ->create(['end_date' => $endDate]);

            $dependencies[] = $dependency;

            if ($maxEndDate === null || $endDate->greaterThan($maxEndDate)) {
                $maxEndDate = $endDate;
            }
        }

        // Create dependent task
        $task = Task::factory()
            ->for($team)
            ->create(['start_date' => now()->addDays(fake()->numberBetween(1, 60))]);

        // Attach all dependencies
        foreach ($dependencies as $dep) {
            $task->dependencies()->attach($dep->id);
        }

        $task->refresh();

        // Property: Earliest start date should be the maximum of all dependency end dates
        $earliestStart = $task->getEarliestStartDate();
        expect($earliestStart)->toBeInstanceOf(\Illuminate\Support\Carbon::class);

        if ($maxEndDate !== null && $task->start_date->lessThan($maxEndDate)) {
            // The earliest start should be the max dependency end date
            expect($earliestStart->format('Y-m-d H:i:s'))->toBe($maxEndDate->format('Y-m-d H:i:s'));
        }

        // Clean up
        $task->dependencies()->detach();
        $task->delete();
        foreach ($dependencies as $dep) {
            $dep->delete();
        }
    }
})->group('property-test', 'task-dependencies');

/**
 * **Feature: projects-resources, Property 1: Dependency enforcement**
 * **Validates: Requirements 2.1**
 *
 * For any task, if it has dependencies, it should be blocked if at least one is not completed.
 */
test('task with incomplete dependencies is blocked', function (): void {
    $team = Team::factory()->create();

    for ($i = 0; $i < 100; $i++) {
        // Create a task
        $task = Task::factory()
            ->for($team)
            ->create();

        // Create an incomplete dependency (no status set means not completed)
        $incompleteDep = Task::factory()
            ->for($team)
            ->create();

        $task->dependencies()->attach($incompleteDep->id);
        $task->refresh();

        // Property: Task with incomplete dependency should be blocked
        expect($task->isBlocked())->toBeTrue();

        // Clean up
        $task->dependencies()->detach();
        $task->delete();
        $incompleteDep->delete();
    }
})->group('property-test', 'task-dependencies');
