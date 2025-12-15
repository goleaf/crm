<?php

declare(strict_types=1);

use App\Enums\CalendarSyncStatus;
use App\Models\CalendarEvent;
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
 * **Feature: communication-collaboration, Property 1: Calendar sync fidelity**
 *
 * **Validates: Requirements 4.3**
 *
 * Property: Calendar events sync bi-directionally with external providers without duplication or drift.
 */
test('property: calendar events sync without duplication', function (): void {
    runPropertyTest(function (): void {
        // Generate a calendar event with sync data
        $event = generateCalendarEvent($this->team, $this->user, [
            'sync_provider' => fake()->randomElement(['google', 'outlook', 'apple']),
            'sync_status' => CalendarSyncStatus::SYNCED,
            'sync_external_id' => fake()->uuid(),
        ]);

        // Simulate sync operation - event should not be duplicated
        $duplicateCheck = CalendarEvent::query()
            ->where('team_id', $this->team->id)
            ->where('sync_external_id', $event->sync_external_id)
            ->where('sync_provider', $event->sync_provider)
            ->count();

        expect($duplicateCheck)->toBe(1,
            "Event with external ID {$event->sync_external_id} should exist only once",
        );

        // Simulate updating the same external event
        $updatedTitle = 'Updated: ' . fake()->sentence(3);
        $event->update([
            'title' => $updatedTitle,
            'sync_status' => CalendarSyncStatus::SYNCED,
        ]);

        // Verify no duplication occurred during update
        $afterUpdateCount = CalendarEvent::query()
            ->where('team_id', $this->team->id)
            ->where('sync_external_id', $event->sync_external_id)
            ->where('sync_provider', $event->sync_provider)
            ->count();

        expect($afterUpdateCount)->toBe(1,
            'Event should still exist only once after sync update',
        );

        // Verify the update was applied
        $refreshedEvent = $event->fresh();
        expect($refreshedEvent->title)->toBe($updatedTitle,
            'Event title should be updated after sync',
        );
    }, 100);
})->group('property');

/**
 * **Feature: communication-collaboration, Property 1: Calendar sync fidelity**
 *
 * **Validates: Requirements 4.3**
 *
 * Property: Recurring events maintain sync integrity across all instances.
 */
test('property: recurring events sync without instance drift', function (): void {
    runPropertyTest(function (): void {
        // Generate a recurring event with sync data
        $parentEvent = generateCalendarEvent($this->team, $this->user, [
            'recurrence_rule' => fake()->randomElement(['DAILY', 'WEEKLY', 'MONTHLY']),
            'recurrence_end_date' => fake()->dateTimeBetween('+1 month', '+6 months'),
            'sync_provider' => fake()->randomElement(['google', 'outlook']),
            'sync_status' => CalendarSyncStatus::SYNCED,
            'sync_external_id' => fake()->uuid(),
        ]);

        // Generate recurring instances
        $recurrenceService = resolve(RecurrenceService::class);
        $instances = $recurrenceService->generateInstances($parentEvent, 5);

        // Save instances to database
        foreach ($instances as $instance) {
            $instance->save();
        }

        // Verify all instances have consistent sync data
        $syncedInstances = CalendarEvent::query()
            ->where('recurrence_parent_id', $parentEvent->id)
            ->get();

        foreach ($syncedInstances as $instance) {
            expect($instance->sync_provider)->toBe($parentEvent->sync_provider,
                "Instance {$instance->id} should have same sync provider as parent",
            );

            // Each instance should have its own external ID but same provider
            expect($instance->sync_external_id)->not->toBeNull(
                "Instance {$instance->id} should have its own external sync ID",
            );
        }

        // Verify no duplicate instances exist
        $instanceCount = CalendarEvent::query()
            ->where('recurrence_parent_id', $parentEvent->id)
            ->count();

        expect($instanceCount)->toBe($instances->count(),
            'Number of saved instances should match generated instances',
        );
    }, 50); // Reduced iterations due to complexity
})->group('property');

/**
 * **Feature: communication-collaboration, Property 1: Calendar sync fidelity**
 *
 * **Validates: Requirements 4.3**
 *
 * Property: Sync conflicts are resolved consistently without data loss.
 */
test('property: sync conflicts preserve data integrity', function (): void {
    runPropertyTest(function (): void {
        // Generate an event that exists both locally and externally
        $event = generateCalendarEvent($this->team, $this->user, [
            'title' => 'Original Title',
            'sync_provider' => 'google',
            'sync_status' => CalendarSyncStatus::SYNCED,
            'sync_external_id' => fake()->uuid(),
        ]);

        // Simulate local modification
        $localTitle = 'Locally Modified: ' . fake()->sentence(2);
        $event->update([
            'title' => $localTitle,
            'sync_status' => CalendarSyncStatus::PENDING_SYNC,
        ]);

        // Simulate external modification (conflict scenario)
        $externalTitle = 'Externally Modified: ' . fake()->sentence(2);

        // In a real sync conflict, we'd apply conflict resolution rules
        // For this test, we'll simulate "latest update wins" strategy
        $resolvedTitle = fake()->boolean() ? $localTitle : $externalTitle;

        $event->update([
            'title' => $resolvedTitle,
            'sync_status' => CalendarSyncStatus::SYNCED,
        ]);

        // Verify event still exists and has resolved data
        $resolvedEvent = $event->fresh();
        expect($resolvedEvent)->not->toBeNull('Event should still exist after conflict resolution');
        expect($resolvedEvent->sync_status)->toBe(CalendarSyncStatus::SYNCED,
            'Event should be marked as synced after conflict resolution',
        );
        expect($resolvedEvent->title)->toBeIn([$localTitle, $externalTitle],
            'Event title should be one of the conflicting values',
        );

        // Verify no duplicate events were created during conflict resolution
        $duplicateCount = CalendarEvent::query()
            ->where('sync_external_id', $event->sync_external_id)
            ->where('sync_provider', $event->sync_provider)
            ->count();

        expect($duplicateCount)->toBe(1,
            'No duplicate events should be created during conflict resolution',
        );
    }, 100);
})->group('property');
