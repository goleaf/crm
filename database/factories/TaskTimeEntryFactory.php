<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Task;
use App\Models\TaskTimeEntry;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TaskTimeEntry>
 */
final class TaskTimeEntryFactory extends Factory
{
    protected $model = TaskTimeEntry::class;

    public function definition(): array
    {
        $start = $this->faker->dateTimeBetween('-1 week', 'now');
        $end = (clone $start)->modify('+45 minutes');

        return [
            'task_id' => Task::factory(),
            'user_id' => User::factory(),
            'started_at' => $start,
            'ended_at' => $end,
            'duration_minutes' => 45,
            'note' => $this->faker->sentence(),
        ];
    }
}
