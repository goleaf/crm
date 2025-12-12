<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Call;
use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Call>
 */
final class CallFactory extends Factory
{
    protected $model = Call::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'team_id' => Team::factory(),
            'creator_id' => User::factory(),
            'direction' => fake()->randomElement(['inbound', 'outbound']),
            'phone_number' => fake()->phoneNumber(),
            'contact_name' => fake()->name(),
            'status' => fake()->randomElement(['scheduled', 'completed', 'missed', 'canceled']),
            'participants' => [
                ['name' => fake()->name(), 'phone' => fake()->phoneNumber()],
            ],
            'follow_up_required' => fake()->boolean(30),
        ];
    }

    public function scheduled(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => 'scheduled',
            'scheduled_at' => fake()->dateTimeBetween('+1 hour', '+1 week'),
        ]);
    }

    public function completed(): static
    {
        $startedAt = fake()->dateTimeBetween('-1 week', '-1 hour');
        $endedAt = fake()->dateTimeBetween($startedAt, $startedAt->format('Y-m-d H:i:s') . ' +2 hours');

        return $this->state(fn (array $attributes): array => [
            'status' => 'completed',
            'started_at' => $startedAt,
            'ended_at' => $endedAt,
            'duration_minutes' => fake()->numberBetween(1, 120),
            'outcome' => fake()->sentence(6),
        ]);
    }
}
