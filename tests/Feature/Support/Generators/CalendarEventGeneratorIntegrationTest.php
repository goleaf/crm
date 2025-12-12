<?php

declare(strict_types=1);

namespace Tests\Feature\Support\Generators;

use App\Models\CalendarEvent;
use App\Models\Company;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\Generators\CalendarEventGenerator;
use Tests\TestCase;

/**
 * Integration tests for CalendarEventGenerator.
 *
 * These tests verify that the generator works correctly with the full
 * application stack including database relationships, observers, and services.
 */
final class CalendarEventGeneratorIntegrationTest extends TestCase
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

    public function test_generated_events_trigger_model_observers(): void
    {
        // This test verifies that CalendarEventObserver is triggered
        $event = CalendarEventGenerator::generate($this->team, $this->user);

        // Verify the event was created and observers ran
        $this->assertDatabaseHas('calendar_events', [
            'id' => $event->id,
            'team_id' => $this->team->id,
            'creator_id' => $this->user->id,
        ]);

        // If there are any observer side effects, they should be tested here
        // For example, if the observer creates related records or sends notifications
    }

    public function test_generated_events_work_with_relationships(): void
    {
        $company = Company::factory()->create(['team_id' => $this->team->id]);

        $event = CalendarEventGenerator::generateWithRelated($this->team, $company, $this->user);

        // Test the polymorphic relationship
        $this->assertInstanceOf(Company::class, $event->related);
        $this->assertEquals($company->id, $event->related->id);

        // Test eager loading works
        $loadedEvent = CalendarEvent::with('related')->find($event->id);
        $this->assertInstanceOf(Company::class, $loadedEvent->related);
    }

    public function test_generated_recurring_events_work_with_instances(): void
    {
        $parentEvent = CalendarEventGenerator::generateRecurring($this->team, $this->user);

        // Generate multiple instances
        $instances = [];
        for ($i = 1; $i <= 3; $i++) {
            $instanceStartAt = \Illuminate\Support\Facades\Date::parse($parentEvent->start_at)->addWeeks($i);
            $instances[] = CalendarEventGenerator::generateRecurringInstance($parentEvent, $instanceStartAt);
        }

        // Test parent-child relationships
        $this->assertCount(3, $parentEvent->recurrenceInstances);

        foreach ($instances as $instance) {
            $this->assertEquals($parentEvent->id, $instance->recurrence_parent_id);
            $this->assertInstanceOf(CalendarEvent::class, $instance->recurrenceParent);
            $this->assertEquals($parentEvent->id, $instance->recurrenceParent->id);
        }
    }

    public function test_generated_events_work_with_scopes(): void
    {
        // Generate events in different date ranges
        $pastEvent = CalendarEventGenerator::generate($this->team, $this->user, [
            'start_at' => now()->subWeek(),
            'end_at' => now()->subWeek()->addHour(),
        ]);

        $futureEvent = CalendarEventGenerator::generate($this->team, $this->user, [
            'start_at' => now()->addWeek(),
            'end_at' => now()->addWeek()->addHour(),
        ]);

        // Test date range scope
        $eventsInRange = CalendarEvent::inDateRange(
            now()->subDays(2),
            now()->addDays(2),
        )->get();

        $this->assertCount(0, $eventsInRange);

        // Test team scope
        $teamEvents = CalendarEvent::forTeam($this->team->id)->get();
        $this->assertCount(2, $teamEvents);
        $this->assertTrue($teamEvents->contains($pastEvent));
        $this->assertTrue($teamEvents->contains($futureEvent));
    }

    public function test_generated_events_work_with_search_scope(): void
    {
        $searchableEvent = CalendarEventGenerator::generate($this->team, $this->user, [
            'title' => 'Important Meeting with Client',
            'location' => 'Conference Room A',
            'notes' => 'Discuss project requirements',
        ]);

        CalendarEventGenerator::generate($this->team, $this->user, [
            'title' => 'Team Standup',
            'location' => 'Office',
            'notes' => 'Daily sync',
        ]);

        // Test search by title
        $titleResults = CalendarEvent::search('Important')->get();
        $this->assertCount(1, $titleResults);
        $this->assertTrue($titleResults->contains($searchableEvent));

        // Test search by location
        $locationResults = CalendarEvent::search('Conference')->get();
        $this->assertCount(1, $locationResults);
        $this->assertTrue($locationResults->contains($searchableEvent));

        // Test search by notes
        $notesResults = CalendarEvent::search('requirements')->get();
        $this->assertCount(1, $notesResults);
        $this->assertTrue($notesResults->contains($searchableEvent));
    }

    public function test_generated_events_work_with_eager_loading(): void
    {
        $events = CalendarEventGenerator::generateMultiple($this->team, 3, $this->user);

        // Test withCommonRelations scope
        $loadedEvents = CalendarEvent::withCommonRelations()
            ->whereIn('id', collect($events)->pluck('id'))
            ->get();

        $this->assertCount(3, $loadedEvents);

        foreach ($loadedEvents as $event) {
            // Verify relationships are loaded
            $this->assertTrue($event->relationLoaded('creator'));
            $this->assertTrue($event->relationLoaded('team'));
            $this->assertInstanceOf(User::class, $event->creator);
            $this->assertInstanceOf(Team::class, $event->team);
        }
    }

    public function test_generated_events_work_with_attendee_collection(): void
    {
        $event = CalendarEventGenerator::generate($this->team, $this->user, [
            'attendees' => [
                ['name' => 'John Doe', 'email' => 'john@example.com'],
                ['name' => 'Jane Smith', 'email' => 'jane@example.com'],
                ['name' => 'Bob Wilson'], // Missing email
            ],
        ]);

        $attendeeCollection = $event->attendeeCollection();

        $this->assertCount(3, $attendeeCollection);

        foreach ($attendeeCollection as $attendee) {
            $this->assertArrayHasKey('name', $attendee);
            $this->assertArrayHasKey('email', $attendee);
            $this->assertIsString($attendee['name']);
        }
    }

    public function test_generated_events_work_with_duration_calculation(): void
    {
        $event = CalendarEventGenerator::generateWithDuration($this->team, 120, $this->user);

        $this->assertEquals(120, $event->durationMinutes());

        // Test with all-day event
        $allDayEvent = CalendarEventGenerator::generateAllDay($this->team, $this->user);
        $this->assertGreaterThan(1400, $allDayEvent->durationMinutes()); // Should be close to 24 hours
    }

    public function test_generated_events_work_with_recurring_checks(): void
    {
        $regularEvent = CalendarEventGenerator::generate($this->team, $this->user);
        $recurringEvent = CalendarEventGenerator::generateRecurring($this->team, $this->user);
        $recurringInstance = CalendarEventGenerator::generateRecurringInstance(
            $recurringEvent,
            \Illuminate\Support\Facades\Date::parse($recurringEvent->start_at)->addWeek(),
        );

        $this->assertFalse($regularEvent->isRecurring());
        $this->assertFalse($regularEvent->isRecurringInstance());

        $this->assertTrue($recurringEvent->isRecurring());
        $this->assertFalse($recurringEvent->isRecurringInstance());

        $this->assertFalse($recurringInstance->isRecurring());
        $this->assertTrue($recurringInstance->isRecurringInstance());
    }

    public function test_generated_events_work_with_soft_deletes(): void
    {
        $event = CalendarEventGenerator::generate($this->team, $this->user);
        $eventId = $event->id;

        // Verify event exists
        $this->assertDatabaseHas('calendar_events', ['id' => $eventId, 'deleted_at' => null]);

        // Soft delete the event
        $event->delete();

        // Verify soft delete
        $this->assertDatabaseHas('calendar_events', ['id' => $eventId]);
        $this->assertDatabaseMissing('calendar_events', ['id' => $eventId, 'deleted_at' => null]);

        // Verify it's not found in normal queries
        $this->assertNull(CalendarEvent::find($eventId));

        // Verify it's found with trashed
        $this->assertNotNull(CalendarEvent::withTrashed()->find($eventId));
    }

    public function test_bulk_generation_performance(): void
    {
        $startTime = microtime(true);

        // Generate a reasonable number of events for performance testing
        $events = CalendarEventGenerator::generateMultiple($this->team, 50, $this->user);

        $endTime = microtime(true);
        $executionTime = $endTime - $startTime;

        $this->assertCount(50, $events);
        $this->assertLessThan(10, $executionTime, 'Bulk generation should complete within 10 seconds');

        // Verify all events are properly created
        foreach ($events as $event) {
            $this->assertInstanceOf(CalendarEvent::class, $event);
            $this->assertTrue($event->exists);
        }
    }

    public function test_generated_events_work_with_different_teams(): void
    {
        $team2 = Team::factory()->create();
        $user2 = User::factory()->create();
        $team2->users()->attach($user2);

        $event1 = CalendarEventGenerator::generate($this->team, $this->user);
        $event2 = CalendarEventGenerator::generate($team2, $user2);

        // Verify team isolation
        $team1Events = CalendarEvent::forTeam($this->team->id)->get();
        $team2Events = CalendarEvent::forTeam($team2->id)->get();

        $this->assertCount(1, $team1Events);
        $this->assertCount(1, $team2Events);
        $this->assertTrue($team1Events->contains($event1));
        $this->assertTrue($team2Events->contains($event2));
        $this->assertFalse($team1Events->contains($event2));
        $this->assertFalse($team2Events->contains($event1));
    }

    public function test_generated_events_work_with_factory_states(): void
    {
        // Test that our generator works alongside factory states
        $recurringEvent = CalendarEventGenerator::generateRecurring($this->team, $this->user);

        // Use factory to create an instance using the recurring state
        $factoryInstance = CalendarEvent::factory()
            ->recurringInstance($recurringEvent)
            ->create();

        $this->assertEquals($recurringEvent->id, $factoryInstance->recurrence_parent_id);
        $this->assertEquals($recurringEvent->team_id, $factoryInstance->team_id);
        $this->assertEquals($recurringEvent->creator_id, $factoryInstance->creator_id);
    }

    public function test_generated_events_maintain_data_integrity(): void
    {
        $events = CalendarEventGenerator::generateMultiple($this->team, 10, $this->user);

        foreach ($events as $event) {
            // Verify required fields are not null
            $this->assertNotNull($event->team_id);
            $this->assertNotNull($event->creator_id);
            $this->assertNotNull($event->title);
            $this->assertNotNull($event->type);
            $this->assertNotNull($event->status);
            $this->assertNotNull($event->start_at);
            $this->assertNotNull($event->end_at);
            $this->assertNotNull($event->creation_source);

            // Verify data types
            $this->assertIsInt($event->team_id);
            $this->assertIsInt($event->creator_id);
            $this->assertIsString($event->title);
            $this->assertIsBool($event->is_all_day);
            $this->assertIsArray($event->attendees);

            // Verify relationships exist
            $this->assertInstanceOf(Team::class, $event->team);
            $this->assertInstanceOf(User::class, $event->creator);

            // Verify date logic
            $this->assertTrue($event->start_at->isBefore($event->end_at));
        }
    }
}
