<?php

declare(strict_types=1);

use App\Models\Employee;
use App\Models\Project;
use App\Models\Task;
use App\Models\Team;

/**
 * **Feature: projects-resources, Property 3: Resource allocation**
 * **Validates: Requirements 3.3**
 *
 * For any employee, the total allocation percentage across all projects and tasks
 * cannot exceed 100%. Over-allocation should be flagged and prevented.
 */
test('employee allocation cannot exceed capacity threshold', function (): void {
    $team = Team::factory()->create();

    // Run 100 iterations to test various allocation scenarios
    for ($i = 0; $i < 100; $i++) {
        $employee = Employee::factory()
            ->for($team)
            ->active()
            ->create([
                'capacity_hours_per_week' => 40,
            ]);

        // Generate random number of allocations (1-5)
        $numAllocations = fake()->numberBetween(1, 5);
        $totalAllocation = 0;

        for ($j = 0; $j < $numAllocations; $j++) {
            // Generate random allocation percentage (10-40%)
            $allocationPercentage = fake()->numberBetween(10, 40);

            // Create either a project or task to allocate to
            $allocatable = fake()->boolean()
                ? Project::factory()->for($team)->create()
                : Task::factory()->for($team)->create();

            // Try to allocate
            try {
                $employee->allocateTo(
                    $allocatable,
                    $allocationPercentage,
                    now(),
                    now()->addDays(30)
                );
                $totalAllocation += $allocationPercentage;
            } catch (\DomainException $e) {
                // Expected when over-allocation would occur
                expect($e->getMessage())->toContain('would exceed capacity');
            }
        }

        // Property: Total allocation should never exceed 100%
        $actualAllocation = $employee->getTotalAllocation(now(), now()->addDays(30));
        expect($actualAllocation)
            ->toBeLessThanOrEqual(100)
            ->and($employee->isOverAllocated(now(), now()->addDays(30)))
            ->toBe($actualAllocation > 100);

        // Property: Available capacity should be non-negative
        $availableCapacity = $employee->getAvailableCapacity(now(), now()->addDays(30));
        expect($availableCapacity)->toBeGreaterThanOrEqual(0);

        // Property: Available capacity + total allocation should equal 100
        expect($availableCapacity + $actualAllocation)->toBe(100.0);
    }
})->group('property-test', 'resource-allocation');

/**
 * **Feature: projects-resources, Property 3: Resource allocation**
 * **Validates: Requirements 3.3**
 *
 * For any employee with overlapping allocations, the system should correctly
 * calculate total allocation for a given time period.
 */
test('overlapping allocations are correctly summed', function (): void {
    $team = Team::factory()->create();

    // Run 100 iterations
    for ($i = 0; $i < 100; $i++) {
        $employee = Employee::factory()
            ->for($team)
            ->active()
            ->create();

        // Create multiple allocations with potentially overlapping periods
        $allocations = [];
        $numAllocations = fake()->numberBetween(2, 4);

        for ($j = 0; $j < $numAllocations; $j++) {
            $startDate = now()->addDays(fake()->numberBetween(0, 10));
            $endDate = $startDate->copy()->addDays(fake()->numberBetween(5, 20));
            $percentage = fake()->numberBetween(10, 30);

            $allocatable = fake()->boolean()
                ? Project::factory()->for($team)->create()
                : Task::factory()->for($team)->create();

            try {
                $allocation = $employee->allocateTo(
                    $allocatable,
                    $percentage,
                    $startDate,
                    $endDate
                );
                $allocations[] = [
                    'allocation' => $allocation,
                    'percentage' => $percentage,
                    'start' => $startDate,
                    'end' => $endDate,
                ];
            } catch (\DomainException) {
                // Skip if over-allocated
                continue;
            }
        }

        if ($allocations === []) {
            continue;
        }

        // Pick a random date within the allocation period
        $testDate = now()->addDays(fake()->numberBetween(0, 30));
        $testEndDate = $testDate->copy()->addDays(1);

        // Calculate expected allocation manually
        $expectedAllocation = 0;
        foreach ($allocations as $alloc) {
            if ($alloc['allocation']->overlapsWith($testDate, $testEndDate)) {
                $expectedAllocation += $alloc['percentage'];
            }
        }

        // Property: System-calculated allocation should match manual calculation
        $actualAllocation = $employee->getTotalAllocation($testDate, $testEndDate);
        expect($actualAllocation)->toBe($expectedAllocation);
    }
})->group('property-test', 'resource-allocation');

/**
 * **Feature: projects-resources, Property 3: Resource allocation**
 * **Validates: Requirements 3.3**
 *
 * For any employee, attempting to allocate more than available capacity
 * should throw an exception and not create the allocation.
 */
test('over-allocation attempts are rejected', function (): void {
    $team = Team::factory()->create();

    // Run 100 iterations
    for ($i = 0; $i < 100; $i++) {
        $employee = Employee::factory()
            ->for($team)
            ->active()
            ->create();

        // First, allocate a random percentage (50-90%)
        $firstAllocation = fake()->numberBetween(50, 90);
        $project1 = Project::factory()->for($team)->create();

        $employee->allocateTo($project1, $firstAllocation, now(), now()->addDays(30));

        // Calculate remaining capacity
        $remainingCapacity = 100 - $firstAllocation;

        // Try to allocate more than remaining capacity
        $overAllocation = $remainingCapacity + fake()->numberBetween(1, 50);
        $project2 = Project::factory()->for($team)->create();

        // Property: Over-allocation should throw exception
        expect(fn () => $employee->allocateTo(
            $project2,
            $overAllocation,
            now(),
            now()->addDays(30)
        ))->toThrow(\DomainException::class);

        // Property: Total allocation should remain at first allocation
        $totalAllocation = $employee->getTotalAllocation(now(), now()->addDays(30));
        expect($totalAllocation)->toBe((float) $firstAllocation);

        // Property: Employee should not be over-allocated
        expect($employee->isOverAllocated(now(), now()->addDays(30)))->toBeFalse();
    }
})->group('property-test', 'resource-allocation');

/**
 * **Feature: projects-resources, Property 3: Resource allocation**
 * **Validates: Requirements 3.3**
 *
 * For any employee, allocations outside a given time period should not
 * affect the allocation calculation for that period.
 */
test('allocation calculation respects time boundaries', function (): void {
    $team = Team::factory()->create();

    // Run 100 iterations
    for ($i = 0; $i < 100; $i++) {
        $employee = Employee::factory()
            ->for($team)
            ->active()
            ->create();

        // Create allocation in the past
        $pastProject = Project::factory()->for($team)->create();
        $employee->allocateTo(
            $pastProject,
            50,
            now()->subDays(60),
            now()->subDays(30)
        );

        // Create allocation in the future
        $futureProject = Project::factory()->for($team)->create();
        $employee->allocateTo(
            $futureProject,
            50,
            now()->addDays(60),
            now()->addDays(90)
        );

        // Create allocation in the present
        $presentProject = Project::factory()->for($team)->create();
        $presentAllocation = fake()->numberBetween(20, 40);
        $employee->allocateTo(
            $presentProject,
            $presentAllocation,
            now(),
            now()->addDays(30)
        );

        // Property: Current period allocation should only include present allocation
        $currentAllocation = $employee->getTotalAllocation(now(), now()->addDays(30));
        expect($currentAllocation)->toBe((float) $presentAllocation);

        // Property: Past period should only include past allocation
        $pastAllocation = $employee->getTotalAllocation(
            now()->subDays(60),
            now()->subDays(30)
        );
        expect($pastAllocation)->toBe(50.0);

        // Property: Future period should only include future allocation
        $futureAllocation = $employee->getTotalAllocation(
            now()->addDays(60),
            now()->addDays(90)
        );
        expect($futureAllocation)->toBe(50.0);
    }
})->group('property-test', 'resource-allocation');
