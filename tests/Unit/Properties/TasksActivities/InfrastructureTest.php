<?php

declare(strict_types=1);

use App\Models\Task;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\Generators\TaskGenerator;

uses(RefreshDatabase::class);

/**
 * Infrastructure validation test to ensure property-based testing setup works correctly.
 *
 * This test validates that:
 * - Generators create valid models
 * - Test helpers are accessible
 * - Database seeding works
 * - Property test iteration works
 */
it('validates property testing infrastructure is set up correctly', function (): void {
    $team = Team::factory()->create();
    $user = User::factory()->create();
    $user->teams()->attach($team);

    // Test basic task generation
    $task = TaskGenerator::generate($team, $user);

    expect($task)->toBeInstanceOf(Task::class)
        ->and($task->team_id)->toBe($team->id)
        ->and($task->creator_id)->toBe($user->id)
        ->and($task->title)->not->toBeEmpty();

    // Test task with subtasks generation
    $parentTask = TaskGenerator::generateWithSubtasks($team, 3);

    expect($parentTask->subtasks)->toHaveCount(3);

    // Test task with assignees generation
    $taskWithAssignees = TaskGenerator::generateWithAssignees($team, 2);

    expect($taskWithAssignees->assignees)->toHaveCount(2);

    // Test helper function
    $generatedTask = generateTask($team, $user);

    expect($generatedTask)->toBeInstanceOf(Task::class);
});

/**
 * Test that property test iteration works correctly.
 */
it('can run property tests with multiple iterations', function (): void {
    $team = Team::factory()->create();
    $iterationCount = 0;

    runPropertyTest(function () use ($team, &$iterationCount): void {
        $task = TaskGenerator::generate($team);

        expect($task)->toBeInstanceOf(Task::class);

        $iterationCount++;
    }, 10);

    expect($iterationCount)->toBe(10);
});

/**
 * Test that random utilities work correctly.
 */
it('provides working random utilities', function (): void {
    $items = [1, 2, 3, 4, 5];
    $subset = randomSubset($items);

    expect($subset)->toBeArray()
        ->and(count($subset))->toBeLessThanOrEqual(count($items));

    $date = randomDate();

    expect($date)->toBeInstanceOf(\Illuminate\Support\Carbon::class);

    $boolean = randomBoolean();

    expect($boolean)->toBeBool();
});
