<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\SavedSearch;
use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\Sequence;

/**
 * @extends Factory<SavedSearch>
 */
final class SavedSearchFactory extends Factory
{
    protected $model = SavedSearch::class;

    public function definition(): array
    {
        return [
            'team_id' => Team::factory(),
            'user_id' => User::factory(),
            'name' => $this->faker->words(2, true),
            'resource' => 'global',
            'query' => $this->faker->word(),
            'filters' => [
                [
                    'field' => 'industry',
                    'operator' => '=',
                    'value' => 'Software',
                ],
            ],
        ];
    }

    public function configure(): Factory
    {
        return $this->sequence(fn (Sequence $sequence): array => [
            'created_at' => now()->subMinutes($sequence->index),
            'updated_at' => now()->subMinutes($sequence->index),
        ]);
    }
}
