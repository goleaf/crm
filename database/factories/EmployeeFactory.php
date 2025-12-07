<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\EmployeeStatus;
use App\Models\Employee;
use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Employee>
 */
final class EmployeeFactory extends Factory
{
    protected $model = Employee::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'team_id' => Team::factory(),
            'user_id' => $this->faker->boolean(70) ? User::factory() : null,
            'manager_id' => null,
            'first_name' => $this->faker->firstName(),
            'last_name' => $this->faker->lastName(),
            'email' => $this->faker->unique()->safeEmail(),
            'phone' => $this->faker->phoneNumber(),
            'mobile' => $this->faker->phoneNumber(),
            'employee_number' => 'EMP-'.$this->faker->unique()->numberBetween(1000, 9999),
            'department' => $this->faker->randomElement([
                'Engineering',
                'Sales',
                'Marketing',
                'Human Resources',
                'Finance',
                'Operations',
                'Customer Support',
            ]),
            'role' => $this->faker->randomElement([
                'Software Engineer',
                'Senior Engineer',
                'Team Lead',
                'Manager',
                'Director',
                'Sales Representative',
                'Account Manager',
                'Marketing Specialist',
            ]),
            'title' => $this->faker->jobTitle(),
            'status' => $this->faker->randomElement(EmployeeStatus::cases())->value,
            'start_date' => $this->faker->dateTimeBetween('-5 years', '-1 month'),
            'end_date' => null,
            'address' => $this->faker->streetAddress(),
            'city' => $this->faker->city(),
            'state' => $this->faker->state(),
            'postal_code' => $this->faker->postcode(),
            'country' => $this->faker->country(),
            'emergency_contact_name' => $this->faker->name(),
            'emergency_contact_phone' => $this->faker->phoneNumber(),
            'emergency_contact_relationship' => $this->faker->randomElement([
                'Spouse',
                'Parent',
                'Sibling',
                'Friend',
            ]),
            'skills' => $this->faker->randomElements([
                'PHP',
                'Laravel',
                'JavaScript',
                'React',
                'Vue.js',
                'Python',
                'Project Management',
                'Sales',
                'Marketing',
                'Customer Service',
            ], $this->faker->numberBetween(2, 5)),
            'certifications' => $this->faker->boolean(30) ? [
                [
                    'name' => 'PMP Certification',
                    'issuer' => 'PMI',
                    'date' => $this->faker->date(),
                ],
            ] : null,
            'performance_notes' => $this->faker->boolean(50) ? $this->faker->paragraph() : null,
            'performance_rating' => $this->faker->boolean(60) ? $this->faker->randomFloat(2, 1, 5) : null,
            'vacation_days_total' => 20,
            'vacation_days_used' => $this->faker->numberBetween(0, 15),
            'sick_days_total' => 10,
            'sick_days_used' => $this->faker->numberBetween(0, 5),
            'has_portal_access' => $this->faker->boolean(80),
            'payroll_id' => $this->faker->boolean(70) ? 'PAY-'.$this->faker->unique()->numberBetween(1000, 9999) : null,
            'payroll_metadata' => null,
            'capacity_hours_per_week' => 40,
        ];
    }

    /**
     * Indicate that the employee is active.
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => EmployeeStatus::ACTIVE->value,
            'end_date' => null,
        ]);
    }

    /**
     * Indicate that the employee is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => EmployeeStatus::INACTIVE->value,
        ]);
    }

    /**
     * Indicate that the employee is on leave.
     */
    public function onLeave(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => EmployeeStatus::ON_LEAVE->value,
        ]);
    }

    /**
     * Indicate that the employee is terminated.
     */
    public function terminated(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => EmployeeStatus::TERMINATED->value,
            'end_date' => $this->faker->dateTimeBetween('-1 year', 'now'),
        ]);
    }

    /**
     * Indicate that the employee has a manager.
     */
    public function withManager(?Employee $manager = null): static
    {
        return $this->state(fn (array $attributes): array => [
            'manager_id' => $manager?->id ?? Employee::factory(),
        ]);
    }
}
