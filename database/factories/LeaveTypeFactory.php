<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\LeaveAccrualFrequency;
use App\Models\LeaveType;
use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LeaveType>
 */
final class LeaveTypeFactory extends Factory
{
    protected $model = LeaveType::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $shouldAccrue = $this->faker->boolean(60);

        return [
            'team_id' => Team::factory(),
            'creator_id' => User::factory(),
            'name' => $this->faker->words(2, true),
            'code' => strtoupper($this->faker->unique()->lexify('LEAVE_????')),
            'description' => $this->faker->boolean(60) ? $this->faker->sentence() : null,
            'color' => $this->faker->boolean(50) ? $this->faker->hexColor() : null,
            'icon' => null,
            'is_paid' => $this->faker->boolean(80),
            'requires_approval' => $this->faker->boolean(80),
            'max_days_per_year' => $this->faker->boolean(60) ? $this->faker->numberBetween(5, 30) : null,
            'accrual_rate' => $shouldAccrue ? $this->faker->randomFloat(2, 0.25, 3) : null,
            'accrual_frequency' => $shouldAccrue ? $this->faker->randomElement(LeaveAccrualFrequency::cases()) : null,
            'allow_carryover' => $this->faker->boolean(30),
            'max_carryover_days' => $this->faker->boolean(30) ? $this->faker->numberBetween(1, 15) : null,
            'is_active' => true,
            'sort_order' => 0,
        ];
    }
}

