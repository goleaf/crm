<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Lead;
use App\Models\Territory;
use App\Models\TerritoryRecord;
use Illuminate\Database\Eloquent\Factories\Factory;

final class TerritoryRecordFactory extends Factory
{
    protected $model = TerritoryRecord::class;

    public function definition(): array
    {
        return [
            'territory_id' => Territory::factory(),
            'record_type' => Lead::class,
            'record_id' => fn () => Lead::factory()->create()->id,
            'is_primary' => true,
            'assigned_at' => now(),
            'assignment_reason' => $this->faker->sentence(),
        ];
    }

    public function secondary(): static
    {
        return $this->state(fn (array $attributes): array => [
            'is_primary' => false,
        ]);
    }
}
