<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Employee;
use App\Models\EmployeeManagerAssignment;
use App\Models\Team;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EmployeeManagerAssignment>
 */
final class EmployeeManagerAssignmentFactory extends Factory
{
    protected $model = EmployeeManagerAssignment::class;

    public function definition(): array
    {
        $effectiveFrom = $this->faker->dateTimeBetween('-6 months', 'now');

        return [
            'team_id' => Team::factory(),
            'employee_id' => Employee::factory(),
            'manager_id' => Employee::factory(),
            'effective_from' => $effectiveFrom,
            'effective_to' => null,
        ];
    }

    public function configure(): static
    {
        return $this->afterMaking(function (EmployeeManagerAssignment $assignment): void {
            if ($assignment->employee_id === null) {
                return;
            }

            $employee = Employee::find($assignment->employee_id);
            if (! $employee instanceof Employee) {
                return;
            }

            $assignment->team_id = $employee->team_id;

            if ($assignment->manager_id === null) {
                return;
            }

            $manager = Employee::find($assignment->manager_id);
            if ($manager instanceof Employee && $manager->team_id !== $employee->team_id) {
                $assignment->manager_id = Employee::factory()->create(['team_id' => $employee->team_id])->id;
            }
        });
    }
}

