<?php

declare(strict_types=1);

namespace Tests\Support\Generators;

use App\Enums\EmployeeStatus;
use App\Models\Employee;
use App\Models\Team;

/**
 * Generator for creating random Employee instances for property-based testing.
 */
final class EmployeeGenerator
{
    /**
     * Generate a random employee.
     *
     * @param  array<string, mixed>  $overrides
     */
    public static function generate(
        Team $team,
        array $overrides = []
    ): Employee {
        $startDate = fake()->optional(0.9)->dateTimeBetween('-5 years', 'now');

        return Employee::factory()->create(array_merge([
            'team_id' => $team->id,
            'first_name' => fake()->firstName(),
            'last_name' => fake()->lastName(),
            'email' => fake()->unique()->safeEmail(),
            'phone' => fake()->optional(0.7)->phoneNumber(),
            'employee_number' => fake()->optional(0.8)->numerify('EMP-####'),
            'department' => fake()->optional(0.8)->randomElement(['Engineering', 'Sales', 'Marketing', 'HR', 'Finance']),
            'role' => fake()->optional(0.8)->jobTitle(),
            'status' => fake()->randomElement(EmployeeStatus::cases()),
            'start_date' => $startDate,
            'capacity_hours_per_week' => fake()->randomElement([20, 30, 40]),
            'vacation_days_total' => fake()->numberBetween(10, 30),
            'sick_days_total' => fake()->numberBetween(5, 15),
        ], $overrides));
    }

    /**
     * Generate an active employee.
     *
     * @param  array<string, mixed>  $overrides
     */
    public static function generateActive(
        Team $team,
        array $overrides = []
    ): Employee {
        return self::generate($team, array_merge([
            'status' => EmployeeStatus::ACTIVE,
        ], $overrides));
    }
}
