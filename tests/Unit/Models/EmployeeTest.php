<?php

declare(strict_types=1);

use App\Enums\EmployeeStatus;
use App\Models\Employee;
use App\Models\Project;
use App\Models\Team;

test('employee can be created with basic information', function (): void {
    $team = Team::factory()->create();

    $employee = Employee::factory()
        ->for($team)
        ->create([
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'john.doe@example.com',
            'status' => EmployeeStatus::ACTIVE,
        ]);

    expect($employee->full_name)->toBe('John Doe')
        ->and($employee->name?->full())->toBe('John Doe')
        ->and($employee->email)->toBe('john.doe@example.com')
        ->and($employee->status)->toBe(EmployeeStatus::ACTIVE)
        ->and($employee->isActive())->toBeTrue();
});

test('employee can have a manager', function (): void {
    $team = Team::factory()->create();

    $manager = Employee::factory()->for($team)->create();
    $employee = Employee::factory()->for($team)->create([
        'manager_id' => $manager->id,
    ]);

    expect($employee->manager->id)->toBe($manager->id)
        ->and($manager->directReports)->toHaveCount(1)
        ->and($manager->directReports->first()->id)->toBe($employee->id);
});

test('employee tracks vacation and sick days correctly', function (): void {
    $team = Team::factory()->create();

    $employee = Employee::factory()->for($team)->create([
        'vacation_days_total' => 20,
        'vacation_days_used' => 5,
        'sick_days_total' => 10,
        'sick_days_used' => 2,
    ]);

    expect($employee->remaining_vacation_days)->toBe(15.0)
        ->and($employee->remaining_sick_days)->toBe(8.0);
});

test('employee can be allocated to a project', function (): void {
    $team = Team::factory()->create();

    $employee = Employee::factory()->for($team)->create();
    $project = Project::factory()->for($team)->create();

    $allocation = $employee->allocateTo($project, 50, now(), now()->addDays(30));

    expect($allocation->employee_id)->toBe($employee->id)
        ->and($allocation->allocatable_id)->toBe($project->id)
        ->and($allocation->allocation_percentage)->toBe(50.0)
        ->and($employee->getTotalAllocation(now(), now()->addDays(30)))->toBe(50.0);
});

test('employee cannot be over-allocated', function (): void {
    $team = Team::factory()->create();

    $employee = Employee::factory()->for($team)->create();
    $project1 = Project::factory()->for($team)->create();
    $project2 = Project::factory()->for($team)->create();

    // Allocate 80%
    $employee->allocateTo($project1, 80, now(), now()->addDays(30));

    // Try to allocate another 30% (would exceed 100%)
    expect(fn () => $employee->allocateTo($project2, 30, now(), now()->addDays(30)))
        ->toThrow(\DomainException::class, 'would exceed capacity');
});

test('employee detects over-allocation correctly', function (): void {
    $team = Team::factory()->create();

    $employee = Employee::factory()->for($team)->create();
    $project1 = Project::factory()->for($team)->create();
    $project2 = Project::factory()->for($team)->create();

    expect($employee->isOverAllocated())->toBeFalse();

    $employee->allocateTo($project1, 60, now(), now()->addDays(30));
    expect($employee->isOverAllocated(now(), now()->addDays(30)))->toBeFalse();

    $employee->allocateTo($project2, 40, now(), now()->addDays(30));
    expect($employee->isOverAllocated(now(), now()->addDays(30)))->toBeFalse();

    // Total is exactly 100%, not over-allocated
    expect($employee->getTotalAllocation(now(), now()->addDays(30)))->toBe(100.0);
});

test('employee calculates available capacity correctly', function (): void {
    $team = Team::factory()->create();

    $employee = Employee::factory()->for($team)->create();
    $project = Project::factory()->for($team)->create();

    expect($employee->getAvailableCapacity())->toBe(100.0);

    $employee->allocateTo($project, 30, now(), now()->addDays(30));

    expect($employee->getAvailableCapacity(now(), now()->addDays(30)))->toBe(70.0);
});

test('employee can request time off', function (): void {
    $team = Team::factory()->create();

    $employee = Employee::factory()->for($team)->create();

    $timeOff = $employee->requestTimeOff(
        'vacation',
        now(),
        now()->addDays(5),
        'Family vacation',
    );

    expect($timeOff->employee_id)->toBe($employee->id)
        ->and($timeOff->type->value)->toBe('vacation')
        ->and($timeOff->days)->toBe(6.0) // 5 days + 1 (inclusive)
        ->and($timeOff->status->value)->toBe('pending')
        ->and($timeOff->isPending())->toBeTrue();
});

test('employee time off detection works correctly', function (): void {
    $team = Team::factory()->create();
    $user = User::factory()->create();

    $employee = Employee::factory()->for($team)->create();

    $timeOff = $employee->requestTimeOff(
        'vacation',
        now()->addDays(10),
        now()->addDays(15),
        'Vacation',
    );

    // Not on time off yet (pending)
    expect($employee->isOnTimeOff(now()->addDays(10), now()->addDays(15)))->toBeFalse();

    // Approve the time off
    $timeOff->approve($user);

    // Now should be on time off
    expect($employee->isOnTimeOff(now()->addDays(10), now()->addDays(15)))->toBeTrue()
        ->and($employee->isOnTimeOff(now(), now()->addDays(5)))->toBeFalse();
});