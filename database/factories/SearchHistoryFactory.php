<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\SearchHistory;
use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SearchHistory>
 */
final class SearchHistoryFactory extends Factory
{
    protected $model = SearchHistory::class;

    public function definition(): array
    {
        return [
            'team_id' => Team::factory(),
            'user_id' => User::factory(),
            'query' => $this->faker->words(3, true),
            'module' => $this->faker->optional()->randomElement(['companies', 'people', 'opportunities', 'tasks', 'support_cases']),
            'filters' => $this->faker->optional()->randomElement([
                null,
                [
                    ['field' => 'status', 'operator' => 'equals', 'value' => 'active'],
                ],
                [
                    ['field' => 'created_at', 'operator' => 'greater_than', 'value' => '2024-01-01'],
                    ['field' => 'name', 'operator' => 'contains', 'value' => 'test'],
                ],
            ]),
            'results_count' => $this->faker->numberBetween(0, 100),
            'execution_time' => $this->faker->randomFloat(4, 0.001, 5.0),
            'searched_at' => $this->faker->dateTimeBetween('-30 days', 'now'),
        ];
    }

    public function withNoResults(): static
    {
        return $this->state(fn (array $attributes): array => [
            'results_count' => 0,
        ]);
    }

    public function slowQuery(): static
    {
        return $this->state(fn (array $attributes): array => [
            'execution_time' => $this->faker->randomFloat(4, 2.0, 10.0),
        ]);
    }
}
