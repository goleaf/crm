<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\SearchIndex;
use App\Models\Team;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SearchIndex>
 */
final class SearchIndexFactory extends Factory
{
    protected $model = SearchIndex::class;

    public function definition(): array
    {
        return [
            'team_id' => Team::factory(),
            'term' => $this->faker->word(),
            'module' => $this->faker->randomElement(['companies', 'people', 'opportunities', 'tasks', 'support_cases']),
            'searchable_type' => $this->faker->randomElement([
                \App\Models\Company::class,
                \App\Models\People::class,
                \App\Models\Opportunity::class,
                \App\Models\Task::class,
                \App\Models\SupportCase::class,
            ]),
            'searchable_id' => $this->faker->numberBetween(1, 1000),
            'metadata' => [
                'created_at' => $this->faker->dateTime()->format('c'),
                'updated_at' => $this->faker->dateTime()->format('c'),
            ],
            'ranking_score' => $this->faker->randomFloat(2, 0, 10),
            'search_count' => $this->faker->numberBetween(0, 100),
            'last_searched_at' => $this->faker->optional()->dateTime(),
        ];
    }

    public function forModule(string $module): static
    {
        return $this->state(fn (array $attributes): array => [
            'module' => $module,
        ]);
    }

    public function withHighRanking(): static
    {
        return $this->state(fn (array $attributes): array => [
            'ranking_score' => $this->faker->randomFloat(2, 7, 10),
            'search_count' => $this->faker->numberBetween(50, 200),
        ]);
    }
}
