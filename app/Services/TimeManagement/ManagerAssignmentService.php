<?php

declare(strict_types=1);

namespace App\Services\TimeManagement;

use App\Models\Employee;
use App\Models\EmployeeManagerAssignment;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final readonly class ManagerAssignmentService
{
    /**
     * Assign a manager to an employee with an effective date, preserving history.
     *
     * @throws ValidationException
     */
    public function assign(Employee $employee, Employee $manager, Carbon $effectiveFrom): EmployeeManagerAssignment
    {
        if ($employee->team_id !== $manager->team_id) {
            throw ValidationException::withMessages([
                'manager_id' => 'Manager must belong to the same team as the employee.',
            ]);
        }

        $effectiveFrom = $effectiveFrom->copy()->startOfDay();

        return DB::transaction(function () use ($employee, $manager, $effectiveFrom): EmployeeManagerAssignment {
            $current = EmployeeManagerAssignment::query()
                ->where('employee_id', $employee->getKey())
                ->whereNull('effective_to')
                ->latest('effective_from')
                ->first();

            if ($current instanceof EmployeeManagerAssignment) {
                if ($effectiveFrom->lessThan($current->effective_from)) {
                    throw ValidationException::withMessages([
                        'effective_from' => 'Effective date must be on or after the current assignment start date.',
                    ]);
                }

                if ($effectiveFrom->equalTo($current->effective_from)) {
                    $current->manager_id = $manager->getKey();
                    $current->save();

                    $this->syncEmployeeManagerId($employee, $manager, $effectiveFrom);

                    return $current;
                }

                $current->effective_to = $effectiveFrom->copy()->subDay();
                $current->save();
            }

            $assignment = EmployeeManagerAssignment::create([
                'team_id' => $employee->team_id,
                'employee_id' => $employee->getKey(),
                'manager_id' => $manager->getKey(),
                'effective_from' => $effectiveFrom->toDateString(),
                'effective_to' => null,
            ]);

            $this->syncEmployeeManagerId($employee, $manager, $effectiveFrom);

            return $assignment;
        });
    }

    /**
     * @param array<int> $employeeIds
     */
    public function assignMany(Employee $manager, array $employeeIds, Carbon $effectiveFrom): void
    {
        DB::transaction(function () use ($manager, $employeeIds, $effectiveFrom): void {
            foreach ($employeeIds as $employeeId) {
                $employee = Employee::query()->findOrFail($employeeId);
                $this->assign($employee, $manager, $effectiveFrom);
            }
        });
    }

    public function getManagerForDate(Employee $employee, Carbon $date): ?Employee
    {
        $date = $date->copy()->startOfDay();

        $assignment = EmployeeManagerAssignment::query()
            ->where('employee_id', $employee->getKey())
            ->whereDate('effective_from', '<=', $date->toDateString())
            ->where(function ($query) use ($date): void {
                $query
                    ->whereNull('effective_to')
                    ->orWhereDate('effective_to', '>=', $date->toDateString());
            })
            ->latest('effective_from')
            ->first();

        if (! $assignment instanceof EmployeeManagerAssignment) {
            return $employee->manager;
        }

        return $assignment->manager;
    }

    private function syncEmployeeManagerId(Employee $employee, Employee $manager, Carbon $effectiveFrom): void
    {
        if ($effectiveFrom->greaterThan(now()->startOfDay())) {
            return;
        }

        if ($employee->manager_id === $manager->getKey()) {
            return;
        }

        $employee->manager_id = $manager->getKey();
        $employee->save();
    }
}
