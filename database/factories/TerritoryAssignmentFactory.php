<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\TerritoryRole;
use App\Models\Territory;
use App\Models\TerritoryAssignment;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

final class TerritoryAssignmentFactory extends Factory
{
    protected $model = TerritoryAssignment::class;

    public function definition(): array
    {
        return [
            'territory_id' => Territory::factory(),
            'user_id' => User::factory(),
            'role' => $this->faker->randomElement(TerritoryRole::cases()),
            'is_primary' => false,
            'start_date' => null,
            'end_date' => null,
        ];
    }

    public function owner(): static
    {
        return $this->state(fn (array $attributes): array => [
            'role' => TerritoryRole::OWNER,
        ]);
    }

    public function member(): static
    {
        return $this->state(fn (array $attributes): array => [
            'role' => TerritoryRole::MEMBER,
        ]);
    }

    public function viewer(): static
    {
        return $this->state(fn (array $attributes): array => [
            'role' => TerritoryRole::VIEWER,
        ]);
    }

    public function primary(): static
    {
        return $this->state(fn (array $attributes): array => [
            'is_primary' => true,
        ]);
    }

    public function withDates(): static
    {
        return $this->state(fn (array $attributes): array => [
            'start_date' => $this->faker->dateTimeBetween('-1 year', 'now'),
            'end_date' => $this->faker->dateTimeBetween('now', '+1 year'),
        ]);
    }
}
