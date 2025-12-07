<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\TaskTemplate;
use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TaskTemplate>
 */
final class TaskTemplateFactory extends Factory
{
    protected $model = TaskTemplate::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'team_id' => Team::factory(),
            'creator_id' => User::factory(),
            'name' => fake()->sentence(3),
            'description' => fake()->paragraph(),
            'estimated_duration_minutes' => fake()->numberBetween(30, 480),
            'is_milestone' => fake()->boolean(20),
            'default_assignees' => null,
            'checklist_items' => null,
        ];
    }

    public function milestone(): self
    {
        return $this->state(fn (array $attributes): array => [
            'is_milestone' => true,
        ]);
    }

    public function withChecklistItems(int $count = 3): self
    {
        return $this->state(fn (array $attributes): array => [
            'checklist_items' => collect(range(1, $count))
                ->map(fn (int $i): array => [
                    'title' => fake()->sentence(4),
                ])
                ->toArray(),
        ]);
    }
}
