<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Task;
use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\Sequence;

/**
 * @extends Factory<Task>
 */
final class TaskFactory extends Factory
{
    protected $model = Task::class;

    public function definition(): array
    {
        return [
            'title' => $this->faker->sentence(3),
            'created_at' => \Illuminate\Support\Facades\Date::now(),
            'updated_at' => \Illuminate\Support\Facades\Date::now(),
            // Use null defaults - caller should provide these to avoid cascading factory creation
            'team_id' => null,
            'creator_id' => null,
        ];
    }

    /**
     * Create with all related factories (for standalone tests).
     * Use this when you need a fully populated Task without providing relations.
     */
    public function withRelations(): static
    {
        return $this->state(fn (): array => [
            'team_id' => Team::factory(),
            'creator_id' => User::factory(),
        ]);
    }

    public function configure(): Factory
    {
        // Use minutes instead of seconds to ensure distinct timestamps
        // and avoid flaky sorting tests in fast CI environments
        return $this->sequence(fn (Sequence $sequence): array => [
            'created_at' => now()->subMinutes($sequence->index),
            'updated_at' => now()->subMinutes($sequence->index),
        ]);
    }
}
