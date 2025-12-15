<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Territory;
use App\Models\TerritoryQuota;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TerritoryQuota>
 */
final class TerritoryQuotaFactory extends Factory
{
    protected $model = TerritoryQuota::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $year = (int) fake()->numberBetween((int) date('Y') - 2, (int) date('Y'));
        $quarter = (int) fake()->numberBetween(1, 4);

        return [
            'territory_id' => Territory::factory(),
            'period' => sprintf('%d-Q%d', $year, $quarter),
            'revenue_target' => fake()->optional()->randomFloat(2, 10000, 250000),
            'unit_target' => fake()->optional()->numberBetween(10, 1000),
            'revenue_actual' => 0,
            'unit_actual' => 0,
        ];
    }
}

