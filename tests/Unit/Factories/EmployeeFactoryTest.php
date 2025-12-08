<?php

declare(strict_types=1);

use App\Enums\EmployeeStatus;
use App\Models\Employee;
use App\Models\Team;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('employee factory creates valid employee with all required fields', function (): void {
    $employee = Employee::factory()->create();

    expect($employee)->toBeInstanceOf(Employee::class)
        ->and($employee->first_name)->not->toBeNull()
        ->and($employee->last_name)->not->toBeNull()
        ->and($employee->email)->not->toBeNull()
        ->and($employee->team_id)->not->toBeNull()
        ->and($employee->employee_number)->not->toBeNull();
});

test('employee factory generates valid address with postal code', function (): void {
    $employee = Employee::factory()->create();

    expect($employee->postal_code)->not->toBeNull()
        ->and($employee->postal_code)->toBeString()
        ->and($employee->address)->not->toBeNull()
        ->and($employee->city)->not->toBeNull()
        ->and($employee->state)->not->toBeNull()
        ->and($employee->country)->not->toBeNull();
});

test('employee factory generates valid postal code format', function (): void {
    $employee = Employee::factory()->create();

    // Postal code should be a 5-digit string
    expect($employee->postal_code)->toBeString()
        ->and(strlen($employee->postal_code))->toBe(5)
        ->and((int) $employee->postal_code)->toBeGreaterThanOrEqual(10000)
        ->and((int) $employee->postal_code)->toBeLessThanOrEqual(99999);
});

test('employee factory creates associated team', function (): void {
    $employee = Employee::factory()->create();

    expect($employee->team)->toBeInstanceOf(Team::class);
});

test('employee factory generates valid status from enum', function (): void {
    $employee = Employee::factory()->create();

    // Status may be cast to enum depending on model casts
    $statusValue = $employee->status instanceof EmployeeStatus
        ? $employee->status->value
        : $employee->status;

    $validStatuses = array_column(EmployeeStatus::cases(), 'value');

    expect($validStatuses)->toContain($statusValue);
});

test('employee factory can create active employee', function (): void {
    $employee = Employee::factory()->active()->create();

    // Status may be cast to enum depending on model casts
    $statusValue = $employee->status instanceof EmployeeStatus
        ? $employee->status->value
        : $employee->status;

    expect($statusValue)->toBe(EmployeeStatus::ACTIVE->value)
        ->and($employee->end_date)->toBeNull();
});

test('employee factory can create inactive employee', function (): void {
    $employee = Employee::factory()->inactive()->create();

    // Status may be cast to enum depending on model casts
    $statusValue = $employee->status instanceof EmployeeStatus
        ? $employee->status->value
        : $employee->status;

    expect($statusValue)->toBe(EmployeeStatus::INACTIVE->value);
});

test('employee factory can create employee on leave', function (): void {
    $employee = Employee::factory()->onLeave()->create();

    // Status may be cast to enum depending on model casts
    $statusValue = $employee->status instanceof EmployeeStatus
        ? $employee->status->value
        : $employee->status;

    expect($statusValue)->toBe(EmployeeStatus::ON_LEAVE->value);
});

test('employee factory can create terminated employee', function (): void {
    $employee = Employee::factory()->terminated()->create();

    // Status may be cast to enum depending on model casts
    $statusValue = $employee->status instanceof EmployeeStatus
        ? $employee->status->value
        : $employee->status;

    expect($statusValue)->toBe(EmployeeStatus::TERMINATED->value)
        ->and($employee->end_date)->not->toBeNull();
});

test('employee factory can override default values', function (): void {
    $customFirstName = 'John';
    $customLastName = 'Doe';
    $customEmail = 'john.doe@example.com';

    $employee = Employee::factory()->create([
        'first_name' => $customFirstName,
        'last_name' => $customLastName,
        'email' => $customEmail,
    ]);

    expect($employee->first_name)->toBe($customFirstName)
        ->and($employee->last_name)->toBe($customLastName)
        ->and($employee->email)->toBe($customEmail);
});

test('employee factory generates valid email address', function (): void {
    $employee = Employee::factory()->create();

    expect($employee->email)->toBeString()
        ->and(filter_var($employee->email, FILTER_VALIDATE_EMAIL))->not->toBeFalse();
});

test('employee factory generates valid employee number format', function (): void {
    $employee = Employee::factory()->create();

    expect($employee->employee_number)->toBeString()
        ->and($employee->employee_number)->toStartWith('EMP-');
});

test('employee factory generates valid department', function (): void {
    $employee = Employee::factory()->create();
    $validDepartments = [
        'Engineering',
        'Sales',
        'Marketing',
        'Human Resources',
        'Finance',
        'Operations',
        'Customer Support',
    ];

    expect($validDepartments)->toContain($employee->department);
});

test('employee factory generates valid skills array', function (): void {
    $employee = Employee::factory()->create();

    expect($employee->skills)->toBeArray()
        ->and($employee->skills)->not->toBeEmpty();
});

test('employee factory generates valid vacation days', function (): void {
    $employee = Employee::factory()->create();

    expect((int) $employee->vacation_days_total)->toBe(20)
        ->and((float) $employee->vacation_days_used)->toBeGreaterThanOrEqual(0)
        ->and((float) $employee->vacation_days_used)->toBeLessThanOrEqual(15);
});

test('employee factory generates valid sick days', function (): void {
    $employee = Employee::factory()->create();

    expect((int) $employee->sick_days_total)->toBe(10)
        ->and((float) $employee->sick_days_used)->toBeGreaterThanOrEqual(0)
        ->and((float) $employee->sick_days_used)->toBeLessThanOrEqual(5);
});

test('employee factory can create employee with manager', function (): void {
    $manager = Employee::factory()->create();
    $employee = Employee::factory()->withManager($manager)->create();

    expect($employee->manager_id)->toBe($manager->id);
});

test('employee factory can create multiple employees', function (): void {
    $employees = Employee::factory()->count(5)->create();

    expect($employees)->toHaveCount(5)
        ->and(Employee::count())->toBe(5);
});

test('employee factory generates valid emergency contact', function (): void {
    $employee = Employee::factory()->create();

    expect($employee->emergency_contact_name)->toBeString()
        ->and($employee->emergency_contact_phone)->toBeString()
        ->and($employee->emergency_contact_relationship)->toBeString();
});
