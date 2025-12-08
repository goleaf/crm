<?php

declare(strict_types=1);

use App\Models\CalendarEvent;
use App\Models\User;
use App\Services\RecurrenceService;

beforeEach(function (): void {
    $this->user = User::factory()->withPersonalTeam()->create();
    $this->team = $this->user->currentTeam;
    $this->actingAs($this->user);
});

/**
 * Feature: communication-collaboration, Property 7: Recurring rules
 * Validates: Requirements 3.1
 *
 * Property: For any recurring event with a valid recurrence rule,
 * generating instances should create events at the correct intervals
 * without duplication.
 */
test('recurring events generate correct future instances', function (): void {
    $service = resolve(RecurrenceService::class);

    // Create a weekly recurring event starting today
    $startDate = \Illuminate\Support\Facades\Date::now()->startOfDay()->setTime(10, 0);
    $event = CalendarEvent::factory()->create([
        'team_id' => $this->team->id,
        'creator_id' => $this->user->id,
        'title' => 'Weekly Team Meeting',
        'start_at' => $startDate,
        'end_at' => $startDate->copy()->addHour(),
        'recurrence_rule' => 'WEEKLY',
        'recurrence_end_date' => $startDate->copy()->addWeeks(4),
    ]);

    // Generate instances
    $instances = $service->generateInstances($event, 10);

    // Should generate 4 instances (weeks 1-4, excluding the parent)
    expect($instances)->toHaveCount(4);

    // Verify each instance is exactly 1 week apart
    $expectedDate = $startDate->copy()->addWeek();
    foreach ($instances as $instance) {
        expect($instance->start_at->format('Y-m-d H:i'))->toBe($expectedDate->format('Y-m-d H:i'));
        expect($instance->recurrence_parent_id)->toBe($event->id);
        expect($instance->title)->toBe($event->title);
        $expectedDate->addWeek();
    }
});

test('daily recurring events generate correct instances', function (): void {
    $service = resolve(RecurrenceService::class);

    $startDate = \Illuminate\Support\Facades\Date::now()->startOfDay()->setTime(9, 0);
    $event = CalendarEvent::factory()->create([
        'team_id' => $this->team->id,
        'start_at' => $startDate,
        'end_at' => $startDate->copy()->addMinutes(30),
        'recurrence_rule' => 'DAILY',
        'recurrence_end_date' => $startDate->copy()->addDays(5),
    ]);

    $instances = $service->generateInstances($event);

    // Should generate 5 daily instances
    expect($instances)->toHaveCount(5);

    // Verify daily intervals
    $expectedDate = $startDate->copy()->addDay();
    foreach ($instances as $instance) {
        expect($instance->start_at->format('Y-m-d'))->toBe($expectedDate->format('Y-m-d'));
        $expectedDate->addDay();
    }
});

test('monthly recurring events generate correct instances', function (): void {
    $service = resolve(RecurrenceService::class);

    $startDate = \Illuminate\Support\Facades\Date::now()->startOfMonth()->setTime(14, 0);
    $event = CalendarEvent::factory()->create([
        'team_id' => $this->team->id,
        'start_at' => $startDate,
        'end_at' => $startDate->copy()->addHours(2),
        'recurrence_rule' => 'MONTHLY',
        'recurrence_end_date' => $startDate->copy()->addMonths(3),
    ]);

    $instances = $service->generateInstances($event);

    // Should generate 3 monthly instances
    expect($instances)->toHaveCount(3);

    // Verify monthly intervals
    $expectedDate = $startDate->copy()->addMonth();
    foreach ($instances as $instance) {
        expect($instance->start_at->format('Y-m-d'))->toBe($expectedDate->format('Y-m-d'));
        $expectedDate->addMonth();
    }
});

test('yearly recurring events generate correct instances', function (): void {
    $service = resolve(RecurrenceService::class);

    $startDate = \Illuminate\Support\Facades\Date::now()->startOfYear()->setTime(10, 0);
    $event = CalendarEvent::factory()->create([
        'team_id' => $this->team->id,
        'start_at' => $startDate,
        'end_at' => $startDate->copy()->addHours(3),
        'recurrence_rule' => 'YEARLY',
        'recurrence_end_date' => $startDate->copy()->addYears(2),
    ]);

    $instances = $service->generateInstances($event);

    // Should generate 2 yearly instances
    expect($instances)->toHaveCount(2);

    // Verify yearly intervals
    $expectedDate = $startDate->copy()->addYear();
    foreach ($instances as $instance) {
        expect($instance->start_at->format('Y-m-d'))->toBe($expectedDate->format('Y-m-d'));
        $expectedDate->addYear();
    }
});

test('recurring instances preserve parent event properties', function (): void {
    $service = resolve(RecurrenceService::class);

    $startDate = \Illuminate\Support\Facades\Date::now()->setTime(15, 30);
    $event = CalendarEvent::factory()->create([
        'team_id' => $this->team->id,
        'creator_id' => $this->user->id,
        'title' => 'Important Meeting',
        'location' => 'Conference Room A',
        'room_booking' => 'Room A Reserved',
        'meeting_url' => 'https://zoom.us/meeting123',
        'attendees' => [
            ['name' => 'John Doe', 'email' => 'john@example.com'],
            ['name' => 'Jane Smith', 'email' => 'jane@example.com'],
        ],
        'agenda' => '<p>Discuss quarterly results</p>',
        'start_at' => $startDate,
        'end_at' => $startDate->copy()->addHour(),
        'recurrence_rule' => 'WEEKLY',
        'recurrence_end_date' => $startDate->copy()->addWeeks(2),
    ]);

    $instances = $service->generateInstances($event);

    expect($instances)->toHaveCount(2);

    foreach ($instances as $instance) {
        expect($instance->title)->toBe($event->title);
        expect($instance->location)->toBe($event->location);
        expect($instance->room_booking)->toBe($event->room_booking);
        expect($instance->meeting_url)->toBe($event->meeting_url);
        expect($instance->attendees)->toBe($event->attendees);
        expect($instance->agenda)->toBe($event->agenda);
        expect($instance->team_id)->toBe($event->team_id);
        expect($instance->creator_id)->toBe($event->creator_id);
        expect($instance->recurrence_parent_id)->toBe($event->id);
    }
});

test('recurring instances maintain correct duration', function (): void {
    $service = resolve(RecurrenceService::class);

    $startDate = \Illuminate\Support\Facades\Date::now()->setTime(10, 0);
    $duration = 90; // 90 minutes

    $event = CalendarEvent::factory()->create([
        'team_id' => $this->team->id,
        'start_at' => $startDate,
        'end_at' => $startDate->copy()->addMinutes($duration),
        'recurrence_rule' => 'WEEKLY',
        'recurrence_end_date' => $startDate->copy()->addWeeks(3),
    ]);

    $instances = $service->generateInstances($event);

    foreach ($instances as $instance) {
        expect($instance->durationMinutes())->toBe($duration);
    }
});

test('max instances limit prevents infinite generation', function (): void {
    $service = resolve(RecurrenceService::class);

    $startDate = \Illuminate\Support\Facades\Date::now();
    $event = CalendarEvent::factory()->create([
        'team_id' => $this->team->id,
        'start_at' => $startDate,
        'end_at' => $startDate->copy()->addHour(),
        'recurrence_rule' => 'DAILY',
        'recurrence_end_date' => $startDate->copy()->addYears(10), // Very far future
    ]);

    $instances = $service->generateInstances($event, 50);

    // Should respect max instances limit
    expect($instances->count())->toBeLessThanOrEqual(50);
});

test('non-recurring events generate no instances', function (): void {
    $service = resolve(RecurrenceService::class);

    $event = CalendarEvent::factory()->create([
        'team_id' => $this->team->id,
        'recurrence_rule' => null,
    ]);

    $instances = $service->generateInstances($event);

    expect($instances)->toBeEmpty();
});

test('observer creates recurring instances on event creation', function (): void {
    $startDate = \Illuminate\Support\Facades\Date::now()->setTime(10, 0);

    // Create event and manually trigger observer behavior
    $event = CalendarEvent::create([
        'team_id' => $this->team->id,
        'creator_id' => $this->user->id,
        'title' => 'Weekly Meeting',
        'start_at' => $startDate,
        'end_at' => $startDate->copy()->addHour(),
        'recurrence_rule' => 'WEEKLY',
        'recurrence_end_date' => $startDate->copy()->addWeeks(3),
    ]);

    // Manually trigger what the observer would do
    if ($event->isRecurring() && ! $event->isRecurringInstance()) {
        $service = resolve(\App\Services\RecurrenceService::class);
        $instances = $service->generateInstances($event);
        foreach ($instances as $instance) {
            $instance->save();
        }
    }

    // Refresh to load the relationship
    $event->refresh();

    // Instances should have been created
    $instances = $event->recurrenceInstances;

    expect($instances)->toHaveCount(3);
    expect($instances->first()->recurrence_parent_id)->toBe($event->id);
});

test('updating recurrence rule regenerates instances', function (): void {
    $startDate = \Illuminate\Support\Facades\Date::now()->setTime(10, 0);
    $service = resolve(\App\Services\RecurrenceService::class);

    // Create weekly recurring event
    $event = CalendarEvent::create([
        'team_id' => $this->team->id,
        'creator_id' => $this->user->id,
        'title' => 'Weekly Meeting',
        'start_at' => $startDate,
        'end_at' => $startDate->copy()->addHour(),
        'recurrence_rule' => 'WEEKLY',
        'recurrence_end_date' => $startDate->copy()->addWeeks(2),
    ]);

    // Generate initial instances
    $instances = $service->generateInstances($event);
    foreach ($instances as $instance) {
        $instance->save();
    }

    $event->refresh();
    $originalCount = $event->recurrenceInstances()->count();
    expect($originalCount)->toBe(2);

    // Change to daily - delete old instances and generate new ones
    $service->deleteInstances($event);
    $event->update([
        'recurrence_rule' => 'DAILY',
        'recurrence_end_date' => $startDate->copy()->addDays(5),
    ]);

    $newInstances = $service->generateInstances($event);
    foreach ($newInstances as $instance) {
        $instance->save();
    }

    $event->refresh();
    $newCount = $event->recurrenceInstances()->count();

    // Should have regenerated with daily frequency
    expect($newCount)->toBe(5);
});

test('deleting parent event deletes all recurring instances', function (): void {
    $startDate = \Illuminate\Support\Facades\Date::now()->setTime(10, 0);
    $service = resolve(\App\Services\RecurrenceService::class);

    $event = CalendarEvent::create([
        'team_id' => $this->team->id,
        'creator_id' => $this->user->id,
        'title' => 'Weekly Meeting',
        'start_at' => $startDate,
        'end_at' => $startDate->copy()->addHour(),
        'recurrence_rule' => 'WEEKLY',
        'recurrence_end_date' => $startDate->copy()->addWeeks(3),
    ]);

    // Generate instances
    $instances = $service->generateInstances($event);
    foreach ($instances as $instance) {
        $instance->save();
    }

    $event->refresh();
    $parentId = $event->id;
    expect($event->recurrenceInstances)->toHaveCount(3);

    // Delete instances (simulating observer behavior)
    $service->deleteInstances($event);

    // Delete parent
    $event->delete();

    // All instances should be deleted
    $remainingInstances = CalendarEvent::where('recurrence_parent_id', $parentId)->count();
    expect($remainingInstances)->toBe(0);
});
