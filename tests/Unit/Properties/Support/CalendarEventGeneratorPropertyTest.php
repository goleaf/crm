<?php

declare(strict_types=1);

namespace Tests\Unit\Properties\Support;

use App\Enums\CalendarEventStatus;
use App\Enums\CalendarEventType;
use App\Enums\CalendarSyncStatus;
use App\Enums\CreationSource;
use App\Models\Team;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\Generators\CalendarEventGenerator;
use Tests\Support\PropertyTestCase;

/**
 * Property-based tests for CalendarEventGenerator.
 *
 * These tests use property-based testing to verify that the generator
 * maintains invariants across a wide range of inputs and scenarios.
 */
final class CalendarEventGeneratorPropertyTest extends PropertyTestCase
{
    use RefreshDatabase;

    /**
     * Property: Generated events always have valid date relationships.
     *
     * For any generated calendar event, the start_at should always be before end_at,
     * and both dates should be valid Carbon instances.
     */
    public function test_property_generated_events_have_valid_date_relationships(): void
    {
        $this->forAll(
            $this->teamGenerator(),
            $this->userGenerator(),
            $this->overridesGenerator(),
        )->then(function (Team $team, User $user, array $overrides): void {
            $event = CalendarEventGenerator::generate($team, $user, $overrides);

            // Property: start_at is always before end_at
            $this->assertTrue(
                $event->start_at->isBefore($event->end_at),
                'start_at must be before end_at',
            );

            // Property: Both dates are valid Carbon instances
            $this->assertInstanceOf(Carbon::class, $event->start_at);
            $this->assertInstanceOf(Carbon::class, $event->end_at);

            // Property: Duration is always positive
            $this->assertGreaterThan(0, $event->durationMinutes());
        });
    }

    /**
     * Property: Generated events always belong to the specified team and creator.
     *
     * Regardless of overrides, the team_id and creator_id should always match
     * the provided team and user.
     */
    public function test_property_generated_events_maintain_team_and_creator_ownership(): void
    {
        $this->forAll(
            $this->teamGenerator(),
            $this->userGenerator(),
            $this->overridesGenerator(),
        )->then(function (Team $team, User $user, array $overrides): void {
            $event = CalendarEventGenerator::generate($team, $user, $overrides);

            // Property: Team ownership is always maintained
            $this->assertEquals($team->id, $event->team_id);
            $this->assertEquals($team->id, $event->team->id);

            // Property: Creator ownership is always maintained
            $this->assertEquals($user->id, $event->creator_id);
            $this->assertEquals($user->id, $event->creator->id);
        });
    }

    /**
     * Property: Generated events always have valid enum values.
     *
     * All enum fields should contain valid enum cases, never invalid values.
     */
    public function test_property_generated_events_have_valid_enum_values(): void
    {
        $this->forAll(
            $this->teamGenerator(),
            $this->userGenerator(),
            $this->overridesGenerator(),
        )->then(function (Team $team, User $user, array $overrides): void {
            $event = CalendarEventGenerator::generate($team, $user, $overrides);

            // Property: Type is always a valid CalendarEventType
            $this->assertInstanceOf(CalendarEventType::class, $event->type);
            $this->assertContains($event->type, CalendarEventType::cases());

            // Property: Status is always a valid CalendarEventStatus
            $this->assertInstanceOf(CalendarEventStatus::class, $event->status);
            $this->assertContains($event->status, CalendarEventStatus::cases());

            // Property: Creation source is always a valid CreationSource
            $this->assertInstanceOf(CreationSource::class, $event->creation_source);
            $this->assertContains($event->creation_source, CreationSource::cases());
        });
    }

    /**
     * Property: Recurring events always have valid recurrence data.
     *
     * When generating recurring events, the recurrence_rule and recurrence_end_date
     * should always be properly set and logically consistent.
     */
    public function test_property_recurring_events_have_valid_recurrence_data(): void
    {
        $this->forAll(
            $this->teamGenerator(),
            $this->userGenerator(),
            $this->overridesGenerator(),
        )->then(function (Team $team, User $user, array $overrides): void {
            $event = CalendarEventGenerator::generateRecurring($team, $user, $overrides);

            // Property: Recurring events have recurrence rule
            $this->assertNotNull($event->recurrence_rule);
            $this->assertContains($event->recurrence_rule, ['DAILY', 'WEEKLY', 'MONTHLY', 'YEARLY']);

            // Property: Recurring events have end date after start date
            $this->assertNotNull($event->recurrence_end_date);
            $this->assertTrue($event->recurrence_end_date->isAfter($event->start_at));

            // Property: isRecurring() returns true
            $this->assertTrue($event->isRecurring());

            // Property: isRecurringInstance() returns false for parent events
            $this->assertFalse($event->isRecurringInstance());
        });
    }

    /**
     * Property: Events with sync data always have consistent sync fields.
     *
     * When generating events with sync, all sync-related fields should be
     * properly populated and consistent.
     */
    public function test_property_synced_events_have_consistent_sync_data(): void
    {
        $this->forAll(
            $this->teamGenerator(),
            $this->userGenerator(),
            $this->overridesGenerator(),
        )->then(function (Team $team, User $user, array $overrides): void {
            $event = CalendarEventGenerator::generateWithSync($team, $user, $overrides);

            // Property: Sync provider is always set
            $this->assertNotNull($event->sync_provider);
            $this->assertContains($event->sync_provider, ['google', 'outlook', 'apple']);

            // Property: Sync status is always a valid enum
            $this->assertInstanceOf(CalendarSyncStatus::class, $event->sync_status);
            $this->assertContains($event->sync_status, CalendarSyncStatus::cases());

            // Property: Sync external ID is always set
            $this->assertNotNull($event->sync_external_id);
            $this->assertIsString($event->sync_external_id);
            $this->assertNotEmpty($event->sync_external_id);
        });
    }

    /**
     * Property: All-day events always have proper time boundaries.
     *
     * All-day events should start at the beginning of a day and end at the end
     * of the same day, with the is_all_day flag set to true.
     */
    public function test_property_all_day_events_have_proper_time_boundaries(): void
    {
        $this->forAll(
            $this->teamGenerator(),
            $this->userGenerator(),
            $this->overridesGenerator(),
        )->then(function (Team $team, User $user, array $overrides): void {
            $event = CalendarEventGenerator::generateAllDay($team, $user, $overrides);

            // Property: is_all_day flag is true
            $this->assertTrue($event->is_all_day);

            // Property: Start time is at beginning of day
            $this->assertEquals('00:00:00', $event->start_at->format('H:i:s'));

            // Property: End time is at end of day
            $this->assertEquals('23:59:59', $event->end_at->format('H:i:s'));

            // Property: Both dates are on the same day
            $this->assertEquals(
                $event->start_at->format('Y-m-d'),
                $event->end_at->format('Y-m-d'),
            );
        });
    }

    /**
     * Property: Events with specific duration always have correct duration.
     *
     * When generating events with a specific duration, the actual duration
     * should always match the requested duration.
     */
    public function test_property_duration_events_have_correct_duration(): void
    {
        $this->forAll(
            $this->teamGenerator(),
            $this->durationGenerator(),
            $this->userGenerator(),
            $this->overridesGenerator(),
        )->then(function (Team $team, int $duration, User $user, array $overrides): void {
            $event = CalendarEventGenerator::generateWithDuration($team, $duration, $user, $overrides);

            // Property: Duration matches requested duration
            $this->assertEquals($duration, $event->durationMinutes());

            // Property: End time is exactly duration minutes after start time
            $expectedEndTime = $event->start_at->copy()->addMinutes($duration);
            $this->assertEquals(
                $expectedEndTime->format('Y-m-d H:i:s'),
                $event->end_at->format('Y-m-d H:i:s'),
            );
        });
    }

    /**
     * Property: Attendees array always has valid structure.
     *
     * The attendees array should always contain properly structured attendee
     * objects with required fields.
     */
    public function test_property_attendees_have_valid_structure(): void
    {
        $this->forAll(
            $this->teamGenerator(),
            $this->userGenerator(),
            $this->overridesGenerator(),
        )->then(function (Team $team, User $user, array $overrides): void {
            $event = CalendarEventGenerator::generate($team, $user, $overrides);

            // Property: Attendees is always an array
            $this->assertIsArray($event->attendees);

            // Property: Each attendee has required structure
            foreach ($event->attendees as $attendee) {
                $this->assertIsArray($attendee);
                $this->assertArrayHasKey('name', $attendee);
                $this->assertArrayHasKey('email', $attendee);
                $this->assertIsString($attendee['name']);
                $this->assertIsString($attendee['email']);
                $this->assertNotEmpty($attendee['name']);
                $this->assertNotEmpty($attendee['email']);
            }

            // Property: attendeeCollection() works correctly
            $collection = $event->attendeeCollection();
            $this->assertCount(count($event->attendees), $collection);
        });
    }

    /**
     * Property: Recurring instances maintain parent relationship integrity.
     *
     * Recurring instances should always properly reference their parent event
     * and inherit appropriate properties.
     */
    public function test_property_recurring_instances_maintain_parent_integrity(): void
    {
        $this->forAll(
            $this->teamGenerator(),
            $this->userGenerator(),
        )->then(function (Team $team, User $user): void {
            $parentEvent = CalendarEventGenerator::generateRecurring($team, $user);
            $instanceStartAt = \Illuminate\Support\Facades\Date::parse($parentEvent->start_at)->addWeek();

            $instance = CalendarEventGenerator::generateRecurringInstance($parentEvent, $instanceStartAt);

            // Property: Instance references parent correctly
            $this->assertEquals($parentEvent->id, $instance->recurrence_parent_id);
            $this->assertEquals($parentEvent->id, $instance->recurrenceParent->id);

            // Property: Instance inherits team and creator
            $this->assertEquals($parentEvent->team_id, $instance->team_id);
            $this->assertEquals($parentEvent->creator_id, $instance->creator_id);

            // Property: Instance has correct recurring flags
            $this->assertFalse($instance->isRecurring());
            $this->assertTrue($instance->isRecurringInstance());

            // Property: Instance maintains duration
            $this->assertEquals($parentEvent->durationMinutes(), $instance->durationMinutes());
        });
    }

    /**
     * Property: Multiple events generation maintains consistency.
     *
     * When generating multiple events, they should all maintain the same
     * base properties while having unique identities.
     */
    public function test_property_multiple_events_maintain_consistency(): void
    {
        $this->forAll(
            $this->teamGenerator(),
            $this->countGenerator(),
            $this->userGenerator(),
            $this->overridesGenerator(),
        )->then(function (Team $team, int $count, User $user, array $overrides): void {
            $events = CalendarEventGenerator::generateMultiple($team, $count, $user, $overrides);

            // Property: Correct number of events generated
            $this->assertCount($count, $events);

            // Property: All events are unique instances
            $ids = collect($events)->pluck('id')->toArray();
            $this->assertCount($count, array_unique($ids));

            // Property: All events share common properties from overrides
            foreach ($events as $event) {
                $this->assertEquals($team->id, $event->team_id);
                $this->assertEquals($user->id, $event->creator_id);

                // Check that overrides were applied
                foreach ($overrides as $key => $value) {
                    if (in_array($key, ['start_at', 'end_at'])) {
                        continue; // Skip date comparisons as they may be modified
                    }
                    $this->assertEquals($value, $event->$key);
                }
            }
        });
    }

    // Generator methods for property-based testing

    private function teamGenerator(): \Generator
    {
        while (true) {
            yield Team::factory()->create();
        }
    }

    private function userGenerator(): \Generator
    {
        while (true) {
            yield User::factory()->create();
        }
    }

    private function overridesGenerator(): \Generator
    {
        $possibleOverrides = [
            [],
            ['title' => 'Custom Title'],
            ['type' => CalendarEventType::DEMO],
            ['status' => CalendarEventStatus::CONFIRMED],
            ['is_all_day' => true],
            ['location' => 'Custom Location'],
            ['notes' => 'Custom notes'],
            ['agenda' => 'Custom agenda'],
        ];

        while (true) {
            yield fake()->randomElement($possibleOverrides);
        }
    }

    private function durationGenerator(): \Generator
    {
        $durations = [15, 30, 60, 90, 120, 180, 240, 480]; // Common meeting durations in minutes

        while (true) {
            yield fake()->randomElement($durations);
        }
    }

    private function countGenerator(): \Generator
    {
        while (true) {
            yield fake()->numberBetween(1, 10);
        }
    }
}
