<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Task;
use App\Models\TaskDelegation;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TaskDelegation>
 */
final class TaskDelegationFactory extends Factory
{
    protected $model = TaskDelegation::class;

    public function definition(): array
    {
        return [
            'task_id' => Task::factory(),
            'from_user_id' => User::factory(),
            'to_user_id' => User::factory(),
            'status' => 'pending',
            'delegated_at' => now(),
            'note' => $this->faker->sentence(),
        ];
    }
}
