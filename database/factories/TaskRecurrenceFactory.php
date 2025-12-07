<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Task;
use App\Models\TaskRecurrence;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TaskRecurrence>
 */
final class TaskRecurrenceFactory extends Factory
{
    protected $model = TaskRecurrence::class;

    public function definition(): array
    {
        return [
            'task_id' => Task::factory(),
            'frequency' => 'weekly',
            'interval' => 1,
            'days_of_week' => ['friday'],
            'starts_on' => $this->faker->dateTimeBetween('now', '+1 day'),
            'max_occurrences' => 8,
            'timezone' => 'UTC',
            'is_active' => true,
        ];
    }
}
