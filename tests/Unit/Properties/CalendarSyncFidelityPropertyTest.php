<?php

declare(strict_types=1);

use App\Enums\CalendarSyncStatus;
use App\Models\CalendarEvent;
use App\Models\Team;
use App\Services\CalendarSyncService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/**
 * Feature: communication-collaboration, Property 1: Calendar sync fidelity
 * Validates: Requirements 4.3
 *
 * Property: Calendar events sync bi-directionally with external providers without duplication or drift.
 *
 * For any set of calendar events and external sync operations, the following must hold:
 * 1. Syncing the same external event multiple times should not create duplicates
 * 2. Bi-directional sync should preserve event data without drift
 * 3. Conflict resolution should use last-write-wins strategy
 * 4. Duplicate detection should identify and remove duplicate synced events
 */
it('syncs external events without creating duplicates', function (): void {
    $team = Team::factory()->create();
    $service = new CalendarSyncService;

    // Generate external events
    $externalEvents = [
        [
            'id' => 'ext-event-1',
            'title' => 'Team Meeting',
            'start' => now()->addDay()->toIso8601String(),
            'end' => now()->addDay()->addHour()->toIso8601String(),
            'updated' => now()->toIso8601String(),
        ],
        [
            'id' => 'ext-event-2',
            'title' => 'Client Call',
            'start' => now()->addDays(2)->toIso8601String(),
            'end' => now()->addDays(2)->addHour()->toIso8601String(),
            'updated' => now()->toIso8601String(),
        ],
    ];

    // First sync
    $result1 = $service->syncFromExternal('google', 'calendar-1', $externalEvents, $team->id);

    expect($result1['synced'])->toBe(2)
        ->and($result1['skipped'])->toBe(0)
        ->and($result1['errors'])->toBe(0);

    // Verify events were created
    $events = CalendarEvent::where('team_id', $team->id)
        ->where('sync_provider', 'google')
        ->get();

    expect($events)->toHaveCount(2);

    // Second sync with same events (should skip, not duplicate)
    $result2 = $service->syncFromExternal('google', 'calendar-1', $externalEvents, $team->id);

    expect($result2['synced'])->toBe(0)
        ->and($result2['skipped'])->toBe(2)
        ->and($result2['errors'])->toBe(0);

    // Verify no duplicates were created
    $eventsAfter = CalendarEvent::where('team_id', $team->id)
        ->where('sync_provider', 'google')
        ->get();

    expect($eventsAfter)->toHaveCount(2);
})->group('property', 'calendar-sync');

it('updates events when external version is newer', function (): void {
    $team = Team::factory()->create();
    $service = new CalendarSyncService;

    $externalEvent = [
        'id' => 'ext-event-1',
        'title' => 'Original Title',
        'start' => now()->addDay()->toIso8601String(),
        'end' => now()->addDay()->addHour()->toIso8601String(),
        'updated' => now()->toIso8601String(),
    ];

    // Initial sync
    $service->syncFromExternal('google', 'calendar-1', [$externalEvent], $team->id);

    $event = CalendarEvent::where('sync_external_id', 'ext-event-1')->first();
    expect($event->title)->toBe('Original Title');

    // Simulate external update (newer timestamp)
    $updatedExternalEvent = [
        'id' => 'ext-event-1',
        'title' => 'Updated Title',
        'start' => now()->addDay()->toIso8601String(),
        'end' => now()->addDay()->addHour()->toIso8601String(),
        'updated' => now()->addMinute()->toIso8601String(),
    ];

    // Sync again with updated event
    $result = $service->syncFromExternal('google', 'calendar-1', [$updatedExternalEvent], $team->id);

    expect($result['synced'])->toBe(1)
        ->and($result['skipped'])->toBe(0);

    // Verify event was updated, not duplicated
    $updatedEvent = CalendarEvent::where('sync_external_id', 'ext-event-1')->first();
    expect($updatedEvent->title)->toBe('Updated Title')
        ->and(CalendarEvent::where('sync_external_id', 'ext-event-1')->count())->toBe(1);
})->group('property', 'calendar-sync');

it('resolves conflicts using last-write-wins strategy', function (): void {
    $team = Team::factory()->create();
    $service = new CalendarSyncService;

    // Create local event
    $localEvent = CalendarEvent::factory()->create([
        'team_id' => $team->id,
        'title' => 'Local Title',
        'sync_provider' => 'google',
        'sync_external_id' => 'ext-event-1',
        'sync_status' => CalendarSyncStatus::SYNCED,
        'updated_at' => now(),
    ]);

    // External event with newer timestamp
    $externalEvent = [
        'id' => 'ext-event-1',
        'title' => 'External Title (Newer)',
        'start' => now()->addDay()->toIso8601String(),
        'end' => now()->addDay()->addHour()->toIso8601String(),
        'updated' => now()->addMinute()->toIso8601String(),
    ];

    // Resolve conflict
    $resolved = $service->resolveConflict($localEvent, $externalEvent);

    // External version should win (newer timestamp)
    expect($resolved->title)->toBe('External Title (Newer)');

    // Now test with older external event
    $localEvent->update(['updated_at' => now()->addMinutes(5)]);

    $olderExternalEvent = [
        'id' => 'ext-event-1',
        'title' => 'External Title (Older)',
        'start' => now()->addDay()->toIso8601String(),
        'end' => now()->addDay()->addHour()->toIso8601String(),
        'updated' => now()->toIso8601String(),
    ];

    $resolved2 = $service->resolveConflict($localEvent->fresh(), $olderExternalEvent);

    // Local version should win (newer timestamp)
    expect($resolved2->title)->toBe('External Title (Newer)'); // Should not change
})->group('property', 'calendar-sync');

it('detects and removes duplicate synced events', function (): void {
    $team = Team::factory()->create();
    $service = new CalendarSyncService;

    // Create duplicate events with same external ID (simulating a sync bug)
    $event1 = CalendarEvent::factory()->create([
        'team_id' => $team->id,
        'sync_provider' => 'google',
        'sync_external_id' => 'ext-event-1',
        'title' => 'Duplicate Event 1',
        'updated_at' => now(),
    ]);

    $event2 = CalendarEvent::factory()->create([
        'team_id' => $team->id,
        'sync_provider' => 'google',
        'sync_external_id' => 'ext-event-1',
        'title' => 'Duplicate Event 2',
        'updated_at' => now()->addMinute(),
    ]);

    $event3 = CalendarEvent::factory()->create([
        'team_id' => $team->id,
        'sync_provider' => 'google',
        'sync_external_id' => 'ext-event-1',
        'title' => 'Duplicate Event 3',
        'updated_at' => now()->subMinute(),
    ]);

    // Find duplicates
    $duplicates = $service->findDuplicates($team->id, 'google');

    expect($duplicates)->toHaveCount(3);

    // Deduplicate
    $removed = $service->deduplicateEvents($duplicates);

    expect($removed)->toBe(2);

    // Verify only one event remains (the most recently updated)
    $remaining = CalendarEvent::where('team_id', $team->id)
        ->where('sync_external_id', 'ext-event-1')
        ->get();

    expect($remaining)->toHaveCount(1)
        ->and($remaining->first()->title)->toBe('Duplicate Event 2'); // Most recently updated
})->group('property', 'calendar-sync');

it('preserves event data through bi-directional sync without drift', function (): void {
    $team = Team::factory()->create();
    $service = new CalendarSyncService;

    // Original external event
    $externalEvent = [
        'id' => 'ext-event-1',
        'title' => 'Important Meeting',
        'start' => now()->addDay()->toIso8601String(),
        'end' => now()->addDay()->addHours(2)->toIso8601String(),
        'updated' => now()->toIso8601String(),
    ];

    // Sync from external to local
    $service->syncFromExternal('google', 'calendar-1', [$externalEvent], $team->id);

    $localEvent = CalendarEvent::where('sync_external_id', 'ext-event-1')->first();

    // Verify data integrity
    expect($localEvent->title)->toBe('Important Meeting')
        ->and($localEvent->start_at->toIso8601String())->toBe($externalEvent['start'])
        ->and($localEvent->end_at->toIso8601String())->toBe($externalEvent['end'])
        ->and($localEvent->sync_status)->toBe(CalendarSyncStatus::SYNCED);

    // Simulate multiple sync cycles
    for ($i = 0; $i < 5; $i++) {
        $service->syncFromExternal('google', 'calendar-1', [$externalEvent], $team->id);
    }

    // Verify no drift occurred
    $finalEvent = CalendarEvent::where('sync_external_id', 'ext-event-1')->first();

    expect($finalEvent->title)->toBe('Important Meeting')
        ->and($finalEvent->start_at->toIso8601String())->toBe($externalEvent['start'])
        ->and($finalEvent->end_at->toIso8601String())->toBe($externalEvent['end'])
        ->and(CalendarEvent::where('sync_external_id', 'ext-event-1')->count())->toBe(1);
})->group('property', 'calendar-sync');
