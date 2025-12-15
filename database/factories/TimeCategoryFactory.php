<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\CreationSource;
use App\Models\Team;
use App\Models\TimeCategory;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TimeCategory>
 */
final class TimeCategoryFactory extends Factory
{
    protected $model = TimeCategory::class;

    public function definition(): array
    {
        return [
            'team_id' => Team::factory(),
            'creator_id' => User::factory(),
            'name' => $this->faker->words(2, true),
            'code' => strtoupper($this->faker->unique()->lexify('CAT-????')),
            'description' => $this->faker->boolean(60) ? $this->faker->sentence() : null,
            'color' => $this->faker->boolean(50) ? $this->faker->hexColor() : null,
            'icon' => $this->faker->boolean(30) ? 'heroicon-o-clock' : null,
            'is_billable_default' => $this->faker->boolean(50),
            'default_billing_rate' => $this->faker->boolean(60) ? $this->faker->randomFloat(2, 25, 250) : null,
            'is_active' => true,
            'sort_order' => 0,
            'creation_source' => CreationSource::WEB->value,
        ];
    }
}
