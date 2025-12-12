<?php

declare(strict_types=1);

namespace Tests\Unit\Support\Generators;

use App\Enums\CalendarEventStatus;
use App\Enums\CalendarEventType;
use App\Enums\CalendarSyncStatus;
use App\Enums\CreationSource;
use App\Models\CalendarEvent;
use App\Models\Company;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Tests\Support\Generators\CalendarEventGenerator;
use Tests\TestCase;

/**
 * Test suite for CalendarEventGenerator.
 *
 * This test suite ensures the generator creates valid calendar events
 * with proper data types, relationships, and edge case handling.
 */
final class CalendarEventGeneratorTest extends TestCase
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

    public function test_generate_creates_valid_calendar_event(): void
    {
        $event = CalendarEventGenerator::generate($this->team, $this->user);

        $this->assertInstanceOf(CalendarEvent::class, $event);
        $this->assertTrue($event->exists);
        $this->assertEquals($this->team->id, $event->team_id);
        $this->assertEquals($this->user->id, $event->creator_id);
        $this->assertNotEmpty($event->title);
        $this->assertInstanceOf(CalendarEventType::class, $event->type);
        $this->assertInstanceOf(CalendarEventStatus::class, $event->status);
        $this->assertInstanceOf(CreationSource::class, $event->creation_source);
        $this->assertNotNull($event->start_at);
        $this->assertNotNull($event->end_at);
        $this->assertTrue($event->start_at->isBefore($event->end_at));
    }

    public function test_generate_creates_user_when_creator_is_null(): void
    {
        $initialUserCount = User::count();

        $event = CalendarEventGenerator::generate($this->team);

        $this->assertEquals($initialUserCount + 1, User::count());
        $this->assertNotNull($event->creator_id);
        $this->assertInstanceOf(User::class, $event->creator);
    }

    public function test_generate_applies_overrides_correctly(): void
    {
        $customTitle = 'Custom Event Title';
        $customType = CalendarEventType::DEMO;
        $customStatus = CalendarEventStatus::CONFIRMED;

        $event = CalendarEventGenerator::generate($this->team, $this->user, [
            'title' => $customTitle,
            'type' => $customType,
            'status' => $customStatus,
        ]);

        $this->assertEquals($customTitle, $event->title);
        $this->assertEquals($customType, $event->type);
        $this->assertEquals($customStatus, $event->status);
    }

    public function test_generate_validates_date_overrides(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('start_at cannot be after end_at');

        CalendarEventGenerator::generate($this->team, $this->user, [
            'start_at' => now()->addHour(),
            'end_at' => now(),
        ]);
    }

    public function test_generate_recurring_creates_recurring_event(): void
    {
        $event = CalendarEventGenerator::generateRecurring($this->team, $this->user);

        $this->assertNotNull($event->recurrence_rule);
        $this->assertNotNull($event->recurrence_end_date);
        $this->assertTrue($event->isRecurring());
        $this->assertFalse($event->isRecurringInstance());
        $this->assertContains($event->recurrence_rule, ['DAILY', 'WEEKLY', 'MONTHLY', 'YEARLY']);
        $this->assertTrue($event->recurrence_end_date->isAfter($event->start_at));
    }

    public function test_generate_recurring_respects_start_at_override(): void
    {
        $customStartAt = now()->addWeek();

        $event = CalendarEventGenerator::generateRecurring($this->team, $this->user, [
            'start_at' => $customStartAt,
        ]);

        $this->assertEquals($customStartAt->format('Y-m-d H:i:s'), $event->start_at->format('Y-m-d H:i:s'));
        $this->assertTrue($event->recurrence_end_date->isAfter($customStartAt));
    }

    public function test_generate_with_sync_creates_synced_event(): void
    {
        $event = CalendarEventGenerator::generateWithSync($this->team, $this->user);

        $this->assertNotNull($event->sync_provider);
        $this->assertNotNull($event->sync_status);
        $this->assertNotNull($event->sync_external_id);
        $this->assertContains($event->sync_provider, ['google', 'outlook', 'apple']);
        $this->assertInstanceOf(CalendarSyncStatus::class, $event->sync_status);
    }

    public function test_generate_all_day_creates_all_day_event(): void
    {
        $event = CalendarEventGenerator::generateAllDay($this->team, $this->user);

        $this->assertTrue($event->is_all_day);
        $this->assertEquals('00:00:00', $event->start_at->format('H:i:s'));
        $this->assertEquals('23:59:59', $event->end_at->format('H:i:s'));
        $this->assertEquals($event->start_at->format('Y-m-d'), $event->end_at->format('Y-m-d'));
    }

    public function test_generate_with_duration_creates_event_with_correct_duration(): void
    {
        $durationMinutes = 90;

        $event = CalendarEventGenerator::generateWithDuration($this->team, $durationMinutes, $this->user);

        $this->assertEquals($durationMinutes, $event->durationMinutes());
        $this->assertEquals($durationMinutes, $event->end_at->diffInMinutes($event->start_at));
    }

    public function test_generate_with_related_creates_event_with_relationship(): void
    {
        $company = Company::factory()->create(['team_id' => $this->team->id]);

        $event = CalendarEventGenerator::generateWithRelated($this->team, $company, $this->user);

        $this->assertEquals($company->id, $event->related_id);
        $this->assertEquals(Company::class, $event->related_type);
        $this->assertInstanceOf(Company::class, $event->related);
        $this->assertEquals($company->id, $event->related->id);
    }

    public function test_generate_with_related_validates_related_record(): void
    {
        $invalidRecord = new \stdClass;

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Related record must have a valid ID');

        CalendarEventGenerator::generateWithRelated($this->team, $invalidRecord, $this->user);
    }

    public function test_generate_recurring_instance_creates_valid_instance(): void
    {
        $parentEvent = CalendarEventGenerator::generateRecurring($this->team, $this->user);
        $instanceStartAt = \Illuminate\Support\Facades\Date::parse($parentEvent->start_at)->addWeek();

        $instance = CalendarEventGenerator::generateRecurringInstance($parentEvent, $instanceStartAt);

        $this->assertEquals($parentEvent->id, $instance->recurrence_parent_id);
        $this->assertTrue($instance->isRecurringInstance());
        $this->assertFalse($instance->isRecurring());
        $this->assertEquals($parentEvent->title, $instance->title);
        $this->assertEquals($parentEvent->type, $instance->type);
        $this->assertEquals($parentEvent->location, $instance->location);
        $this->assertEquals($instanceStartAt->format('Y-m-d H:i:s'), $instance->start_at->format('Y-m-d H:i:s'));
        $this->assertEquals($parentEvent->durationMinutes(), $instance->durationMinutes());
    }

    public function test_generate_recurring_instance_validates_parent_event(): void
    {
        $nonRecurringEvent = CalendarEventGenerator::generate($this->team, $this->user);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Parent event must be a recurring event');

        CalendarEventGenerator::generateRecurringInstance($nonRecurringEvent, now());
    }

    public function test_generate_data_returns_valid_array_without_creating_model(): void
    {
        $initialEventCount = CalendarEvent::count();

        $data = CalendarEventGenerator::generateData($this->team, $this->user);

        $this->assertEquals($initialEventCount, CalendarEvent::count());
        $this->assertIsArray($data);
        $this->assertArrayHasKey('team_id', $data);
        $this->assertArrayHasKey('creator_id', $data);
        $this->assertArrayHasKey('title', $data);
        $this->assertArrayHasKey('type', $data);
        $this->assertArrayHasKey('status', $data);
        $this->assertArrayHasKey('start_at', $data);
        $this->assertArrayHasKey('end_at', $data);
        $this->assertEquals($this->team->id, $data['team_id']);
        $this->assertEquals($this->user->id, $data['creator_id']);
    }

    public function test_generate_multiple_creates_correct_number_of_events(): void
    {
        $count = 5;
        $baseOverrides = ['type' => CalendarEventType::MEETING];

        $events = CalendarEventGenerator::generateMultiple($this->team, $count, $this->user, $baseOverrides);

        $this->assertCount($count, $events);

        foreach ($events as $event) {
            $this->assertInstanceOf(CalendarEvent::class, $event);
            $this->assertTrue($event->exists);
            $this->assertEquals(CalendarEventType::MEETING, $event->type);
            $this->assertEquals($this->team->id, $event->team_id);
            $this->assertEquals($this->user->id, $event->creator_id);
        }
    }

    public function test_generate_edge_case_creates_event_with_edge_case_data(): void
    {
        $event = CalendarEventGenerator::generateEdgeCase($this->team, $this->user);

        $this->assertInstanceOf(CalendarEvent::class, $event);
        $this->assertTrue($event->exists);

        // The event should have some edge case characteristic
        // We can't predict which one, but we can verify it's still valid
        $this->assertNotNull($event->title);
        $this->assertNotNull($event->start_at);
        $this->assertNotNull($event->end_at);
    }

    public function test_generate_edge_case_with_long_title(): void
    {
        // Force a specific edge case by providing overrides that match one of the edge cases
        $event = CalendarEventGenerator::generateEdgeCase($this->team, $this->user, [
            'title' => str_repeat('Very Long Event Title ', 20),
        ]);

        $this->assertStringContainsString('Very Long Event Title', $event->title);
        $this->assertGreaterThan(100, strlen($event->title));
    }

    public function test_generate_edge_case_with_empty_attendees(): void
    {
        $event = CalendarEventGenerator::generateEdgeCase($this->team, $this->user, [
            'attendees' => [],
        ]);

        $this->assertEmpty($event->attendees);
        $this->assertEquals(0, $event->attendeeCollection()->count());
    }

    public function test_generate_edge_case_with_maximum_attendees(): void
    {
        $event = CalendarEventGenerator::generateEdgeCase($this->team, $this->user, [
            'attendees' => array_fill(0, 50, ['name' => 'Test User', 'email' => 'test@example.com']),
        ]);

        $this->assertCount(50, $event->attendees);
        $this->assertEquals(50, $event->attendeeCollection()->count());
    }

    public function test_generate_edge_case_with_very_short_duration(): void
    {
        $startAt = now();
        $endAt = $startAt->copy()->addMinute();

        $event = CalendarEventGenerator::generateEdgeCase($this->team, $this->user, [
            'start_at' => $startAt,
            'end_at' => $endAt,
        ]);

        $this->assertEquals(1, $event->durationMinutes());
    }

    public function test_generate_edge_case_with_very_long_duration(): void
    {
        $startAt = now();
        $endAt = $startAt->copy()->addDay();

        $event = CalendarEventGenerator::generateEdgeCase($this->team, $this->user, [
            'start_at' => $startAt,
            'end_at' => $endAt,
        ]);

        $this->assertEquals(1440, $event->durationMinutes()); // 24 hours = 1440 minutes
    }

    public function test_attendees_generation_creates_valid_attendee_structure(): void
    {
        $event = CalendarEventGenerator::generate($this->team, $this->user);

        $this->assertIsArray($event->attendees);

        foreach ($event->attendees as $attendee) {
            $this->assertIsArray($attendee);
            $this->assertArrayHasKey('name', $attendee);
            $this->assertArrayHasKey('email', $attendee);
            $this->assertIsString($attendee['name']);
            $this->assertIsString($attendee['email']);
            $this->assertNotEmpty($attendee['name']);
            $this->assertNotEmpty($attendee['email']);
            $this->assertStringContainsString('@', $attendee['email']);
        }
    }

    public function test_generated_events_have_consistent_team_scoping(): void
    {
        $events = CalendarEventGenerator::generateMultiple($this->team, 3, $this->user);

        foreach ($events as $event) {
            $this->assertEquals($this->team->id, $event->team_id);
            $this->assertEquals($this->user->id, $event->creator_id);
        }
    }

    public function test_generated_events_have_valid_enum_values(): void
    {
        $event = CalendarEventGenerator::generate($this->team, $this->user);

        $this->assertContains($event->type, CalendarEventType::cases());
        $this->assertContains($event->status, CalendarEventStatus::cases());
        $this->assertContains($event->creation_source, CreationSource::cases());
    }

    public function test_generated_events_have_realistic_date_ranges(): void
    {
        $event = CalendarEventGenerator::generate($this->team, $this->user);

        // Event should be within reasonable past/future range
        $oneMonthAgo = now()->subMonth();
        $threeMonthsFromNow = now()->addMonths(3);

        $this->assertTrue($event->start_at->between($oneMonthAgo, $threeMonthsFromNow));
        $this->assertTrue($event->end_at->isAfter($event->start_at));

        // Duration should be reasonable (not more than 4 hours for regular generation)
        $this->assertLessThanOrEqual(240, $event->durationMinutes()); // 4 hours max
    }

    public function test_all_generation_methods_create_valid_events(): void
    {
        $methods = [
            'generate',
            'generateRecurring',
            'generateWithSync',
            'generateAllDay',
        ];

        foreach ($methods as $method) {
            $event = CalendarEventGenerator::$method($this->team, $this->user);

            $this->assertInstanceOf(CalendarEvent::class, $event, "Method {$method} should create CalendarEvent");
            $this->assertTrue($event->exists, "Method {$method} should persist the event");
            $this->assertEquals($this->team->id, $event->team_id, "Method {$method} should set correct team");
            $this->assertEquals($this->user->id, $event->creator_id, "Method {$method} should set correct creator");
        }
    }
}
