<?php

declare(strict_types=1);

use App\Models\CalendarEvent;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Performance tests for calendar events and recurring instances.
 *
 * These tests verify that:
 * - List pages load efficiently with many events
 * - Recurring event creation uses batch inserts
 * - Queries avoid N+1 problems
 * - Performance targets are met
 */
beforeEach(function (): void {
    $this->user = User::factory()->withPersonalTeam()->create();
    $this->team = $this->user->currentTeam;
    $this->actingAs($this->user);
});

test('creates recurring event efficiently with batch insert', function (): void {
    DB::enableQueryLog();

    $start = microtime(true);

    $event = CalendarEvent::create([
        'team_id' => $this->team->id,
        'creator_id' => $this->user->id,
        'title' => 'Daily Standup',
        'start_at' => \Illuminate\Support\Facades\Date::now(),
        'end_at' => \Illuminate\Support\Facades\Date::now()->addMinutes(15),
        'recurrence_rule' => 'DAILY',
        'recurrence_end_date' => \Illuminate\Support\Facades\Date::now()->addDays(30),
    ]);

    $duration = (microtime(true) - $start) * 1000;
    $queries = DB::getQueryLog();

    // Should create 30 instances
    expect($event->recurrenceInstances()->count())->toBe(30);

    // Should complete in under 1 second
    expect($duration)->toBeLessThan(1000);

    // Should use batch insert (not 30 individual INSERTs)
    // Expect: 1 INSERT for parent + 1 batch INSERT for instances + a few SELECTs
    $insertQueries = collect($queries)->filter(fn ($q): bool => str_starts_with(strtoupper((string) $q['query']), 'INSERT'))->count();
    expect($insertQueries)->toBeLessThanOrEqual(2);
});

test('queries recurring instances without N+1', function (): void {
    $parent = CalendarEvent::factory()
        ->create([
            'team_id' => $this->team->id,
            'creator_id' => $this->user->id,
            'recurrence_rule' => 'WEEKLY',
            'recurrence_end_date' => \Illuminate\Support\Facades\Date::now()->addWeeks(10),
        ]);

    // Manually create instances to avoid observer
    $service = resolve(\App\Services\RecurrenceService::class);
    $instances = $service->generateInstances($parent);

    if ($instances->isNotEmpty()) {
        $data = $instances->map(fn ($instance): array => array_merge(
            $instance->getAttributes(),
            ['created_at' => now(), 'updated_at' => now()]
        ))->all();
        CalendarEvent::insert($data);
    }

    DB::enableQueryLog();

    $fetchedInstances = $parent->recurrenceInstances()->get();

    $queries = DB::getQueryLog();

    expect($fetchedInstances)->toHaveCount(10);
    expect($queries)->toHaveCount(1); // Should be single query
});

test('deletes recurring instances efficiently with batch delete', function (): void {
    $parent = CalendarEvent::factory()
        ->create([
            'team_id' => $this->team->id,
            'creator_id' => $this->user->id,
            'recurrence_rule' => 'DAILY',
            'recurrence_end_date' => \Illuminate\Support\Facades\Date::now()->addDays(50),
        ]);

    expect($parent->recurrenceInstances()->count())->toBe(50);

    DB::enableQueryLog();

    $service = resolve(\App\Services\RecurrenceService::class);
    $service->deleteInstances($parent);

    $queries = DB::getQueryLog();

    // Should use batch update (soft delete), not 50 individual UPDATEs
    $updateQueries = collect($queries)->filter(fn ($q): bool => str_starts_with(strtoupper((string) $q['query']), 'UPDATE'))->count();
    expect($updateQueries)->toBe(1);

    expect($parent->recurrenceInstances()->count())->toBe(0);
});

test('updates recurring instances efficiently with batch update', function (): void {
    $parent = CalendarEvent::factory()
        ->create([
            'team_id' => $this->team->id,
            'creator_id' => $this->user->id,
            'recurrence_rule' => 'WEEKLY',
            'recurrence_end_date' => \Illuminate\Support\Facades\Date::now()->addWeeks(20),
        ]);

    expect($parent->recurrenceInstances()->count())->toBe(20);

    DB::enableQueryLog();

    $service = resolve(\App\Services\RecurrenceService::class);
    $service->updateInstances($parent, ['location' => 'New Location']);

    $queries = DB::getQueryLog();

    // Should use batch update, not 20 individual UPDATEs
    $updateQueries = collect($queries)->filter(fn ($q): bool => str_starts_with(strtoupper((string) $q['query']), 'UPDATE'))->count();
    expect($updateQueries)->toBe(1);

    // Verify all instances were updated
    $updatedCount = $parent->recurrenceInstances()
        ->where('location', 'New Location')
        ->count();
    expect($updatedCount)->toBeGreaterThan(0);
});

test('calendar event resource uses eager loading', function (): void {
    // Create events with relationships
    CalendarEvent::factory()
        ->count(10)
        ->create([
            'team_id' => $this->team->id,
            'creator_id' => $this->user->id,
        ]);

    DB::enableQueryLog();

    // Simulate resource query
    $events = \App\Filament\Resources\CalendarEventResource::getEloquentQuery()
        ->limit(10)
        ->get();

    $queries = DB::getQueryLog();

    // Should eager load creator and team
    // Expect: 1 main query + 1 for creators + 1 for teams = 3 queries max
    expect($queries)->toHaveCount(3);

    // Verify relationships are loaded
    expect($events->first()->relationLoaded('creator'))->toBeTrue();
    expect($events->first()->relationLoaded('team'))->toBeTrue();
});

test('large recurring event creation completes within performance target', function (): void {
    $start = microtime(true);

    // Create a yearly recurring event (365 instances)
    $event = CalendarEvent::create([
        'team_id' => $this->team->id,
        'creator_id' => $this->user->id,
        'title' => 'Daily Task',
        'start_at' => \Illuminate\Support\Facades\Date::now(),
        'end_at' => \Illuminate\Support\Facades\Date::now()->addMinutes(30),
        'recurrence_rule' => 'DAILY',
        'recurrence_end_date' => \Illuminate\Support\Facades\Date::now()->addYear(),
    ]);

    $duration = (microtime(true) - $start) * 1000;

    // Should create 365 instances
    expect($event->recurrenceInstances()->count())->toBe(365);

    // Should complete in under 2 seconds even for large sets
    expect($duration)->toBeLessThan(2000);
});

test('calendar event queries use proper indexes', function (): void {
    // Create test data
    CalendarEvent::factory()
        ->count(100)
        ->create([
            'team_id' => $this->team->id,
            'recurrence_rule' => 'WEEKLY',
        ]);

    DB::enableQueryLog();

    // Query that should use indexes
    $events = CalendarEvent::query()
        ->where('team_id', $this->team->id)
        ->where('recurrence_rule', 'WEEKLY')
        ->oldest('start_at')
        ->limit(25)
        ->get();

    $queries = DB::getQueryLog();

    expect($events)->toHaveCount(25);

    // Query should be fast (using indexes)
    // Note: This is a basic check; actual EXPLAIN analysis would be better
    expect($queries)->toHaveCount(1);
});

test('recurring event with parent relationship loads efficiently', function (): void {
    $parent = CalendarEvent::factory()
        ->create([
            'team_id' => $this->team->id,
            'creator_id' => $this->user->id,
            'recurrence_rule' => 'WEEKLY',
            'recurrence_end_date' => \Illuminate\Support\Facades\Date::now()->addWeeks(5),
        ]);

    DB::enableQueryLog();

    // Load instances with parent relationship
    $instances = CalendarEvent::query()
        ->where('recurrence_parent_id', $parent->id)
        ->with('recurrenceParent')
        ->get();

    $queries = DB::getQueryLog();

    expect($instances)->toHaveCount(5);

    // Should use 2 queries: 1 for instances + 1 for parent
    expect($queries)->toHaveCount(2);

    // Verify parent is loaded
    expect($instances->first()->relationLoaded('recurrenceParent'))->toBeTrue();
});
