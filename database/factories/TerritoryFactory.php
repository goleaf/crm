<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\TerritoryType;
use App\Models\Team;
use App\Models\Territory;
use Illuminate\Database\Eloquent\Factories\Factory;

final class TerritoryFactory extends Factory
{
    protected $model = Territory::class;

    public function definition(): array
    {
        return [
            'team_id' => Team::factory(),
            'name' => $this->faker->city().' Territory',
            'code' => strtoupper($this->faker->unique()->lexify('???-###')),
            'type' => $this->faker->randomElement(TerritoryType::cases()),
            'description' => $this->faker->sentence(),
            'parent_id' => null,
            'level' => 0,
            'path' => null,
            'assignment_rules' => null,
            'revenue_quota' => $this->faker->randomFloat(2, 100000, 1000000),
            'unit_quota' => $this->faker->numberBetween(10, 100),
            'quota_period' => $this->faker->randomElement(['monthly', 'quarterly', 'yearly']),
            'is_active' => true,
        ];
    }

    public function withAssignmentRules(): static
    {
        return $this->state(fn (array $attributes): array => [
            'assignment_rules' => [
                [
                    'field' => 'state',
                    'operator' => '=',
                    'value' => $this->faker->state(),
                ],
            ],
        ]);
    }

    public function geographic(): static
    {
        return $this->state(fn (array $attributes): array => [
            'type' => TerritoryType::GEOGRAPHIC,
        ]);
    }

    public function product(): static
    {
        return $this->state(fn (array $attributes): array => [
            'type' => TerritoryType::PRODUCT,
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes): array => [
            'is_active' => false,
        ]);
    }

    public function withParent(Territory $parent): static
    {
        return $this->state(fn (array $attributes): array => [
            'parent_id' => $parent->id,
            'level' => $parent->level + 1,
            'path' => $parent->path.'/'.($attributes['id'] ?? 'new'),
        ]);
    }
}
