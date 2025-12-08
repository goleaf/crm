<?php

declare(strict_types=1);

use App\Models\Task;
use App\Models\Team;
use App\Models\User;
use App\Services\TaskRecurrenceService;

use function Pest\Laravel\actingAs;

beforeEach(function (): void {
    $this->team = Team::factory()->create();
    $this->user = User::factory()->create();
    $this->user->teams()->attach($this->team);
    actingAs($this->user);

    $this->recurrenceService = resolve(TaskRecurrenceService::class);
});

/**
 * **Feature: communication-collaboration, Property 7: Recurring rules**
 *
 * **Validates: Requirements 5.1, 5.3**
 *
 * Property: For any task with daily recurrence, the next occurrence should be
 * exactly N days after the current occurrence, where N is the interval.
 */
test('property: daily recurrence generates correct next occurrence date', function (): void {
    runPropertyTest(function (): void {
        // Generate a task with daily recurrence
        $task = generateTask($this->team, $this->user, [
            'start_date' => \Illuminate\Support\Facades\Date::now(),
        ]);

        $interval = fake()->numberBetween(1, 7);
        $recurrence = generateTaskRecurrence($task, [
            'frequency' => 'daily',
            'interval' => $interval,
            'is_active' => true,
        ]);

        // Calculate next occurrence
        $nextDate = $this->recurrenceService->calculateNextOccurrenceDate($task, $recurrence);

        // Property: Next date should be exactly interval days after start
        $expectedDate = $task->start_date->copy()->addDays($interval);

        expect($nextDate)->not->toBeNull();
        expect($nextDate->isSameDay($expectedDate))->toBeTrue(
            "Next occurrence should be {$interval} days after start. Expected: {$expectedDate}, Got: {$nextDate}"
        );
    }, 100);
})->group('property');

/**
 * **Feature: communication-collaboration, Property 7: Recurring rules**
 *
 * **Validates: Requirements 5.1, 5.3**
 *
 * Property: For any task with weekly recurrence, the next occurrence should be
 * exactly N weeks after the current occurrence.
 */
test('property: weekly recurrence generates correct next occurrence date', function (): void {
    runPropertyTest(function (): void {
        $task = generateTask($this->team, $this->user, [
            'start_date' => \Illuminate\Support\Facades\Date::now(),
        ]);

        $interval = fake()->numberBetween(1, 4);
        $recurrence = generateTaskRecurrence($task, [
            'frequency' => 'weekly',
            'interval' => $interval,
            'is_active' => true,
            'days_of_week' => null, // No specific days
        ]);

        $nextDate = $this->recurrenceService->calculateNextOccurrenceDate($task, $recurrence);

        // Property: Next date should be exactly interval weeks after start
        $expectedDate = $task->start_date->copy()->addWeeks($interval);

        expect($nextDate)->not->toBeNull();
        expect($nextDate->isSameDay($expectedDate))->toBeTrue(
            "Next occurrence should be {$interval} weeks after start"
        );
    }, 100);
})->group('property');

/**
 * **Feature: communication-collaboration, Property 7: Recurring rules**
 *
 * **Validates: Requirements 5.1, 5.3**
 *
 * Property: For any task with monthly recurrence, the next occurrence should be
 * exactly N months after the current occurrence.
 */
test('property: monthly recurrence generates correct next occurrence date', function (): void {
    runPropertyTest(function (): void {
        $task = generateTask($this->team, $this->user, [
            'start_date' => \Illuminate\Support\Facades\Date::now(),
        ]);

        $interval = fake()->numberBetween(1, 6);
        $recurrence = generateTaskRecurrence($task, [
            'frequency' => 'monthly',
            'interval' => $interval,
            'is_active' => true,
        ]);

        $nextDate = $this->recurrenceService->calculateNextOccurrenceDate($task, $recurrence);

        // Property: Next date should be exactly interval months after start
        $expectedDate = $task->start_date->copy()->addMonths($interval);

        expect($nextDate)->not->toBeNull();
        expect($nextDate->isSameDay($expectedDate))->toBeTrue(
            "Next occurrence should be {$interval} months after start"
        );
    }, 100);
})->group('property');

/**
 * **Feature: communication-collaboration, Property 7: Recurring rules**
 *
 * **Validates: Requirements 5.1, 5.3**
 *
 * Property: Recurrence with an end date should not generate occurrences after that date.
 */
test('property: recurrence respects end date', function (): void {
    runPropertyTest(function (): void {
        $startDate = \Illuminate\Support\Facades\Date::now();
        $endDate = $startDate->copy()->addDays(fake()->numberBetween(10, 30));

        $task = generateTask($this->team, $this->user, [
            'start_date' => $startDate,
        ]);

        $recurrence = generateTaskRecurrence($task, [
            'frequency' => 'daily',
            'interval' => 1,
            'ends_on' => $endDate,
            'is_active' => true,
        ]);

        // Generate occurrences until well past the end date
        $until = $endDate->copy()->addMonths(1);
        $occurrences = $this->recurrenceService->generateOccurrencesUntil($task, $until);

        // Property: All occurrences should be before or on end date
        foreach ($occurrences as $occurrence) {
            expect($occurrence->start_date->lessThanOrEqualTo($endDate))->toBeTrue(
                "Occurrence at {$occurrence->start_date} should not be after end date {$endDate}"
            );
        }

        // Property: Should not generate occurrence after end date
        $lastOccurrence = $occurrences->last();
        if ($lastOccurrence) {
            $nextAfterLast = $this->recurrenceService->calculateNextOccurrenceDate(
                $lastOccurrence,
                $recurrence
            );

            if ($nextAfterLast !== null) {
                expect($nextAfterLast->lessThanOrEqualTo($endDate))->toBeTrue(
                    'Next occurrence after last should not exceed end date'
                );
            }
        }
    }, 100);
})->group('property');

/**
 * **Feature: communication-collaboration, Property 7: Recurring rules**
 *
 * **Validates: Requirements 5.1, 5.3**
 *
 * Property: Generated task occurrences should preserve the original task's properties.
 */
test('property: recurring task occurrences preserve original properties', function (): void {
    runPropertyTest(function (): void {
        // Generate a task with various properties
        $task = generateTask($this->team, $this->user, [
            'start_date' => \Illuminate\Support\Facades\Date::now(),
            'end_date' => \Illuminate\Support\Facades\Date::now()->addHours(2),
            'estimated_duration_minutes' => fake()->numberBetween(30, 240),
        ]);

        // Add assignees
        $assignees = User::factory()->count(fake()->numberBetween(1, 3))->create();
        foreach ($assignees as $assignee) {
            $assignee->teams()->attach($this->team);
        }
        $task->assignees()->sync($assignees->pluck('id'));

        // Add checklist items
        for ($i = 0; $i < fake()->numberBetween(1, 5); $i++) {
            generateTaskChecklistItem($task);
        }

        $recurrence = generateTaskRecurrence($task, [
            'frequency' => 'daily',
            'interval' => 1,
            'is_active' => true,
        ]);

        // Generate next occurrence
        $nextTask = $this->recurrenceService->generateNextOccurrence($task);

        expect($nextTask)->not->toBeNull();

        // Property: Title should be preserved
        expect($nextTask->title)->toBe($task->title);

        // Property: Team should be preserved
        expect($nextTask->team_id)->toBe($task->team_id);

        // Property: Estimated duration should be preserved
        expect($nextTask->estimated_duration_minutes)->toBe($task->estimated_duration_minutes);

        // Property: Assignees should be preserved
        expect($nextTask->assignees->pluck('id')->sort()->values()->toArray())
            ->toBe($task->assignees->pluck('id')->sort()->values()->toArray());

        // Property: Checklist items should be preserved (but not completed)
        expect($nextTask->checklistItems->count())->toBe($task->checklistItems->count());
        foreach ($nextTask->checklistItems as $item) {
            expect($item->is_completed)->toBeFalse('Checklist items should not be completed in new occurrence');
        }

        // Property: Percent complete should be reset to 0
        expect((float) $nextTask->percent_complete)->toBe(0.0);

        // Property: Duration between start and end should be preserved
        if ($task->end_date !== null && $task->start_date !== null) {
            $originalDuration = $task->start_date->diffInMinutes($task->end_date);
            $newDuration = $nextTask->start_date->diffInMinutes($nextTask->end_date);

            expect($newDuration)->toBe($originalDuration);
        }
    }, 50); // Fewer iterations due to complexity
})->group('property');

/**
 * **Feature: communication-collaboration, Property 7: Recurring rules**
 *
 * **Validates: Requirements 5.3**
 *
 * Property: Inactive recurrence patterns should not generate new occurrences.
 */
test('property: inactive recurrence does not generate occurrences', function (): void {
    runPropertyTest(function (): void {
        $task = generateTask($this->team, $this->user, [
            'start_date' => \Illuminate\Support\Facades\Date::now(),
        ]);

        $recurrence = generateTaskRecurrence($task, [
            'frequency' => 'daily',
            'interval' => 1,
            'is_active' => false, // Inactive
        ]);

        // Try to generate next occurrence
        $nextTask = $this->recurrenceService->generateNextOccurrence($task);

        // Property: Should not generate occurrence when inactive
        expect($nextTask)->toBeNull(
            'Inactive recurrence should not generate new occurrences'
        );
    }, 100);
})->group('property');

/**
 * **Feature: communication-collaboration, Property 7: Recurring rules**
 *
 * **Validates: Requirements 5.1**
 *
 * Property: Yearly recurrence generates occurrences exactly N years apart.
 */
test('property: yearly recurrence generates correct intervals', function (): void {
    runPropertyTest(function (): void {
        $task = generateTask($this->team, $this->user, [
            'start_date' => \Illuminate\Support\Facades\Date::now(),
        ]);

        $interval = fake()->numberBetween(1, 3);
        $recurrence = generateTaskRecurrence($task, [
            'frequency' => 'yearly',
            'interval' => $interval,
            'is_active' => true,
        ]);

        $nextDate = $this->recurrenceService->calculateNextOccurrenceDate($task, $recurrence);

        // Property: Next date should be exactly interval years after start
        $expectedDate = $task->start_date->copy()->addYears($interval);

        expect($nextDate)->not->toBeNull();
        expect($nextDate->isSameDay($expectedDate))->toBeTrue(
            "Next occurrence should be {$interval} years after start"
        );
    }, 100);
})->group('property');

/**
 * **Feature: communication-collaboration, Property 7: Recurring rules**
 *
 * **Validates: Requirements 5.3**
 *
 * Property: Updating series occurrences should affect all future occurrences.
 */
test('property: series updates affect all future occurrences', function (): void {
    runPropertyTest(function (): void {
        $task = generateTask($this->team, $this->user, [
            'start_date' => \Illuminate\Support\Facades\Date::now(),
            'title' => 'Original Title',
        ]);

        $recurrence = generateTaskRecurrence($task, [
            'frequency' => 'daily',
            'interval' => 1,
            'is_active' => true,
        ]);

        // Generate several future occurrences
        $occurrenceCount = fake()->numberBetween(3, 10);
        for ($i = 0; $i < $occurrenceCount; $i++) {
            $this->recurrenceService->generateNextOccurrence($task);
        }

        // Update the series
        $newTitle = 'Updated Title '.fake()->word();
        $updatedCount = $this->recurrenceService->updateSeriesOccurrences($task, [
            'title' => $newTitle,
        ]);

        // Property: Should have updated some occurrences
        expect($updatedCount)->toBeGreaterThan(0);

        // Property: All future occurrences should have new title
        $futureOccurrences = Task::query()
            ->where('team_id', $task->team_id)
            ->where('title', $newTitle)
            ->where('start_date', '>', now())
            ->get();

        expect($futureOccurrences->count())->toBe($updatedCount);
    }, 50); // Fewer iterations due to complexity
})->group('property');
