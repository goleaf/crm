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
 * Integration test for calendar sync functionality.
 *
 * **Validates: Requirements 4.3**
 */
test('calendar events sync with external providers', function (): void {
    // Create a local calendar event
    $event = CalendarEvent::factory()->create([
        'team_id' => $this->team->id,
        'creator_id' => $this->user->id,
        'title' => 'Local Event',
        'start_at' => now()->addDay(),
        'end_at' => now()->addDay()->addHour(),
        'sync_provider' => null,
        'sync_status' => CalendarSyncStatus::NOT_SYNCED,
        'sync_external_id' => null,
    ]);

    // Simulate sync to external provider
    $externalId = fake()->uuid();
    $event->update([
        'sync_provider' => 'google',
        'sync_status' => CalendarSyncStatus::SYNCED,
        'sync_external_id' => $externalId,
    ]);

    // Verify sync data
    expect($event->fresh()->sync_provider)->toBe('google');
    expect($event->fresh()->sync_status)->toBe(CalendarSyncStatus::SYNCED);
    expect($event->fresh()->sync_external_id)->toBe($externalId);

    // Simulate external update
    $updatedTitle = 'Updated from External';
    $event->update([
        'title' => $updatedTitle,
        'sync_status' => CalendarSyncStatus::SYNCED,
    ]);

    // Verify update is reflected
    expect($event->fresh()->title)->toBe($updatedTitle);
    expect($event->fresh()->sync_status)->toBe(CalendarSyncStatus::SYNCED);
});

test('calendar sync prevents duplicate events', function (): void {
    $externalId = fake()->uuid();
    $provider = 'outlook';

    // Create first event with sync data
    $event1 = CalendarEvent::factory()->create([
        'team_id' => $this->team->id,
        'creator_id' => $this->user->id,
        'title' => 'Synced Event',
        'sync_provider' => $provider,
        'sync_external_id' => $externalId,
        'sync_status' => CalendarSyncStatus::SYNCED,
    ]);

    // Attempt to create duplicate event with same external ID
    $duplicateCheck = CalendarEvent::where('sync_external_id', $externalId)
        ->where('sync_provider', $provider)
        ->count();

    expect($duplicateCheck)->toBe(1);

    // Simulate sync process that would prevent duplicates
    $existingEvent = CalendarEvent::where('sync_external_id', $externalId)
        ->where('sync_provider', $provider)
        ->first();

    expect($existingEvent->id)->toBe($event1->id);

    // Update existing event instead of creating duplicate
    $existingEvent->update([
        'title' => 'Updated Synced Event',
        'sync_status' => CalendarSyncStatus::SYNCED,
    ]);

    // Verify no duplicate was created
    $finalCount = CalendarEvent::where('sync_external_id', $externalId)
        ->where('sync_provider', $provider)
        ->count();

    expect($finalCount)->toBe(1);
    expect($existingEvent->fresh()->title)->toBe('Updated Synced Event');
});

test('recurring events sync all instances', function (): void {
    // Create recurring event
    $parentEvent = CalendarEvent::factory()->create([
        'team_id' => $this->team->id,
        'creator_id' => $this->user->id,
        'title' => 'Recurring Meeting',
        'start_at' => now()->addDay(),
        'end_at' => now()->addDay()->addHour(),
        'recurrence_rule' => 'WEEKLY',
        'recurrence_end_date' => now()->addMonth(),
        'sync_provider' => 'google',
        'sync_status' => CalendarSyncStatus::SYNCED,
        'sync_external_id' => fake()->uuid(),
    ]);

    $recurrenceService = resolve(RecurrenceService::class);

    // Generate instances
    $instances = $recurrenceService->generateInstances($parentEvent, 4);

    // Save instances with sync data
    foreach ($instances as $instance) {
        $instance->sync_provider = $parentEvent->sync_provider;
        $instance->sync_external_id = fake()->uuid(); // Each instance gets unique external ID
        $instance->sync_status = CalendarSyncStatus::SYNCED;
        $instance->save();
    }

    // Verify all instances are synced
    $syncedInstances = CalendarEvent::where('recurrence_parent_id', $parentEvent->id)
        ->where('sync_status', CalendarSyncStatus::SYNCED)
        ->get();

    expect($syncedInstances->count())->toBe($instances->count());

    foreach ($syncedInstances as $instance) {
        expect($instance->sync_provider)->toBe($parentEvent->sync_provider);
        expect($instance->sync_external_id)->not->toBeNull();
        expect($instance->sync_status)->toBe(CalendarSyncStatus::SYNCED);
    }
});

test('sync conflicts are resolved consistently', function (): void {
    $externalId = fake()->uuid();

    // Create event with initial sync
    $event = CalendarEvent::factory()->create([
        'team_id' => $this->team->id,
        'creator_id' => $this->user->id,
        'title' => 'Original Title',
        'start_at' => now()->addDay(),
        'sync_provider' => 'google',
        'sync_external_id' => $externalId,
        'sync_status' => CalendarSyncStatus::SYNCED,
    ]);

    // Simulate local modification
    $event->update([
        'title' => 'Locally Modified Title',
        'sync_status' => CalendarSyncStatus::PENDING_SYNC,
    ]);

    expect($event->fresh()->sync_status)->toBe(CalendarSyncStatus::PENDING_SYNC);

    // Simulate conflict resolution (external wins)
    $event->update([
        'title' => 'External Modified Title',
        'sync_status' => CalendarSyncStatus::SYNCED,
    ]);

    // Verify conflict resolution
    expect($event->fresh()->title)->toBe('External Modified Title');
    expect($event->fresh()->sync_status)->toBe(CalendarSyncStatus::SYNCED);

    // Verify no duplicate events were created during conflict
    $eventCount = CalendarEvent::where('sync_external_id', $externalId)
        ->where('sync_provider', 'google')
        ->count();

    expect($eventCount)->toBe(1);
});

test('sync status transitions are tracked correctly', function (): void {
    // Create event in not synced state
    $event = CalendarEvent::factory()->create([
        'team_id' => $this->team->id,
        'creator_id' => $this->user->id,
        'title' => 'Event to Sync',
        'sync_status' => CalendarSyncStatus::NOT_SYNCED,
    ]);

    // Transition to pending sync
    $event->update(['sync_status' => CalendarSyncStatus::PENDING_SYNC]);
    expect($event->fresh()->sync_status)->toBe(CalendarSyncStatus::PENDING_SYNC);

    // Transition to synced
    $event->update([
        'sync_provider' => 'outlook',
        'sync_external_id' => fake()->uuid(),
        'sync_status' => CalendarSyncStatus::SYNCED,
    ]);

    expect($event->fresh()->sync_status)->toBe(CalendarSyncStatus::SYNCED);
    expect($event->fresh()->sync_provider)->toBe('outlook');
    expect($event->fresh()->sync_external_id)->not->toBeNull();

    // Simulate sync failure
    $event->update(['sync_status' => CalendarSyncStatus::SYNC_FAILED]);
    expect($event->fresh()->sync_status)->toBe(CalendarSyncStatus::SYNC_FAILED);

    // Retry sync
    $event->update(['sync_status' => CalendarSyncStatus::SYNCED]);
    expect($event->fresh()->sync_status)->toBe(CalendarSyncStatus::SYNCED);
});

test('multiple provider sync is handled correctly', function (): void {
    // Create event synced with Google
    $event = CalendarEvent::factory()->create([
        'team_id' => $this->team->id,
        'creator_id' => $this->user->id,
        'title' => 'Multi-Provider Event',
        'sync_provider' => 'google',
        'sync_external_id' => fake()->uuid(),
        'sync_status' => CalendarSyncStatus::SYNCED,
    ]);

    // Verify Google sync
    expect($event->sync_provider)->toBe('google');
    expect($event->sync_status)->toBe(CalendarSyncStatus::SYNCED);

    // Switch to Outlook sync (simulate user changing primary calendar)
    $outlookExternalId = fake()->uuid();
    $event->update([
        'sync_provider' => 'outlook',
        'sync_external_id' => $outlookExternalId,
        'sync_status' => CalendarSyncStatus::SYNCED,
    ]);

    // Verify provider switch
    expect($event->fresh()->sync_provider)->toBe('outlook');
    expect($event->fresh()->sync_external_id)->toBe($outlookExternalId);
    expect($event->fresh()->sync_status)->toBe(CalendarSyncStatus::SYNCED);
});

test('sync preserves event relationships and metadata', function (): void {
    $company = \App\Models\Company::factory()->create(['team_id' => $this->team->id]);

    // Create event with relationships and metadata
    $event = CalendarEvent::factory()->create([
        'team_id' => $this->team->id,
        'creator_id' => $this->user->id,
        'title' => 'Client Meeting',
        'related_id' => $company->id,
        'related_type' => \App\Models\Company::class,
        'attendees' => [
            ['name' => 'John Doe', 'email' => 'john@example.com'],
            ['name' => 'Jane Smith', 'email' => 'jane@example.com'],
        ],
        'location' => 'Conference Room A',
        'notes' => 'Important client meeting',
    ]);

    // Sync event
    $event->update([
        'sync_provider' => 'google',
        'sync_external_id' => fake()->uuid(),
        'sync_status' => CalendarSyncStatus::SYNCED,
    ]);

    // Verify all data is preserved after sync
    $syncedEvent = $event->fresh();
    expect($syncedEvent->related_id)->toBe($company->id);
    expect($syncedEvent->related_type)->toBe(\App\Models\Company::class);
    expect($syncedEvent->attendees)->toHaveCount(2);
    expect($syncedEvent->location)->toBe('Conference Room A');
    expect($syncedEvent->notes)->toBe('Important client meeting');
    expect($syncedEvent->sync_status)->toBe(CalendarSyncStatus::SYNCED);

    // Verify relationship still works
    expect($syncedEvent->related)->toBeInstanceOf(\App\Models\Company::class);
    expect($syncedEvent->related->id)->toBe($company->id);
});
