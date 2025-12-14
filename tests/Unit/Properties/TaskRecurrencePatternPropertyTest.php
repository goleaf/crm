<?php

declare(strict_types=1);

use App\Models\Task;
use App\Models\Team;
use App\Models\User;
use App\Services\Task\TaskRecurrenceService;

use function Pest\Laravel\actingAs;

beforeEach(function (): void {
    $this->team = Team::factory()->create();
    $this->user = User::factory()->create();
    $this->user->teams()->attach($this->team);
    actingAs($this->user);

    $this->recurrenceService = resolve(TaskRecurrenceService::class);
});

/**
 * **Feature: tasks-activities-enhancement, Property 9: Recurrence pattern storage and generation**
 * **Validates: Requirements 6.1, 6.2, 6.3**
 *
 * Property: Creating a recurrence stores the pattern and calculates the next occurrence date based on the interval.
 */
test('property: recurrence persists pattern and computes next occurrence', function (): void {
    runPropertyTest(function (): void {
        $task = generateTask($this->team, $this->user, [
            'start_date' => now(),
            'end_date' => now()->addDay(),
        ]);

        $pattern = [
            'frequency' => fake()->randomElement(['daily', 'weekly', 'monthly']),
            'interval' => fake()->numberBetween(1, 4),
            'timezone' => config('app.timezone'),
        ];

        $recurrence = $this->recurrenceService->createRecurrence($task, $pattern);

        expect($task->fresh()->recurrence)->not->toBeNull();
        expect($recurrence->frequency)->toBe($pattern['frequency']);
        expect($recurrence->interval)->toBe($pattern['interval']);

        $nextDate = $task->getNextOccurrenceDate();
        expect($nextDate)->not->toBeNull();
        expect($nextDate->greaterThanOrEqualTo($task->start_date))->toBeTrue();
    }, 50);
})->group('property');

/**
 * **Feature: tasks-activities-enhancement, Property 9: Recurrence pattern storage and generation**
 * **Validates: Requirements 6.2, 6.3**
 *
 * Property: Generating the next instance copies core attributes, assignees, and respects max occurrences.
 */
test('property: next recurring instance inherits attributes and respects limits', function (): void {
    runPropertyTest(function (): void {
        $task = generateTask($this->team, $this->user, [
            'start_date' => now(),
            'end_date' => now()->addHours(2),
            'estimated_duration_minutes' => 120,
        ]);

        $assignees = User::factory()->count(fake()->numberBetween(1, 3))->create();
        foreach ($assignees as $assignee) {
            $assignee->teams()->attach($this->team);
        }
        $task->assignees()->sync($assignees->pluck('id'));

        $recurrence = generateTaskRecurrence($task, [
            'frequency' => 'daily',
            'interval' => 1,
            'max_occurrences' => 1,
            'is_active' => true,
        ]);

        $nextInstance = $this->recurrenceService->generateNextInstance($task);

        expect($nextInstance)->not->toBeNull();
        expect($nextInstance->parent_id)->toBe($task->id);
        expect($nextInstance->estimated_duration_minutes)->toBe($task->estimated_duration_minutes);
        expect($nextInstance->assignees->pluck('id')->sort()->values()->toArray())
            ->toBe($assignees->pluck('id')->sort()->values()->toArray());

        // After one generation, max occurrences reached; should not generate again
        $secondInstance = $this->recurrenceService->generateNextInstance($task->fresh());
        expect($secondInstance)->toBeNull();

        // Deactivate recurrence should stop generation
        $recurrence->update(['is_active' => false]);
        expect($task->fresh()->isRecurring())->toBeFalse();
    }, 25);
})->group('property');
