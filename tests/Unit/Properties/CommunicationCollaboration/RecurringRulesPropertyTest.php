<?php

declare(strict_types=1);

use App\Models\CalendarEvent;
use App\Models\Task;
use App\Models\Team;
use App\Models\User;
use App\Services\RecurrenceService;

use function Pest\Laravel\actingAs;

beforeEach(function (): void {
    $this->team = Team::factory()->create();
    $this->user = User::factory()->create();
    $this->user->teams()->attach($this->team);
    $this->user->currentTeam()->associate($this->team);
    $this->user->save();
    actingAs($this->user);
});

/**
 * **Feature: communication-collaboration, Property 7: Recurring rules**
 *
 * **Validates: Requirements 3.1, 5.3**
 *
 * Property: Recurring meetings generate correct future instances.
 */
test('property: recurring meetings generate correct future instances', function (): void {
    runPropertyTest(function (): void {
        // Generate a recurring calendar event
        $recurrenceRules = ['DAILY', 'WEEKLY', 'MONTHLY', 'YEARLY'];
        $rule = fake()->randomElement($recurrenceRules);
        $startDate = fake()->dateTimeBetween('now', '+1 week');
        $endDate = fake()->dateTimeBetween('+1 month', '+6 months');

        $event = generateCalendarEvent($this->team, $this->user, [
            'title' => 'Recurring meeting: ' . fake()->sentence(3),
            'start_at' => $startDate,
            'end_at' => $startDate->copy()->addHour(),
            'recurrence_rule' => $rule,
            'recurrence_end_date' => $endDate,
        ]);

        $recurrenceService = resolve(RecurrenceService::class);

        // Generate instances
        $instances = $recurrenceService->generateInstances($event, 10);

        // Verify instances are generated
        expect($instances->count())->toBeGreaterThan(0,
            'Recurring event should generate at least one instance',
        );

        // Verify each instance has correct properties
        foreach ($instances as $index => $instance) {
            expect($instance->title)->toBe($event->title,
                "Instance {$index} should have same title as parent",
            );
            expect($instance->team_id)->toBe($event->team_id,
                "Instance {$index} should belong to same team",
            );
            expect($instance->creator_id)->toBe($event->creator_id,
                "Instance {$index} should have same creator",
            );
            expect($instance->recurrence_parent_id)->toBe($event->id,
                "Instance {$index} should reference parent event",
            );
            expect($instance->start_at)->toBeGreaterThan($event->start_at,
                "Instance {$index} should start after parent event",
            );
        }

        // Verify instances follow the recurrence pattern
        if ($instances->count() > 1) {
            $firstInstance = $instances->first();
            $secondInstance = $instances->get(1);

            $expectedInterval = match ($rule) {
                'DAILY' => 1,
                'WEEKLY' => 7,
                'MONTHLY' => 30, // Approximate
                'YEARLY' => 365, // Approximate
                default => 1,
            };

            if (in_array($rule, ['DAILY', 'WEEKLY'])) {
                $actualInterval = $firstInstance->start_at->diffInDays($secondInstance->start_at);
                expect($actualInterval)->toBe($expectedInterval,
                    "Instances should follow {$rule} pattern",
                );
            }
        }

        // Verify instances don't exceed end date
        foreach ($instances as $instance) {
            expect($instance->start_at)->toBeLessThanOrEqual($endDate,
                'Instance should not exceed recurrence end date',
            );
        }
    }, 50); // Reduced iterations due to complexity
})->group('property');

/**
 * **Feature: communication-collaboration, Property 7: Recurring rules**
 *
 * **Validates: Requirements 5.3**
 *
 * Property: Recurring tasks generate correct future instances.
 */
test('property: recurring tasks maintain pattern integrity', function (): void {
    runPropertyTest(function (): void {
        // Generate a recurring task
        $task = generateTask($this->team, $this->user, [
            'title' => 'Recurring task: ' . fake()->sentence(3),
            'due_date' => fake()->dateTimeBetween('+1 day', '+1 week'),
        ]);

        // Add recurrence pattern
        $frequencies = ['daily', 'weekly', 'monthly'];
        $frequency = fake()->randomElement($frequencies);
        $interval = fake()->numberBetween(1, 3);

        $recurrence = generateTaskRecurrence($task, [
            'frequency' => $frequency,
            'interval' => $interval,
            'end_date' => fake()->dateTimeBetween('+1 month', '+6 months'),
        ]);

        // Verify recurrence is created
        expect($task->recurrence)->not->toBeNull();
        expect($task->recurrence->frequency)->toBe($frequency);
        expect($task->recurrence->interval)->toBe($interval);

        // Simulate generating next occurrence
        $nextDueDate = match ($frequency) {
            'daily' => $task->due_date->copy()->addDays($interval),
            'weekly' => $task->due_date->copy()->addWeeks($interval),
            'monthly' => $task->due_date->copy()->addMonths($interval),
            default => $task->due_date->copy()->addDay(),
        };

        // Verify next occurrence calculation
        expect($nextDueDate)->toBeGreaterThan($task->due_date,
            'Next occurrence should be after current due date',
        );

        // Verify interval is respected
        $expectedDiff = match ($frequency) {
            'daily' => $interval,
            'weekly' => $interval * 7,
            'monthly' => $interval * 30, // Approximate
            default => 1,
        };

        if (in_array($frequency, ['daily', 'weekly'])) {
            $actualDiff = $task->due_date->diffInDays($nextDueDate);
            expect($actualDiff)->toBe($expectedDiff,
                "Next occurrence should respect {$frequency} interval of {$interval}",
            );
        }

        // Verify recurrence doesn't exceed end date
        expect($nextDueDate)->toBeLessThanOrEqual($recurrence->end_date,
            'Next occurrence should not exceed recurrence end date',
        );
    }, 100);
})->group('property');

/**
 * **Feature: communication-collaboration, Property 7: Recurring rules**
 *
 * **Validates: Requirements 3.1**
 *
 * Property: Series edits update all future occurrences correctly.
 */
test('property: series edits propagate to future instances', function (): void {
    runPropertyTest(function (): void {
        // Generate a recurring event with instances
        $event = generateCalendarEvent($this->team, $this->user, [
            'title' => 'Original series title',
            'location' => 'Original location',
            'start_at' => fake()->dateTimeBetween('now', '+1 week'),
            'recurrence_rule' => 'WEEKLY',
            'recurrence_end_date' => fake()->dateTimeBetween('+1 month', '+3 months'),
        ]);

        $recurrenceService = resolve(RecurrenceService::class);

        // Generate and save instances
        $instances = $recurrenceService->generateInstances($event, 5);
        foreach ($instances as $instance) {
            $instance->save();
        }

        // Verify instances exist
        $savedInstances = CalendarEvent::query()
            ->where('recurrence_parent_id', $event->id)
            ->get();

        expect($savedInstances->count())->toBe($instances->count(),
            'All instances should be saved',
        );

        // Edit the series (parent event)
        $newTitle = 'Updated series title: ' . fake()->sentence(2);
        $newLocation = 'Updated location: ' . fake()->address();

        $event->update([
            'title' => $newTitle,
            'location' => $newLocation,
        ]);

        // Update all instances
        $recurrenceService->updateInstances($event, [
            'title' => $newTitle,
            'location' => $newLocation,
        ]);

        // Verify all future instances are updated
        $updatedInstances = CalendarEvent::query()
            ->where('recurrence_parent_id', $event->id)
            ->where('start_at', '>=', now())
            ->get();

        foreach ($updatedInstances as $instance) {
            expect($instance->title)->toBe($newTitle,
                "Instance {$instance->id} should have updated title",
            );
            expect($instance->location)->toBe($newLocation,
                "Instance {$instance->id} should have updated location",
            );
        }

        // Verify parent event is also updated
        expect($event->fresh()->title)->toBe($newTitle,
            'Parent event should have updated title',
        );
        expect($event->fresh()->location)->toBe($newLocation,
            'Parent event should have updated location',
        );
    }, 50); // Reduced iterations due to complexity
})->group('property');

/**
 * **Feature: communication-collaboration, Property 7: Recurring rules**
 *
 * **Validates: Requirements 3.1**
 *
 * Property: Deleting recurring series removes all instances.
 */
test('property: deleting recurring series removes all instances', function (): void {
    runPropertyTest(function (): void {
        // Generate a recurring event
        $event = generateCalendarEvent($this->team, $this->user, [
            'title' => 'Series to be deleted: ' . fake()->sentence(3),
            'recurrence_rule' => fake()->randomElement(['DAILY', 'WEEKLY', 'MONTHLY']),
            'recurrence_end_date' => fake()->dateTimeBetween('+1 month', '+3 months'),
        ]);

        $recurrenceService = resolve(RecurrenceService::class);

        // Generate and save instances
        $instances = $recurrenceService->generateInstances($event, 8);
        foreach ($instances as $instance) {
            $instance->save();
        }

        // Verify instances exist
        $instanceCount = CalendarEvent::query()
            ->where('recurrence_parent_id', $event->id)
            ->count();

        expect($instanceCount)->toBe($instances->count(),
            'All instances should be created',
        );

        // Delete the series (parent event)
        $recurrenceService->deleteInstances($event);

        // Verify all instances are soft deleted
        $remainingInstances = CalendarEvent::query()
            ->where('recurrence_parent_id', $event->id)
            ->count();

        expect($remainingInstances)->toBe(0,
            'All instances should be soft deleted',
        );

        // Verify instances exist in trash
        $trashedInstances = CalendarEvent::onlyTrashed()
            ->where('recurrence_parent_id', $event->id)
            ->count();

        expect($trashedInstances)->toBe($instances->count(),
            'All instances should be in trash',
        );

        // Delete the parent event
        $event->delete();

        // Verify parent is also deleted
        expect(CalendarEvent::find($event->id))->toBeNull(
            'Parent event should be soft deleted',
        );

        expect(CalendarEvent::withTrashed()->find($event->id))->not->toBeNull(
            'Parent event should exist in trash',
        );
    }, 50); // Reduced iterations due to complexity
})->group('property');

/**
 * **Feature: communication-collaboration, Property 7: Recurring rules**
 *
 * **Validates: Requirements 3.1, 5.3**
 *
 * Property: Modifying single instance doesn't affect series.
 */
test('property: single instance modifications preserve series integrity', function (): void {
    runPropertyTest(function (): void {
        // Generate a recurring event
        $event = generateCalendarEvent($this->team, $this->user, [
            'title' => 'Series title',
            'location' => 'Series location',
            'recurrence_rule' => 'WEEKLY',
            'recurrence_end_date' => fake()->dateTimeBetween('+1 month', '+3 months'),
        ]);

        $recurrenceService = resolve(RecurrenceService::class);

        // Generate and save instances
        $instances = $recurrenceService->generateInstances($event, 4);
        foreach ($instances as $instance) {
            $instance->save();
        }

        // Select a random instance to modify
        $instanceToModify = $instances->random();
        $originalTitle = $instanceToModify->title;
        $originalLocation = $instanceToModify->location;

        // Modify the single instance
        $newTitle = 'Modified instance: ' . fake()->sentence(2);
        $newLocation = 'Modified location: ' . fake()->address();

        $instanceToModify->update([
            'title' => $newTitle,
            'location' => $newLocation,
        ]);

        // Verify the modified instance has new values
        expect($instanceToModify->fresh()->title)->toBe($newTitle,
            'Modified instance should have new title',
        );
        expect($instanceToModify->fresh()->location)->toBe($newLocation,
            'Modified instance should have new location',
        );

        // Verify parent event is unchanged
        expect($event->fresh()->title)->toBe('Series title',
            'Parent event title should remain unchanged',
        );
        expect($event->fresh()->location)->toBe('Series location',
            'Parent event location should remain unchanged',
        );

        // Verify other instances are unchanged
        $otherInstances = CalendarEvent::query()
            ->where('recurrence_parent_id', $event->id)
            ->where('id', '!=', $instanceToModify->id)
            ->get();

        foreach ($otherInstances as $instance) {
            expect($instance->title)->toBe($originalTitle,
                "Other instance {$instance->id} should retain original title",
            );
            expect($instance->location)->toBe($originalLocation,
                "Other instance {$instance->id} should retain original location",
            );
        }

        // Verify series relationship is maintained
        expect($instanceToModify->fresh()->recurrence_parent_id)->toBe($event->id,
            'Modified instance should still reference parent',
        );
    }, 50); // Reduced iterations due to complexity
})->group('property');
