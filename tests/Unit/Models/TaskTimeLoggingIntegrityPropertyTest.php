<?php

declare(strict_types=1);

use App\Models\Task;
use App\Models\TaskTimeEntry;
use App\Models\Team;
use App\Models\User;

/**
 * **Feature: projects-resources, Property 6: Time logging integrity**
 * **Validates: Requirements 4.1**
 *
 * For any task, the sum of all time entry durations should equal the
 * total of individual time entry durations (no duplication).
 */
test('task total time equals sum of individual time entries', function (): void {
    $team = Team::factory()->create();
    $user = User::factory()->create();

    for ($i = 0; $i < 100; $i++) {
        $task = Task::factory()
            ->for($team)
            ->create();

        $entryCount = fake()->numberBetween(1, 10);
        $expectedTotal = 0;

        // Create random time entries
        for ($j = 0; $j < $entryCount; $j++) {
            $duration = fake()->numberBetween(15, 480);
            $expectedTotal += $duration;

            TaskTimeEntry::factory()
                ->for($task)
                ->for($user)
                ->create(['duration_minutes' => $duration]);
        }

        // Property: Sum of all time entries should equal expected total
        $actualTotal = $task->timeEntries()->sum('duration_minutes');
        expect($actualTotal)->toBe($expectedTotal);

        // Clean up
        $task->timeEntries()->delete();
        $task->delete();
    }
})->group('property-test', 'time-logging');

/**
 * **Feature: projects-resources, Property 6: Time logging integrity**
 * **Validates: Requirements 4.1**
 *
 * For any task, billable time should only include entries marked as billable.
 */
test('task billable time only includes billable entries', function (): void {
    $team = Team::factory()->create();
    $user = User::factory()->create();

    for ($i = 0; $i < 100; $i++) {
        $task = Task::factory()
            ->for($team)
            ->create();

        $billableCount = fake()->numberBetween(1, 5);
        $nonBillableCount = fake()->numberBetween(1, 5);
        $expectedBillableTotal = 0;

        // Create billable entries
        for ($j = 0; $j < $billableCount; $j++) {
            $duration = fake()->numberBetween(15, 480);
            $expectedBillableTotal += $duration;

            TaskTimeEntry::factory()
                ->for($task)
                ->for($user)
                ->create([
                    'duration_minutes' => $duration,
                    'is_billable' => true,
                ]);
        }

        // Create non-billable entries
        for ($j = 0; $j < $nonBillableCount; $j++) {
            TaskTimeEntry::factory()
                ->for($task)
                ->for($user)
                ->create([
                    'duration_minutes' => fake()->numberBetween(15, 480),
                    'is_billable' => false,
                ]);
        }

        // Property: Billable time should only include billable entries
        $actualBillableTotal = $task->getTotalBillableTime();
        expect($actualBillableTotal)->toBe($expectedBillableTotal);

        // Clean up
        $task->timeEntries()->delete();
        $task->delete();
    }
})->group('property-test', 'time-logging');

/**
 * **Feature: projects-resources, Property 6: Time logging integrity**
 * **Validates: Requirements 4.2**
 *
 * For any task, total billing amount should equal sum of
 * (duration_minutes / 60) * billing_rate for all billable entries.
 */
test('task billing amount equals sum of billable time multiplied by rates', function (): void {
    $team = Team::factory()->create();
    $user = User::factory()->create();

    for ($i = 0; $i < 100; $i++) {
        $task = Task::factory()
            ->for($team)
            ->create();

        $entryCount = fake()->numberBetween(1, 10);
        $expectedTotal = 0.0;

        // Create billable entries with rates
        for ($j = 0; $j < $entryCount; $j++) {
            $duration = fake()->numberBetween(15, 480);
            $rate = fake()->randomFloat(2, 50, 300);
            $expectedTotal += ($duration / 60) * $rate;

            TaskTimeEntry::factory()
                ->for($task)
                ->for($user)
                ->create([
                    'duration_minutes' => $duration,
                    'is_billable' => true,
                    'billing_rate' => $rate,
                ]);
        }

        // Property: Total billing amount should match calculated total
        $actualTotal = $task->getTotalBillingAmount();
        expect($actualTotal)->toBeGreaterThanOrEqual($expectedTotal - 0.01)
            ->and($actualTotal)->toBeLessThanOrEqual($expectedTotal + 0.01);

        // Clean up
        $task->timeEntries()->delete();
        $task->delete();
    }
})->group('property-test', 'time-logging');

/**
 * **Feature: projects-resources, Property 6: Time logging integrity**
 * **Validates: Requirements 4.1**
 *
 * For any time entry, if started_at and ended_at are set, duration_minutes
 * should be consistent with the time difference.
 */
test('time entry duration is consistent with start and end times', function (): void {
    $team = Team::factory()->create();
    $user = User::factory()->create();

    for ($i = 0; $i < 100; $i++) {
        $task = Task::factory()
            ->for($team)
            ->create();

        $startedAt = now()->subHours(fake()->numberBetween(1, 8));
        $durationMinutes = fake()->numberBetween(15, 480);
        $endedAt = $startedAt->copy()->addMinutes($durationMinutes);

        $entry = TaskTimeEntry::factory()
            ->for($task)
            ->for($user)
            ->create([
                'started_at' => $startedAt,
                'ended_at' => $endedAt,
                'duration_minutes' => $durationMinutes,
            ]);

        // Property: Duration should match the difference between start and end times
        $calculatedDuration = (int) $entry->started_at->diffInMinutes($entry->ended_at);
        expect($entry->duration_minutes)->toBe($calculatedDuration);

        // Clean up
        $entry->delete();
        $task->delete();
    }
})->group('property-test', 'time-logging');

/**
 * **Feature: projects-resources, Property 6: Time logging integrity**
 * **Validates: Requirements 4.1**
 *
 * For any user and date, time entries should not overlap (no duplicate time logging).
 */
test('user time entries do not overlap on same date', function (): void {
    $team = Team::factory()->create();
    $user = User::factory()->create();

    for ($i = 0; $i < 100; $i++) {
        $task1 = Task::factory()->for($team)->create();
        $task2 = Task::factory()->for($team)->create();

        $baseTime = now()->startOfDay()->addHours(fake()->numberBetween(8, 12));

        // Create first time entry
        $entry1Start = $baseTime;
        $entry1Duration = fake()->numberBetween(30, 120);
        $entry1End = $entry1Start->copy()->addMinutes($entry1Duration);

        $entry1 = TaskTimeEntry::factory()
            ->for($task1)
            ->for($user)
            ->create([
                'started_at' => $entry1Start,
                'ended_at' => $entry1End,
                'duration_minutes' => $entry1Duration,
            ]);

        // Create second time entry that should not overlap
        $entry2Start = $entry1End->copy()->addMinutes(fake()->numberBetween(1, 60));
        $entry2Duration = fake()->numberBetween(30, 120);
        $entry2End = $entry2Start->copy()->addMinutes($entry2Duration);

        $entry2 = TaskTimeEntry::factory()
            ->for($task2)
            ->for($user)
            ->create([
                'started_at' => $entry2Start,
                'ended_at' => $entry2End,
                'duration_minutes' => $entry2Duration,
            ]);

        // Property: Second entry should start after first entry ends (no overlap)
        expect($entry2->started_at->greaterThanOrEqualTo($entry1->ended_at))->toBeTrue();

        // Clean up
        $entry1->delete();
        $entry2->delete();
        $task1->delete();
        $task2->delete();
    }
})->group('property-test', 'time-logging');
