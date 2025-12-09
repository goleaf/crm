<?php

declare(strict_types=1);

namespace Tests\Unit\Properties\ProjectsResources;

use App\Models\Employee;
use App\Models\Project;
use Tests\Support\Generators\EmployeeGenerator;
use Tests\Support\Generators\ProjectGenerator;
use Tests\Support\PropertyTestCase;

/**
 * Feature: projects-resources, Property 3: Resource allocation
 * Validates: Requirements 3.3
 *
 * Property: Employee allocation across tasks cannot exceed capacity thresholds;
 * over-allocation flagged.
 */
final class ResourceAllocationPropertyTest extends PropertyTestCase
{
    /**
     * @test
     */
    public function employee_allocation_cannot_exceed_100_percent(): void
    {
        $this->runPropertyTest(function (): void {
            $employee = EmployeeGenerator::generateActive($this->team);
            $project = ProjectGenerator::generate($this->team, $this->user);

            // Allocate employee to project at random percentage
            $allocation1 = fake()->numberBetween(20, 80);
            $employee->allocateTo($project, $allocation1);

            // Property: Attempting to allocate more than remaining capacity should throw exception
            $remainingCapacity = 100 - $allocation1;
            $overAllocation = $remainingCapacity + fake()->numberBetween(1, 20);

            $this->expectException(\DomainException::class);
            $this->expectExceptionMessage('would exceed capacity');

            $employee->allocateTo($project, $overAllocation);
        }, 100);
    }

    /**
     * @test
     */
    public function employee_total_allocation_is_sum_of_all_allocations(): void
    {
        $this->runPropertyTest(function (): void {
            $employee = EmployeeGenerator::generateActive($this->team);

            // Create multiple projects and allocate employee
            $allocations = [];
            $totalAllocated = 0;
            $allocationCount = fake()->numberBetween(2, 5);

            for ($i = 0; $i < $allocationCount; $i++) {
                $project = ProjectGenerator::generate($this->team, $this->user);
                $percentage = fake()->numberBetween(5, 20);

                if ($totalAllocated + $percentage <= 100) {
                    $employee->allocateTo($project, $percentage);
                    $allocations[] = $percentage;
                    $totalAllocated += $percentage;
                }
            }

            // Property: Total allocation should equal sum of individual allocations
            $calculatedTotal = $employee->getTotalAllocation();

            $this->assertEquals(
                $totalAllocated,
                $calculatedTotal,
                'Total allocation should equal sum of all allocations',
            );
        }, 100);
    }

    /**
     * @test
     */
    public function employee_is_over_allocated_when_exceeding_100_percent(): void
    {
        $this->runPropertyTest(function (): void {
            $employee = EmployeeGenerator::generateActive($this->team);

            // Manually create allocations that exceed 100% (bypassing validation)
            $project1 = ProjectGenerator::generate($this->team, $this->user);
            $project2 = ProjectGenerator::generate($this->team, $this->user);

            $employee->allocations()->create([
                'allocatable_type' => Project::class,
                'allocatable_id' => $project1->id,
                'allocation_percentage' => 60,
            ]);

            $employee->allocations()->create([
                'allocatable_type' => Project::class,
                'allocatable_id' => $project2->id,
                'allocation_percentage' => 50,
            ]);

            // Property: Employee should be flagged as over-allocated
            $this->assertTrue(
                $employee->isOverAllocated(),
                'Employee with >100% allocation should be flagged as over-allocated',
            );

            // Property: Total allocation should be 110%
            $this->assertEquals(110, $employee->getTotalAllocation());
        }, 100);
    }

    /**
     * @test
     */
    public function employee_available_capacity_is_100_minus_allocated(): void
    {
        $this->runPropertyTest(function (): void {
            $employee = EmployeeGenerator::generateActive($this->team);

            // Allocate random percentage
            $allocated = fake()->numberBetween(10, 90);
            $project = ProjectGenerator::generate($this->team, $this->user);
            $employee->allocateTo($project, $allocated);

            // Property: Available capacity should be 100 - allocated
            $availableCapacity = $employee->getAvailableCapacity();
            $expectedCapacity = 100 - $allocated;

            $this->assertEquals(
                $expectedCapacity,
                $availableCapacity,
                'Available capacity should equal 100 minus allocated percentage',
            );
        }, 100);
    }

    /**
     * @test
     */
    public function allocation_respects_date_ranges(): void
    {
        $this->runPropertyTest(function (): void {
            $employee = EmployeeGenerator::generateActive($this->team);
            $project = ProjectGenerator::generate($this->team, $this->user);

            // Create allocation for specific date range
            $startDate = \Illuminate\Support\Facades\Date::today()->addDays(10);
            $endDate = \Illuminate\Support\Facades\Date::today()->addDays(30);

            $employee->allocateTo($project, 50, $startDate, $endDate);

            // Property: Allocation within date range should be counted
            $allocationInRange = $employee->getTotalAllocation($startDate, $endDate);
            $this->assertEquals(50, $allocationInRange);

            // Property: Allocation outside date range should not be counted
            $beforeStart = \Illuminate\Support\Facades\Date::today();
            $beforeEnd = \Illuminate\Support\Facades\Date::today()->addDays(5);
            $allocationBefore = $employee->getTotalAllocation($beforeStart, $beforeEnd);
            $this->assertEquals(0, $allocationBefore);

            // Property: Allocation after date range should not be counted
            $afterStart = \Illuminate\Support\Facades\Date::today()->addDays(40);
            $afterEnd = \Illuminate\Support\Facades\Date::today()->addDays(50);
            $allocationAfter = $employee->getTotalAllocation($afterStart, $afterEnd);
            $this->assertEquals(0, $allocationAfter);
        }, 100);
    }
}
