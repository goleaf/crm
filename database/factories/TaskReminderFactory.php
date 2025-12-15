<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Task;
use App\Models\TaskReminder;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TaskReminder>
 */
final class TaskReminderFactory extends Factory
{
    protected $model = TaskReminder::class;

    public function definition(): array
    {
        $remindAt = $this->faker->dateTimeBetween('+1 day', '+2 days');

        return [
            'task_id' => Task::factory(),
            'user_id' => User::factory(),
            'remind_at' => $remindAt,
            'status' => 'pending',
            'channel' => 'database',
        ];
    }
}
