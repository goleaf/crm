<?php

declare(strict_types=1);

namespace Tests\Examples;

use App\Enums\CalendarEventStatus;
use App\Enums\CalendarEventType;
use App\Models\CalendarEvent;
use App\Models\Company;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\Generators\CalendarEventGenerator;
use Tests\TestCase;

/**
 * Example usage patterns for CalendarEventGenerator.
 *
 * This test class demonstrates various ways to use the CalendarEventGenerator
 * in different testing scenarios. These examples can serve as documentation
 * and reference for developers.
 */
final class CalendarEventGeneratorExamplesTest extends TestCase
{
    use RefreshDatabase;

    private Team $team;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->team = Team::factory()->create();
        $this->user = User::factory()->create();
        $this->team->users()->attach($this->user);
    }

    /**
     * Example: Basic event generation for simple tests.
     */
    public function test_example_basic_event_generation(): void
    {
        // Generate a basic calendar event with random data
        $event = CalendarEventGenerator::generate($this->team, $this->user);

        // Basic assertions you might make in tests
        $this->assertInstanceOf(CalendarEvent::class, $event);
        $this->assertEquals($this->team->id, $event->team_id);
        $this->assertEquals($this->user->id, $event->creator_id);
        $this->assertNotEmpty($event->title);
        $this->assertTrue($event->start_at->isBefore($event->end_at));
    }

    /**
     * Example: Testing specific event types and statuses.
     */
    public function test_example_specific_event_types(): void
    {
        // Generate a demo event that's confirmed
        $demoEvent = CalendarEventGenerator::generate($this->team, $this->user, [
            'type' => CalendarEventType::DEMO,
            'status' => CalendarEventStatus::CONFIRMED,
            'title' => 'Product Demo for Client',
        ]);

        // Test demo-specific logic
        $this->assertEquals(CalendarEventType::DEMO, $demoEvent->type);
        $this->assertEquals(CalendarEventStatus::CONFIRMED, $demoEvent->status);
        $this->assertStringContainsString('Demo', $demoEvent->title);

        // Generate a cancelled meeting
        $cancelledMeeting = CalendarEventGenerator::generate($this->team, $this->user, [
            'type' => CalendarEventType::MEETING,
            'status' => CalendarEventStatus::CANCELLED,
        ]);

        // Test cancellation logic
        $this->assertEquals(CalendarEventStatus::CANCELLED, $cancelledMeeting->status);
    }

    /**
     * Example: Testing recurring events and their instances.
     */
    public function test_example_recurring_events(): void
    {
        // Generate a weekly recurring meeting
        $weeklyMeeting = CalendarEventGenerator::generateRecurring($this->team, $this->user, [
            'title' => 'Weekly Team Standup',
            'recurrence_rule' => 'WEEKLY',
        ]);

        // Test recurring event properties
        $this->assertTrue($weeklyMeeting->isRecurring());
        $this->assertEquals('WEEKLY', $weeklyMeeting->recurrence_rule);
        $this->assertNotNull($weeklyMeeting->recurrence_end_date);

        // Generate instances for the next 4 weeks
        $instances = [];
        for ($week = 1; $week <= 4; $week++) {
            $instanceStart = \Illuminate\Support\Facades\Date::parse($weeklyMeeting->start_at)->addWeeks($week);
            $instances[] = CalendarEventGenerator::generateRecurringInstance(
                $weeklyMeeting,
                $instanceStart,
            );
        }

        // Test instance relationships
        $this->assertCount(4, $instances);
        foreach ($instances as $instance) {
            $this->assertTrue($instance->isRecurringInstance());
            $this->assertEquals($weeklyMeeting->id, $instance->recurrence_parent_id);
            $this->assertEquals($weeklyMeeting->title, $instance->title);
        }
    }

    /**
     * Example: Testing events linked to other models.
     */
    public function test_example_events_with_related_records(): void
    {
        // Create a company to link the event to
        $company = Company::factory()->create([
            'team_id' => $this->team->id,
            'name' => 'Acme Corporation',
        ]);

        // Generate an event related to the company
        $clientMeeting = CalendarEventGenerator::generateWithRelated(
            $this->team,
            $company,
            $this->user,
            [
                'title' => 'Client Meeting with ' . $company->name,
                'type' => CalendarEventType::MEETING,
            ],
        );

        // Test the relationship
        $this->assertEquals($company->id, $clientMeeting->related_id);
        $this->assertEquals(Company::class, $clientMeeting->related_type);
        $this->assertInstanceOf(Company::class, $clientMeeting->related);
        $this->assertEquals('Acme Corporation', $clientMeeting->related->name);
    }

    /**
     * Example: Testing all-day events.
     */
    public function test_example_all_day_events(): void
    {
        // Generate a conference or holiday event
        $conference = CalendarEventGenerator::generateAllDay($this->team, $this->user, [
            'title' => 'Annual Tech Conference',
            'location' => 'Convention Center',
        ]);

        // Test all-day properties
        $this->assertTrue($conference->is_all_day);
        $this->assertEquals('00:00:00', $conference->start_at->format('H:i:s'));
        $this->assertEquals('23:59:59', $conference->end_at->format('H:i:s'));
        $this->assertEquals(
            $conference->start_at->format('Y-m-d'),
            $conference->end_at->format('Y-m-d'),
        );
    }

    /**
     * Example: Testing events with specific durations.
     */
    public function test_example_events_with_specific_durations(): void
    {
        // Generate a quick 15-minute standup
        $standup = CalendarEventGenerator::generateWithDuration($this->team, 15, $this->user, [
            'title' => 'Daily Standup',
            'type' => CalendarEventType::MEETING,
        ]);

        // Generate a long 4-hour workshop
        $workshop = CalendarEventGenerator::generateWithDuration($this->team, 240, $this->user, [
            'title' => 'Development Workshop',
            'type' => CalendarEventType::OTHER,
        ]);

        // Test durations
        $this->assertEquals(15, $standup->durationMinutes());
        $this->assertEquals(240, $workshop->durationMinutes());

        // Test that end times are calculated correctly
        $this->assertEquals(15, $standup->end_at->diffInMinutes($standup->start_at));
        $this->assertEquals(240, $workshop->end_at->diffInMinutes($workshop->start_at));
    }

    /**
     * Example: Testing bulk event generation for performance tests.
     */
    public function test_example_bulk_event_generation(): void
    {
        // Generate multiple events for a busy calendar
        $events = CalendarEventGenerator::generateMultiple($this->team, 20, $this->user, [
            'type' => CalendarEventType::MEETING,
        ]);

        // Test bulk properties
        $this->assertCount(20, $events);

        // Verify all events are meetings
        foreach ($events as $event) {
            $this->assertEquals(CalendarEventType::MEETING, $event->type);
            $this->assertEquals($this->team->id, $event->team_id);
            $this->assertEquals($this->user->id, $event->creator_id);
        }

        // Verify all events are unique
        $ids = collect($events)->pluck('id')->unique();
        $this->assertCount(20, $ids);
    }

    /**
     * Example: Testing edge cases and error conditions.
     */
    public function test_example_edge_cases_and_error_handling(): void
    {
        // Generate events with edge case data
        $edgeEvent = CalendarEventGenerator::generateEdgeCase($this->team, $this->user);

        // The event should still be valid despite edge case data
        $this->assertInstanceOf(CalendarEvent::class, $edgeEvent);
        $this->assertTrue($edgeEvent->exists);

        // Test specific edge cases
        $longTitleEvent = CalendarEventGenerator::generateEdgeCase($this->team, $this->user, [
            'title' => str_repeat('Very Long Title ', 50),
        ]);
        $this->assertGreaterThan(100, strlen($longTitleEvent->title));

        $emptyAttendeesEvent = CalendarEventGenerator::generateEdgeCase($this->team, $this->user, [
            'attendees' => [],
        ]);
        $this->assertEmpty($emptyAttendeesEvent->attendees);

        // Test error conditions
        $this->expectException(\InvalidArgumentException::class);
        CalendarEventGenerator::generate($this->team, $this->user, [
            'start_at' => now()->addHour(),
            'end_at' => now(), // Invalid: start after end
        ]);
    }

    /**
     * Example: Testing calendar sync scenarios.
     */
    public function test_example_calendar_sync_scenarios(): void
    {
        // Generate events with different sync providers
        $googleEvent = CalendarEventGenerator::generateWithSync($this->team, $this->user, [
            'sync_provider' => 'google',
            'title' => 'Google Calendar Meeting',
        ]);

        $outlookEvent = CalendarEventGenerator::generateWithSync($this->team, $this->user, [
            'sync_provider' => 'outlook',
            'title' => 'Outlook Calendar Meeting',
        ]);

        // Test sync properties
        $this->assertEquals('google', $googleEvent->sync_provider);
        $this->assertNotNull($googleEvent->sync_status);
        $this->assertNotNull($googleEvent->sync_external_id);

        $this->assertEquals('outlook', $outlookEvent->sync_provider);
        $this->assertNotNull($outlookEvent->sync_status);
        $this->assertNotNull($outlookEvent->sync_external_id);
    }

    /**
     * Example: Testing data generation without persistence.
     */
    public function test_example_data_generation_without_persistence(): void
    {
        $initialEventCount = CalendarEvent::count();

        // Generate data arrays for validation testing
        $eventData1 = CalendarEventGenerator::generateData($this->team, $this->user);
        $eventData2 = CalendarEventGenerator::generateData($this->team, $this->user);

        // Verify no events were created in database
        $this->assertEquals($initialEventCount, CalendarEvent::count());

        // Verify data structure
        $this->assertIsArray($eventData1);
        $this->assertIsArray($eventData2);
        $this->assertArrayHasKey('title', $eventData1);
        $this->assertArrayHasKey('start_at', $eventData1);
        $this->assertArrayHasKey('end_at', $eventData1);

        // Verify data is different (random generation)
        $this->assertNotEquals($eventData1['title'], $eventData2['title']);
    }

    /**
     * Example: Testing complex scenarios with multiple event types.
     */
    public function test_example_complex_calendar_scenarios(): void
    {
        // Simulate a busy week with various event types
        $events = [];

        // Monday: Team standup
        $events[] = CalendarEventGenerator::generate($this->team, $this->user, [
            'title' => 'Monday Standup',
            'type' => CalendarEventType::MEETING,
            'start_at' => now()->startOfWeek()->setHour(9),
            'end_at' => now()->startOfWeek()->setHour(9)->addMinutes(15),
        ]);

        // Tuesday: Client demo
        $client = Company::factory()->create(['team_id' => $this->team->id]);
        $events[] = CalendarEventGenerator::generateWithRelated($this->team, $client, $this->user, [
            'title' => 'Client Demo',
            'type' => CalendarEventType::DEMO,
            'start_at' => now()->startOfWeek()->addDay()->setHour(14),
            'end_at' => now()->startOfWeek()->addDay()->setHour(15),
        ]);

        // Wednesday: All-day conference
        $events[] = CalendarEventGenerator::generateAllDay($this->team, $this->user, [
            'title' => 'Tech Conference',
            'start_at' => now()->startOfWeek()->addDays(2),
        ]);

        // Thursday: Lunch meeting
        $events[] = CalendarEventGenerator::generate($this->team, $this->user, [
            'title' => 'Lunch with Partner',
            'type' => CalendarEventType::LUNCH,
            'start_at' => now()->startOfWeek()->addDays(3)->setHour(12),
            'end_at' => now()->startOfWeek()->addDays(3)->setHour(13),
        ]);

        // Friday: Follow-up call
        $events[] = CalendarEventGenerator::generate($this->team, $this->user, [
            'title' => 'Follow-up Call',
            'type' => CalendarEventType::FOLLOW_UP,
            'start_at' => now()->startOfWeek()->addDays(4)->setHour(16),
            'end_at' => now()->startOfWeek()->addDays(4)->setHour(16)->addMinutes(30),
        ]);

        // Test the complete week
        $this->assertCount(5, $events);

        // Verify event types
        $this->assertEquals(CalendarEventType::MEETING, $events[0]->type);
        $this->assertEquals(CalendarEventType::DEMO, $events[1]->type);
        $this->assertTrue($events[2]->is_all_day);
        $this->assertEquals(CalendarEventType::LUNCH, $events[3]->type);
        $this->assertEquals(CalendarEventType::FOLLOW_UP, $events[4]->type);
        $counter = count($events);

        // Verify chronological order
        for ($i = 1; $i < $counter; $i++) {
            $this->assertTrue($events[$i - 1]->start_at->isBefore($events[$i]->start_at));
        }
    }
}
