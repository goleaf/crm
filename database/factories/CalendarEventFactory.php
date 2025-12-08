<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\CalendarEventStatus;
use App\Enums\CalendarEventType;
use App\Models\CalendarEvent;
use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

/**
 * @extends Factory<CalendarEvent>
 */
final class CalendarEventFactory extends Factory
{
    protected $model = CalendarEvent::class;

    public function definition(): array
    {
        $start = \Illuminate\Support\Facades\Date::now()->addDays($this->faker->numberBetween(0, 10))->setMinutes(0);

        return [
            'team_id' => Team::factory(),
            'creator_id' => User::factory(),
            'title' => $this->faker->sentence(3),
            'type' => CalendarEventType::MEETING,
            'status' => CalendarEventStatus::SCHEDULED,
            'start_at' => $start,
            'end_at' => (clone $start)->addHour(),
            'location' => $this->faker->randomElement(['Zoom', 'Google Meet', 'Office']),
            'room_booking' => $this->faker->optional()->randomElement(['Conference Room A', 'Conference Room B', 'Meeting Room 1']),
            'meeting_url' => $this->faker->url(),
            'attendees' => [
                ['name' => $this->faker->name(), 'email' => $this->faker->safeEmail()],
            ],
            'agenda' => $this->faker->optional()->paragraph(),
            'minutes' => null,
        ];
    }

    /**
     * Indicate that the event is recurring.
     */
    public function recurring(string $rule = 'WEEKLY', ?Carbon $endDate = null): static
    {
        return $this->state(fn (array $attributes): array => [
            'recurrence_rule' => $rule,
            'recurrence_end_date' => $endDate ?? \Illuminate\Support\Facades\Date::parse($attributes['start_at'])->addMonths(3),
        ]);
    }

    /**
     * Indicate that the event is a recurring instance.
     */
    public function recurringInstance(CalendarEvent $parent): static
    {
        return $this->state(fn (array $attributes): array => [
            'recurrence_parent_id' => $parent->id,
            'team_id' => $parent->team_id,
            'creator_id' => $parent->creator_id,
        ]);
    }
}
