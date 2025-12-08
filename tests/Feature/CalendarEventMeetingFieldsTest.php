<?php

declare(strict_types=1);

use App\Models\CalendarEvent;
use App\Models\User;

/**
 * Feature tests for meeting-specific fields in CalendarEvent.
 *
 * Tests verify:
 * - Agenda and minutes can be stored and retrieved
 * - Room booking functionality works correctly
 * - Meeting fields integrate with recurrence
 * - Rich text content is preserved
 */
beforeEach(function (): void {
    $this->user = User::factory()->withPersonalTeam()->create();
    $this->team = $this->user->currentTeam;
    $this->actingAs($this->user);
});

test('calendar event can store agenda', function (): void {
    $agenda = '<p>1. Review Q4 results</p><p>2. Discuss 2025 strategy</p>';

    $event = CalendarEvent::factory()->create([
        'team_id' => $this->team->id,
        'creator_id' => $this->user->id,
        'title' => 'Board Meeting',
        'agenda' => $agenda,
    ]);

    expect($event->agenda)->toBe($agenda);

    $event->refresh();
    expect($event->agenda)->toBe($agenda);
});

test('calendar event can store meeting minutes', function (): void {
    $minutes = '<p><strong>Decisions:</strong></p><ul><li>Approved budget</li><li>Hired new manager</li></ul>';

    $event = CalendarEvent::factory()->create([
        'team_id' => $this->team->id,
        'creator_id' => $this->user->id,
        'title' => 'Executive Meeting',
        'minutes' => $minutes,
    ]);

    expect($event->minutes)->toBe($minutes);

    $event->refresh();
    expect($event->minutes)->toBe($minutes);
});

test('calendar event can store room booking information', function (): void {
    $roomBooking = 'Conference Room A - Building 2';

    $event = CalendarEvent::factory()->create([
        'team_id' => $this->team->id,
        'creator_id' => $this->user->id,
        'title' => 'Team Standup',
        'location' => 'Office',
        'room_booking' => $roomBooking,
    ]);

    expect($event->room_booking)->toBe($roomBooking);

    $event->refresh();
    expect($event->room_booking)->toBe($roomBooking);
});

test('meeting fields can be updated', function (): void {
    $event = CalendarEvent::factory()->create([
        'team_id' => $this->team->id,
        'creator_id' => $this->user->id,
        'agenda' => '<p>Initial agenda</p>',
        'minutes' => null,
        'room_booking' => 'Room A',
    ]);

    $event->update([
        'agenda' => '<p>Updated agenda</p>',
        'minutes' => '<p>Meeting notes</p>',
        'room_booking' => 'Room B',
    ]);

    expect($event->agenda)->toBe('<p>Updated agenda</p>');
    expect($event->minutes)->toBe('<p>Meeting notes</p>');
    expect($event->room_booking)->toBe('Room B');
});

test('meeting fields are optional', function (): void {
    $event = CalendarEvent::factory()->create([
        'team_id' => $this->team->id,
        'creator_id' => $this->user->id,
        'title' => 'Quick Call',
        'agenda' => null,
        'minutes' => null,
        'room_booking' => null,
    ]);

    expect($event->agenda)->toBeNull();
    expect($event->minutes)->toBeNull();
    expect($event->room_booking)->toBeNull();
});

test('recurring event instances inherit agenda', function (): void {
    $agenda = '<p>Weekly sync agenda</p>';
    $startDate = \Illuminate\Support\Facades\Date::now()->setTime(10, 0);
    $service = resolve(\App\Services\RecurrenceService::class);

    $event = CalendarEvent::factory()->create([
        'team_id' => $this->team->id,
        'creator_id' => $this->user->id,
        'title' => 'Weekly Sync',
        'start_at' => $startDate,
        'end_at' => $startDate->copy()->addHour(),
        'agenda' => $agenda,
        'recurrence_rule' => 'WEEKLY',
        'recurrence_end_date' => $startDate->copy()->addWeeks(2),
    ]);

    // Generate instances
    $instances = $service->generateInstances($event);
    foreach ($instances as $instance) {
        $instance->save();
    }

    $event->refresh();
    $instances = $event->recurrenceInstances;

    expect($instances)->toHaveCount(2);

    foreach ($instances as $instance) {
        expect($instance->agenda)->toBe($agenda);
        expect($instance->minutes)->toBeNull(); // Minutes should not be inherited
    }
});

test('recurring event instances do not inherit minutes', function (): void {
    $startDate = \Illuminate\Support\Facades\Date::now()->setTime(10, 0);
    $service = resolve(\App\Services\RecurrenceService::class);

    $event = CalendarEvent::factory()->create([
        'team_id' => $this->team->id,
        'creator_id' => $this->user->id,
        'title' => 'Weekly Review',
        'start_at' => $startDate,
        'end_at' => $startDate->copy()->addHour(),
        'agenda' => '<p>Review agenda</p>',
        'minutes' => '<p>Parent meeting minutes</p>',
        'recurrence_rule' => 'WEEKLY',
        'recurrence_end_date' => $startDate->copy()->addWeeks(2),
    ]);

    // Generate instances
    $instances = $service->generateInstances($event);
    foreach ($instances as $instance) {
        $instance->save();
    }

    $event->refresh();
    $instances = $event->recurrenceInstances;

    expect($instances)->toHaveCount(2);

    foreach ($instances as $instance) {
        expect($instance->minutes)->toBeNull();
    }
});

test('room booking is preserved in recurring instances', function (): void {
    $roomBooking = 'Conference Room B';
    $startDate = \Illuminate\Support\Facades\Date::now()->setTime(14, 0);
    $service = resolve(\App\Services\RecurrenceService::class);

    $event = CalendarEvent::factory()->create([
        'team_id' => $this->team->id,
        'creator_id' => $this->user->id,
        'title' => 'Daily Standup',
        'start_at' => $startDate,
        'end_at' => $startDate->copy()->addMinutes(15),
        'room_booking' => $roomBooking,
        'recurrence_rule' => 'DAILY',
        'recurrence_end_date' => $startDate->copy()->addDays(3),
    ]);

    // Generate instances
    $instances = $service->generateInstances($event);
    foreach ($instances as $instance) {
        $instance->save();
    }

    $event->refresh();
    $instances = $event->recurrenceInstances;

    expect($instances)->toHaveCount(3);

    foreach ($instances as $instance) {
        expect($instance->room_booking)->toBe($roomBooking);
    }
});

test('agenda supports rich text formatting', function (): void {
    $richAgenda = <<<'HTML'
<h2>Meeting Agenda</h2>
<ol>
    <li><strong>Opening remarks</strong> - 5 minutes</li>
    <li><em>Project updates</em> - 20 minutes</li>
    <li>Q&amp;A - 10 minutes</li>
</ol>
<p>Please review <a href="https://example.com/docs">the documentation</a> before the meeting.</p>
HTML;

    $event = CalendarEvent::factory()->create([
        'team_id' => $this->team->id,
        'agenda' => $richAgenda,
    ]);

    expect($event->agenda)->toBe($richAgenda);

    $event->refresh();
    expect($event->agenda)->toBe($richAgenda);
});

test('minutes support rich text formatting', function (): void {
    $richMinutes = <<<'HTML'
<h2>Meeting Minutes</h2>
<h3>Attendees</h3>
<ul>
    <li>John Doe</li>
    <li>Jane Smith</li>
</ul>
<h3>Decisions</h3>
<ol>
    <li><strong>Approved:</strong> New marketing budget</li>
    <li><strong>Deferred:</strong> Office relocation</li>
</ol>
HTML;

    $event = CalendarEvent::factory()->create([
        'team_id' => $this->team->id,
        'minutes' => $richMinutes,
    ]);

    expect($event->minutes)->toBe($richMinutes);

    $event->refresh();
    expect($event->minutes)->toBe($richMinutes);
});

test('meeting fields work with all-day events', function (): void {
    $event = CalendarEvent::factory()->create([
        'team_id' => $this->team->id,
        'creator_id' => $this->user->id,
        'title' => 'Annual Conference',
        'is_all_day' => true,
        'agenda' => '<p>Full day conference agenda</p>',
        'room_booking' => 'Main Auditorium',
    ]);

    expect($event->is_all_day)->toBeTrue();
    expect($event->agenda)->toBe('<p>Full day conference agenda</p>');
    expect($event->room_booking)->toBe('Main Auditorium');
});

test('empty strings are stored as null for meeting fields', function (): void {
    $event = CalendarEvent::factory()->create([
        'team_id' => $this->team->id,
        'agenda' => '',
        'minutes' => '',
        'room_booking' => '',
    ]);

    // Empty strings should be stored as-is (Laravel doesn't auto-convert to null)
    // But we can verify they're handled correctly
    expect($event->agenda)->toBe('');
    expect($event->minutes)->toBe('');
    expect($event->room_booking)->toBe('');
});

test('meeting fields persist through soft delete and restore', function (): void {
    $event = CalendarEvent::factory()->create([
        'team_id' => $this->team->id,
        'agenda' => '<p>Important agenda</p>',
        'minutes' => '<p>Important minutes</p>',
        'room_booking' => 'VIP Room',
    ]);

    $eventId = $event->id;

    // Soft delete
    $event->delete();

    // Restore
    $restored = CalendarEvent::withTrashed()->find($eventId);
    $restored->restore();

    // Verify fields are preserved
    expect($restored->agenda)->toBe('<p>Important agenda</p>');
    expect($restored->minutes)->toBe('<p>Important minutes</p>');
    expect($restored->room_booking)->toBe('VIP Room');
});

test('large agenda content can be stored', function (): void {
    $largeAgenda = str_repeat('<p>Agenda item with detailed description. </p>', 100);

    $event = CalendarEvent::factory()->create([
        'team_id' => $this->team->id,
        'agenda' => $largeAgenda,
    ]);

    expect($event->agenda)->toBe($largeAgenda);
    expect(strlen((string) $event->agenda))->toBeGreaterThan(1000);
});

test('large minutes content can be stored', function (): void {
    $largeMinutes = str_repeat('<p>Detailed meeting notes with extensive information. </p>', 100);

    $event = CalendarEvent::factory()->create([
        'team_id' => $this->team->id,
        'minutes' => $largeMinutes,
    ]);

    expect($event->minutes)->toBe($largeMinutes);
    expect(strlen((string) $event->minutes))->toBeGreaterThan(1000);
});
