<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Absence;
use App\Models\Employee;
use App\Models\LeaveType;
use App\Models\Team;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Absence>
 */
final class AbsenceFactory extends Factory
{
    protected $model = Absence::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $startDate = $this->faker->dateTimeBetween('-1 month', '+1 month');

        return [
            'team_id' => Team::factory(),
            'employee_id' => fn (array $attributes): int => Employee::factory()->create([
                'team_id' => $attributes['team_id'],
            ])->getKey(),
            'leave_type_id' => fn (array $attributes): int => LeaveType::factory()->create([
                'team_id' => $attributes['team_id'],
            ])->getKey(),
            'start_date' => $startDate,
            'end_date' => (clone $startDate)->modify('+1 day'),
            'reason' => $this->faker->boolean(50) ? $this->faker->sentence() : null,
            'notes' => $this->faker->boolean(30) ? $this->faker->paragraph() : null,
        ];
    }
}
