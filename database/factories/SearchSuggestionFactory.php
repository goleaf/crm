<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\SearchSuggestion;
use App\Models\Team;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SearchSuggestion>
 */
final class SearchSuggestionFactory extends Factory
{
    protected $model = SearchSuggestion::class;

    public function definition(): array
    {
        return [
            'team_id' => Team::factory(),
            'term' => $this->faker->word(),
            'module' => $this->faker->optional()->randomElement(['companies', 'people', 'opportunities', 'tasks', 'support_cases']),
            'frequency' => $this->faker->numberBetween(1, 50),
            'relevance_score' => $this->faker->randomFloat(2, 0, 10),
            'metadata' => [
                'source' => 'user_search',
                'category' => $this->faker->optional()->randomElement(['name', 'email', 'phone', 'description']),
            ],
            'is_active' => $this->faker->boolean(90),
        ];
    }

    public function popular(): static
    {
        return $this->state(fn (array $attributes): array => [
            'frequency' => $this->faker->numberBetween(20, 100),
            'relevance_score' => $this->faker->randomFloat(2, 5, 10),
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes): array => [
            'is_active' => false,
        ]);
    }
}
