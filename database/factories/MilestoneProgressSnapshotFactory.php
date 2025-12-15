<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Milestone;
use App\Models\MilestoneProgressSnapshot;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MilestoneProgressSnapshot>
 */
final class MilestoneProgressSnapshotFactory extends Factory
{
    protected $model = MilestoneProgressSnapshot::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'milestone_id' => Milestone::factory(),
            'completion_percentage' => fake()->randomFloat(2, 0, 100),
            'schedule_variance_days' => fake()->numberBetween(-10, 10),
            'remaining_tasks_count' => fake()->numberBetween(0, 20),
            'blocked_tasks_count' => fake()->numberBetween(0, 10),
        ];
    }
}

