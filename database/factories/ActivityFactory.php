<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Activity;
use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\Sequence;

/**
 * @extends Factory<Activity>
 */
final class ActivityFactory extends Factory
{
    protected $model = Activity::class;

    public function definition(): array
    {
        return [
            'team_id' => Team::factory(),
            'causer_id' => User::factory(),
            'event' => $this->faker->randomElement(['created', 'updated', 'deleted', 'commented']),
            'changes' => [
                'before' => ['name' => $this->faker->company()],
                'after' => ['name' => $this->faker->company().' Updated'],
            ],
        ];
    }

    public function configure(): Factory
    {
        return $this->sequence(fn (Sequence $sequence): array => [
            'created_at' => now()->subMinutes($sequence->index),
            'updated_at' => now()->subMinutes($sequence->index),
        ]);
    }
}
