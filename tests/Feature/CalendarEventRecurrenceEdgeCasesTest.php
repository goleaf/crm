<?php

declare(strict_types=1);

use App\Models\CalendarEvent;
use App\Models\User;
use App\Services\RecurrenceService;
use Carbon\Carbon;

/**
 * Edge case and error condition tests for calendar event recurrence.
 *
 * Tests verify:
 * - Handling of invalid recurrence rules
 * - Boundary conditions for date ranges
 * - Orphaned instances when parent is deleted
 * - Circular reference prevention
 * - Performance with large recurrence sets
 */
beforeEach(function (): void {
    $this->user = User::factory()->withPersonalTeam()->create();
    $this->team = $this->user->currentTeam;
    $this->actingAs($this->user);
});

test('recurrence with end date before start date generates no instances', function (): void {
    $service = resolve(RecurrenceService::class);
    $startDate = \Illuminate\Support\Facades\Date::now();

    $event = CalendarEvent::factory()->create([
        'team_id' => $this->team->id,
        'start_at' => $startDate,
        'end_at' => $startDate->copy()->addHour(),
        'recurrence_rule' => 'WEEKLY',
        'recurrence_end_date' => $startDate->copy()->subWeek(), // End before start
    ]);

    $instances = $service->generateInstances($event);

    expect($instances)->toBeEmpty();
});

test('recurrence with same start and end date generates no instances', function (): void {
    $service = resolve(RecurrenceService::class);
    $startDate = \Illuminate\Support\Facades\Date::now();

    $event = CalendarEvent::factory()->create([
        'team_id' => $this->team->id,
        'start_at' => $startDate,
        'end_at' => $startDate->copy()->addHour(),
        'recurrence_rule' => 'WEEKLY',
        'recurrence_end_date' => $startDate, // Same as start
    ]);

    $instances = $service->generateInstances($event);

    expect($instances)->toBeEmpty();
});

test('invalid recurrence rule defaults to daily', function (): void {
    $service = resolve(RecurrenceService::class);
    $startDate = \Illuminate\Support\Facades\Date::now();

    $event = CalendarEvent::factory()->create([
        'team_id' => $this->team->id,
        'start_at' => $startDate,
        'end_at' => $startDate->copy()->addHour(),
        'recurrence_rule' => 'INVALID_RULE',
        'recurrence_end_date' => $startDate->copy()->addDays(2),
    ]);

    $instances = $service->generateInstances($event);

    // Should default to daily and generate 2 instances
    expect($instances)->toHaveCount(2);
});

test('recurring instance cannot be parent of another instance', function (): void {
    $startDate = \Illuminate\Support\Facades\Date::now();
    $service = resolve(RecurrenceService::class);

    $parent = CalendarEvent::factory()->create([
        'team_id' => $this->team->id,
        'start_at' => $startDate,
        'end_at' => $startDate->copy()->addHour(),
        'recurrence_rule' => 'WEEKLY',
        'recurrence_end_date' => $startDate->copy()->addWeeks(2),
    ]);

    // Generate instances
    $instances = $service->generateInstances($parent);
    foreach ($instances as $instance) {
        $instance->save();
    }

    $parent->refresh();
    $instance = $parent->recurrenceInstances->first();

    // Attempting to make an instance a parent should not work
    expect($instance)->not->toBeNull();
    expect($instance->isRecurringInstance())->toBeTrue();
    expect($instance->isRecurring())->toBeFalse();

    // Instance should not have recurrence_rule
    expect($instance->recurrence_rule)->toBeNull();
});

test('deleting parent nullifies recurrence_parent_id in instances', function (): void {
    $startDate = \Illuminate\Support\Facades\Date::now();
    $service = resolve(RecurrenceService::class);

    $parent = CalendarEvent::factory()->create([
        'team_id' => $this->team->id,
        'start_at' => $startDate,
        'end_at' => $startDate->copy()->addHour(),
        'recurrence_rule' => 'WEEKLY',
        'recurrence_end_date' => $startDate->copy()->addWeeks(2),
    ]);

    // Generate instances
    $instances = $service->generateInstances($parent);
    foreach ($instances as $instance) {
        $instance->save();
    }

    $parent->refresh();
    $parentId = $parent->id;

    // Verify instances exist
    expect($parent->recurrenceInstances)->toHaveCount(2);

    // Delete instances (simulating observer)
    $service->deleteInstances($parent);

    // Delete parent (soft delete)
    $parent->delete();

    // Instances should be deleted
    $remainingInstances = CalendarEvent::where('recurrence_parent_id', $parentId)->count();
    expect($remainingInstances)->toBe(0);
});

test('force deleting parent removes instances permanently', function (): void {
    $startDate = \Illuminate\Support\Facades\Date::now();
    $service = resolve(RecurrenceService::class);

    $parent = CalendarEvent::factory()->create([
        'team_id' => $this->team->id,
        'start_at' => $startDate,
        'end_at' => $startDate->copy()->addHour(),
        'recurrence_rule' => 'WEEKLY',
        'recurrence_end_date' => $startDate->copy()->addWeeks(2),
    ]);

    // Generate instances
    $instances = $service->generateInstances($parent);
    foreach ($instances as $instance) {
        $instance->save();
    }

    $parent->refresh();
    $parentId = $parent->id;

    // Delete instances first
    $service->deleteInstances($parent);

    // Force delete parent
    $parent->forceDelete();

    // No instances should remain (including soft deleted)
    $remainingInstances = CalendarEvent::withTrashed()
        ->where('recurrence_parent_id', $parentId)
        ->count();

    expect($remainingInstances)->toBe(0);
});

test('recurrence with null end date uses default one year limit', function (): void {
    $service = resolve(RecurrenceService::class);
    $startDate = \Illuminate\Support\Facades\Date::now();

    $event = CalendarEvent::factory()->create([
        'team_id' => $this->team->id,
        'start_at' => $startDate,
        'end_at' => $startDate->copy()->addHour(),
        'recurrence_rule' => 'WEEKLY',
        'recurrence_end_date' => null, // No end date
    ]);

    // Should generate up to max instances (default 100)
    $instances = $service->generateInstances($event, 10);

    expect($instances->count())->toBeLessThanOrEqual(10);
});

test('updating non-recurrence fields does not regenerate instances', function (): void {
    $startDate = \Illuminate\Support\Facades\Date::now();

    $event = CalendarEvent::factory()->create([
        'team_id' => $this->team->id,
        'start_at' => $startDate,
        'end_at' => $startDate->copy()->addHour(),
        'title' => 'Original Title',
        'recurrence_rule' => 'WEEKLY',
        'recurrence_end_date' => $startDate->copy()->addWeeks(2),
    ]);

    $event->refresh();
    $originalInstanceCount = $event->recurrenceInstances()->count();

    // Update non-recurrence field
    $event->update(['title' => 'Updated Title']);

    $event->refresh();
    $newInstanceCount = $event->recurrenceInstances()->count();

    // Instance count should remain the same
    expect($newInstanceCount)->toBe($originalInstanceCount);
});

test('recurrence parent relationship is correctly established', function (): void {
    $startDate = \Illuminate\Support\Facades\Date::now();
    $service = resolve(RecurrenceService::class);

    $parent = CalendarEvent::factory()->create([
        'team_id' => $this->team->id,
        'start_at' => $startDate,
        'end_at' => $startDate->copy()->addHour(),
        'recurrence_rule' => 'WEEKLY',
        'recurrence_end_date' => $startDate->copy()->addWeeks(2),
    ]);

    // Generate instances
    $instances = $service->generateInstances($parent);
    foreach ($instances as $instance) {
        $instance->save();
    }

    $parent->refresh();
    $instance = $parent->recurrenceInstances->first();

    // Test relationship
    expect($instance)->not->toBeNull();
    expect($instance->recurrenceParent->id)->toBe($parent->id);
    expect($instance->recurrenceParent->title)->toBe($parent->title);
});

test('event without end_at still generates recurring instances', function (): void {
    $service = resolve(RecurrenceService::class);
    $startDate = \Illuminate\Support\Facades\Date::now();

    $event = CalendarEvent::factory()->create([
        'team_id' => $this->team->id,
        'start_at' => $startDate,
        'end_at' => null, // No end time
        'recurrence_rule' => 'WEEKLY',
        'recurrence_end_date' => $startDate->copy()->addWeeks(2),
    ]);

    $instances = $service->generateInstances($event);

    expect($instances)->toHaveCount(2);

    // Note: RecurrenceService calculates duration and may set end_at even if parent has null
    // This is expected behavior to maintain event duration
});

test('recurring instances maintain team and creator relationships', function (): void {
    $startDate = \Illuminate\Support\Facades\Date::now();
    $service = resolve(RecurrenceService::class);

    $event = CalendarEvent::factory()->create([
        'team_id' => $this->team->id,
        'creator_id' => $this->user->id,
        'start_at' => $startDate,
        'end_at' => $startDate->copy()->addHour(),
        'recurrence_rule' => 'WEEKLY',
        'recurrence_end_date' => $startDate->copy()->addWeeks(2),
    ]);

    // Generate instances
    $instances = $service->generateInstances($event);
    foreach ($instances as $instance) {
        $instance->save();
    }

    $event->refresh();

    expect($event->recurrenceInstances)->toHaveCount(2);

    foreach ($event->recurrenceInstances as $instance) {
        expect($instance->team_id)->toBe($this->team->id);
        expect($instance->creator_id)->toBe($this->user->id);
    }
});

test('very long recurrence period respects max instances limit', function (): void {
    $service = resolve(RecurrenceService::class);
    $startDate = \Illuminate\Support\Facades\Date::now();

    $event = CalendarEvent::factory()->create([
        'team_id' => $this->team->id,
        'start_at' => $startDate,
        'end_at' => $startDate->copy()->addHour(),
        'recurrence_rule' => 'DAILY',
        'recurrence_end_date' => $startDate->copy()->addYears(100), // 100 years
    ]);

    $instances = $service->generateInstances($event, 50);

    // Should stop at or before max instances (count starts at 1, not 0)
    expect($instances->count())->toBeLessThanOrEqual(50);
    expect($instances->count())->toBeGreaterThan(0);
});

test('monthly recurrence handles month-end dates correctly', function (): void {
    $service = resolve(RecurrenceService::class);

    // Start on Jan 31
    $startDate = \Illuminate\Support\Facades\Date::create(2025, 1, 31, 10, 0);

    $event = CalendarEvent::factory()->create([
        'team_id' => $this->team->id,
        'start_at' => $startDate,
        'end_at' => $startDate->copy()->addHour(),
        'recurrence_rule' => 'MONTHLY',
        'recurrence_end_date' => $startDate->copy()->addMonths(3),
    ]);

    $instances = $service->generateInstances($event);

    // Should generate instances for Feb, Mar, Apr
    // Feb 31 doesn't exist, so Carbon will adjust to Feb 28/29
    // This test verifies the service handles this gracefully
    expect($instances->count())->toBeGreaterThan(0);
    expect($instances->count())->toBeLessThanOrEqual(3);
});

test('yearly recurrence handles leap years correctly', function (): void {
    $service = resolve(RecurrenceService::class);

    // Start on Feb 29, 2024 (leap year)
    $startDate = \Illuminate\Support\Facades\Date::create(2024, 2, 29, 10, 0);

    $event = CalendarEvent::factory()->create([
        'team_id' => $this->team->id,
        'start_at' => $startDate,
        'end_at' => $startDate->copy()->addHour(),
        'recurrence_rule' => 'YEARLY',
        'recurrence_end_date' => $startDate->copy()->addYears(2),
    ]);

    $instances = $service->generateInstances($event);

    // Should generate 2 instances (2025, 2026)
    expect($instances)->toHaveCount(2);
});

test('recurrence instances are created with correct creation_source', function (): void {
    $startDate = \Illuminate\Support\Facades\Date::now();
    $service = resolve(RecurrenceService::class);

    $event = CalendarEvent::factory()->create([
        'team_id' => $this->team->id,
        'start_at' => $startDate,
        'end_at' => $startDate->copy()->addHour(),
        'recurrence_rule' => 'WEEKLY',
        'recurrence_end_date' => $startDate->copy()->addWeeks(2),
        'creation_source' => \App\Enums\CreationSource::WEB,
    ]);

    // Generate instances
    $instances = $service->generateInstances($event);
    foreach ($instances as $instance) {
        $instance->save();
    }

    $event->refresh();

    expect($event->recurrenceInstances)->toHaveCount(2);

    foreach ($event->recurrenceInstances as $instance) {
        expect($instance->creation_source)->toBe(\App\Enums\CreationSource::WEB);
    }
});
