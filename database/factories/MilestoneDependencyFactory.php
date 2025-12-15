<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\DependencyType;
use App\Models\Milestone;
use App\Models\MilestoneDependency;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MilestoneDependency>
 */
final class MilestoneDependencyFactory extends Factory
{
    protected $model = MilestoneDependency::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $projectMilestones = Milestone::factory()->count(2)->create();
        $predecessor = $projectMilestones->first();
        $successor = $projectMilestones->last();

        return [
            'predecessor_id' => $predecessor?->getKey() ?? Milestone::factory(),
            'successor_id' => $successor?->getKey() ?? Milestone::factory(),
            'dependency_type' => fake()->randomElement(DependencyType::cases()),
            'lag_days' => fake()->numberBetween(0, 5),
            'is_active' => true,
        ];
    }
}

