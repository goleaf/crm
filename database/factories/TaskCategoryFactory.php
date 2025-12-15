<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\TaskCategory;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TaskCategory>
 */
final class TaskCategoryFactory extends Factory
{
    protected $model = TaskCategory::class;

    public function definition(): array
    {
        return [
            'team_id' => 1,
            'name' => $this->faker->word(),
            'color' => $this->faker->safeHexColor(),
        ];
    }
}
