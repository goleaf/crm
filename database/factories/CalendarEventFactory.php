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
        $start = Carbon::now()->addDays($this->faker->numberBetween(0, 10))->setMinutes(0);

        return [
            'team_id' => Team::factory(),
            'creator_id' => User::factory(),
            'title' => $this->faker->sentence(3),
            'type' => CalendarEventType::MEETING,
            'status' => CalendarEventStatus::SCHEDULED,
            'start_at' => $start,
            'end_at' => (clone $start)->addHour(),
            'location' => $this->faker->randomElement(['Zoom', 'Google Meet', 'Office']),
            'meeting_url' => $this->faker->url(),
            'attendees' => [
                ['name' => $this->faker->name(), 'email' => $this->faker->safeEmail()],
            ],
        ];
    }
}
