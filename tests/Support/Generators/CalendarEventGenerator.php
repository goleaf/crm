<?php

declare(strict_types=1);

namespace Tests\Support\Generators;

use App\Enums\CalendarEventStatus;
use App\Enums\CalendarEventType;
use App\Enums\CalendarSyncStatus;
use App\Enums\CreationSource;
use App\Models\CalendarEvent;
use App\Models\Team;
use App\Models\User;
use Carbon\Carbon;
use InvalidArgumentException;

/**
 * Generator for creating random CalendarEvent instances for property-based testing.
 *
 * This generator provides various methods to create calendar events with realistic
 * data for testing different scenarios including recurring events, sync scenarios,
 * and edge cases.
 */
final class CalendarEventGenerator
{
    /**
     * Generate a random calendar event with all fields populated.
     *
     * @param Team                 $team      The team the event belongs to
     * @param User|null            $creator   The user creating the event (will create one if null)
     * @param array<string, mixed> $overrides Field overrides for customization
     *
     * @return CalendarEvent The created calendar event
     *
     * @throws InvalidArgumentException If start_at is after end_at in overrides
     */
    public static function generate(Team $team, ?User $creator = null, array $overrides = []): CalendarEvent
    {
        $creator ??= User::factory()->create();

        // Validate date overrides if provided
        if (isset($overrides['start_at'], $overrides['end_at'])) {
            $startAt = \Illuminate\Support\Facades\Date::parse($overrides['start_at']);
            $endAt = \Illuminate\Support\Facades\Date::parse($overrides['end_at']);

            if ($startAt->isAfter($endAt)) {
                throw new InvalidArgumentException('start_at cannot be after end_at');
            }
        }

        $data = array_merge(self::generateBaseData($team, $creator), $overrides);

        return CalendarEvent::factory()->create($data);
    }

    /**
     * Generate a recurring calendar event.
     *
     * @param Team                 $team      The team the event belongs to
     * @param User|null            $creator   The user creating the event
     * @param array<string, mixed> $overrides Field overrides for customization
     *
     * @return CalendarEvent The created recurring calendar event
     */
    public static function generateRecurring(Team $team, ?User $creator = null, array $overrides = []): CalendarEvent
    {
        $recurrenceRules = ['DAILY', 'WEEKLY', 'MONTHLY', 'YEARLY'];
        $baseStartAt = isset($overrides['start_at'])
            ? \Illuminate\Support\Facades\Date::parse($overrides['start_at'])
            : \Illuminate\Support\Facades\Date::parse(fake()->dateTimeBetween('-1 month', '+1 month'));

        $endDate = fake()->dateTimeBetween(
            $baseStartAt->copy()->addMonth(),
            $baseStartAt->copy()->addYear(),
        );

        return self::generate($team, $creator, array_merge([
            'recurrence_rule' => fake()->randomElement($recurrenceRules),
            'recurrence_end_date' => $endDate,
        ], $overrides));
    }

    /**
     * Generate a calendar event with external sync configuration.
     *
     * @param Team                 $team      The team the event belongs to
     * @param User|null            $creator   The user creating the event
     * @param array<string, mixed> $overrides Field overrides for customization
     *
     * @return CalendarEvent The created calendar event with sync data
     */
    public static function generateWithSync(Team $team, ?User $creator = null, array $overrides = []): CalendarEvent
    {
        $providers = ['google', 'outlook', 'apple'];

        return self::generate($team, $creator, array_merge([
            'sync_provider' => fake()->randomElement($providers),
            'sync_status' => fake()->randomElement(CalendarSyncStatus::cases()),
            'sync_external_id' => fake()->uuid(),
        ], $overrides));
    }

    /**
     * Generate a calendar event that is all-day.
     *
     * @param Team                 $team      The team the event belongs to
     * @param User|null            $creator   The user creating the event
     * @param array<string, mixed> $overrides Field overrides for customization
     *
     * @return CalendarEvent The created all-day calendar event
     */
    public static function generateAllDay(Team $team, ?User $creator = null, array $overrides = []): CalendarEvent
    {
        $startAt = fake()->dateTimeBetween('-1 month', '+3 months');
        $startOfDay = \Illuminate\Support\Facades\Date::parse($startAt)->startOfDay();

        return self::generate($team, $creator, array_merge([
            'is_all_day' => true,
            'start_at' => $startOfDay,
            'end_at' => $startOfDay->copy()->endOfDay(),
        ], $overrides));
    }

    /**
     * Generate a calendar event with a specific duration in minutes.
     *
     * @param Team                 $team            The team the event belongs to
     * @param int                  $durationMinutes Duration of the event in minutes
     * @param User|null            $creator         The user creating the event
     * @param array<string, mixed> $overrides       Field overrides for customization
     *
     * @return CalendarEvent The created calendar event with specified duration
     */
    public static function generateWithDuration(
        Team $team,
        int $durationMinutes,
        ?User $creator = null,
        array $overrides = [],
    ): CalendarEvent {
        $startAt = fake()->dateTimeBetween('-1 month', '+3 months');
        $endAt = \Illuminate\Support\Facades\Date::parse($startAt)->addMinutes($durationMinutes);

        return self::generate($team, $creator, array_merge([
            'start_at' => $startAt,
            'end_at' => $endAt,
        ], $overrides));
    }

    /**
     * Generate attendees array with realistic data.
     *
     * @param int|null $count Number of attendees to generate (random if null)
     *
     * @return array<int, array{name: string, email: string}>
     */
    private static function generateAttendees(?int $count = null): array
    {
        $count ??= fake()->numberBetween(1, 8);
        $attendees = [];

        for ($i = 0; $i < $count; $i++) {
            $attendees[] = [
                'name' => fake()->name(),
                'email' => fake()->safeEmail(),
            ];
        }

        return $attendees;
    }

    /**
     * Generate base data array for calendar events.
     *
     * This method contains the core logic for generating realistic calendar event data
     * that can be reused across different generation methods.
     *
     * @param Team $team    The team the event belongs to
     * @param User $creator The user creating the event
     *
     * @return array<string, mixed> Base data array for calendar event
     */
    private static function generateBaseData(Team $team, User $creator): array
    {
        $startAt = fake()->dateTimeBetween('-1 month', '+3 months');
        $endAt = fake()->dateTimeBetween($startAt, $startAt->format('Y-m-d H:i:s') . ' +4 hours');

        return [
            'team_id' => $team->id,
            'creator_id' => $creator->id,
            'title' => fake()->sentence(3),
            'type' => fake()->randomElement(CalendarEventType::cases()),
            'status' => fake()->randomElement(CalendarEventStatus::cases()),
            'is_all_day' => fake()->boolean(20), // 20% chance of all-day events
            'start_at' => $startAt,
            'end_at' => $endAt,
            'location' => fake()->optional(0.7)->address(),
            'room_booking' => fake()->optional(0.3)->words(2, true),
            'meeting_url' => fake()->optional(0.4)->url(),
            'reminder_minutes_before' => fake()->optional(0.8)->randomElement([5, 10, 15, 30, 60]),
            'attendees' => self::generateAttendees(),
            'notes' => fake()->optional(0.6)->paragraph(),
            'agenda' => fake()->optional(0.5)->paragraph(),
            'creation_source' => fake()->randomElement(CreationSource::cases()),
            // Include additional fields that might be needed
            'minutes' => fake()->optional(0.3)->paragraph(),
        ];
    }

    /**
     * Generate a calendar event with specific related record.
     *
     * @param Team                 $team          The team the event belongs to
     * @param object               $relatedRecord The related model (Company, People, etc.)
     * @param User|null            $creator       The user creating the event
     * @param array<string, mixed> $overrides     Field overrides for customization
     *
     * @return CalendarEvent The created calendar event with related record
     */
    public static function generateWithRelated(Team $team, object $relatedRecord, ?User $creator = null, array $overrides = []): CalendarEvent
    {
        $relatedId = null;
        $relatedType = null;

        if ($relatedRecord instanceof \Illuminate\Database\Eloquent\Model) {
            $relatedId = $relatedRecord->getKey();
            $relatedType = $relatedRecord->getMorphClass();
        } elseif (property_exists($relatedRecord, 'id')) {
            $relatedId = $relatedRecord->id;
            $relatedType = $relatedRecord::class;
        }

        if (! filled($relatedId) || ! filled($relatedType)) {
            throw new InvalidArgumentException('Related record must have a valid ID');
        }

        return self::generate($team, $creator, array_merge([
            'related_id' => $relatedId,
            'related_type' => $relatedType,
        ], $overrides));
    }

    /**
     * Generate a recurring instance of a parent event.
     *
     * @param CalendarEvent        $parentEvent     The parent recurring event
     * @param Carbon               $instanceStartAt The start time for this instance
     * @param array<string, mixed> $overrides       Field overrides for customization
     *
     * @return CalendarEvent The created recurring instance
     */
    public static function generateRecurringInstance(
        CalendarEvent $parentEvent,
        Carbon $instanceStartAt,
        array $overrides = [],
    ): CalendarEvent {
        if (! $parentEvent->isRecurring()) {
            throw new InvalidArgumentException('Parent event must be a recurring event');
        }

        $duration = $parentEvent->durationMinutes() ?? 60;
        $instanceEndAt = $instanceStartAt->copy()->addMinutes($duration);

        return self::generate($parentEvent->team, $parentEvent->creator, array_merge([
            'recurrence_parent_id' => $parentEvent->id,
            'title' => $parentEvent->title,
            'type' => $parentEvent->type,
            'location' => $parentEvent->location,
            'start_at' => $instanceStartAt,
            'end_at' => $instanceEndAt,
            'attendees' => $parentEvent->attendees,
            'agenda' => $parentEvent->agenda,
            'reminder_minutes_before' => $parentEvent->reminder_minutes_before,
        ], $overrides));
    }

    /**
     * Generate random calendar event data without creating a model.
     *
     * This method is useful for testing validation or when you need the data
     * but don't want to persist it to the database.
     *
     * @param Team      $team    The team the event belongs to
     * @param User|null $creator The user creating the event (will create one if null)
     *
     * @return array<string, mixed> Calendar event data array
     */
    public static function generateData(Team $team, ?User $creator = null): array
    {
        $creator ??= User::factory()->create();

        return self::generateBaseData($team, $creator);
    }

    /**
     * Generate multiple calendar events for testing bulk operations.
     *
     * @param Team                 $team          The team the events belong to
     * @param int                  $count         Number of events to generate
     * @param User|null            $creator       The user creating the events
     * @param array<string, mixed> $baseOverrides Base overrides applied to all events
     *
     * @return array<CalendarEvent> Array of created calendar events
     */
    public static function generateMultiple(
        Team $team,
        int $count,
        ?User $creator = null,
        array $baseOverrides = [],
    ): array {
        $events = [];

        for ($i = 0; $i < $count; $i++) {
            $events[] = self::generate($team, $creator, $baseOverrides);
        }

        return $events;
    }

    /**
     * Generate a calendar event with edge case data for testing robustness.
     *
     * @param Team                 $team      The team the event belongs to
     * @param User|null            $creator   The user creating the event
     * @param array<string, mixed> $overrides Field overrides for customization
     *
     * @return CalendarEvent The created calendar event with edge case data
     */
    public static function generateEdgeCase(Team $team, ?User $creator = null, array $overrides = []): CalendarEvent
    {
        $edgeCases = [
            // Very long title
            ['title' => str_repeat('Very Long Event Title ', 20)],
            // Empty attendees
            ['attendees' => []],
            // Maximum attendees
            ['attendees' => self::generateAttendees(50)],
            // Very short duration (1 minute)
            ['start_at' => now(), 'end_at' => now()->addMinute()],
            // Very long duration (24 hours)
            ['start_at' => now(), 'end_at' => now()->addDay()],
            // Far future event
            ['start_at' => now()->addYears(5), 'end_at' => now()->addYears(5)->addHour()],
            // Past event
            ['start_at' => now()->subYears(2), 'end_at' => now()->subYears(2)->addHour()],
        ];

        $randomEdgeCase = fake()->randomElement($edgeCases);

        return self::generate($team, $creator, array_merge($randomEdgeCase, $overrides));
    }
}
