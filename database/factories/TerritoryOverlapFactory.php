<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\TerritoryOverlapResolution;
use App\Models\Territory;
use App\Models\TerritoryOverlap;
use Illuminate\Database\Eloquent\Factories\Factory;

final class TerritoryOverlapFactory extends Factory
{
    protected $model = TerritoryOverlap::class;

    public function definition(): array
    {
        return [
            'territory_a_id' => Territory::factory(),
            'territory_b_id' => Territory::factory(),
            'resolution_strategy' => $this->faker->randomElement(TerritoryOverlapResolution::cases()),
            'priority_territory_id' => null,
            'notes' => $this->faker->sentence(),
        ];
    }

    public function withPriority(): static
    {
        return $this->state(fn (array $attributes): array => [
            'resolution_strategy' => TerritoryOverlapResolution::PRIORITY,
            'priority_territory_id' => $attributes['territory_a_id'],
        ]);
    }
}
