<?php

declare(strict_types=1);

namespace App\Services\TimeManagement;

use App\Models\Absence;
use App\Models\Employee;
use App\Models\LeaveBalance;
use App\Models\LeaveType;
use Illuminate\Support\Facades\DB;

final readonly class LeaveBalanceService
{
    public function getBalance(Employee $employee, LeaveType $leaveType, int $year): LeaveBalance
    {
        $balance = LeaveBalance::query()->firstOrCreate(
            [
                'team_id' => $employee->team_id,
                'employee_id' => $employee->getKey(),
                'leave_type_id' => $leaveType->getKey(),
                'year' => $year,
            ],
            [
                'allocated_days' => 0,
                'used_days' => 0,
                'pending_days' => 0,
                'available_days' => 0,
                'carried_over_days' => 0,
            ],
        );

        $balance->recalculate();
        $balance->save();

        return $balance;
    }

    public function initializeBalances(Employee $employee, int $year): void
    {
        $leaveTypes = LeaveType::query()
            ->where('team_id', $employee->team_id)
            ->where('is_active', true)
            ->get();

        foreach ($leaveTypes as $leaveType) {
            $balance = $this->getBalance($employee, $leaveType, $year);

            if ((float) $balance->allocated_days === 0.0 && $leaveType->max_days_per_year !== null) {
                $balance->allocated_days = (float) $leaveType->max_days_per_year;
                $balance->recalculate();
                $balance->save();
            }
        }
    }

    public function accrueLeave(Employee $employee, LeaveType $leaveType): void
    {
        if ($leaveType->accrual_rate === null || $leaveType->accrual_frequency === null) {
            return;
        }

        $year = (int) now()->format('Y');
        $balance = $this->getBalance($employee, $leaveType, $year);

        $balance->allocated_days = (float) $balance->allocated_days + (float) $leaveType->accrual_rate;
        $balance->recalculate();
        $balance->save();
    }

    public function reserveLeave(Absence $absence): void
    {
        DB::transaction(function () use ($absence): void {
            $absence->loadMissing(['employee', 'leaveType']);

            $employee = $absence->employee;
            $leaveType = $absence->leaveType;

            $balance = $this->getBalance($employee, $leaveType, (int) now()->format('Y'));

            $balance->pending_days = round((float) $balance->pending_days + (float) $absence->duration_days, 2);
            $balance->recalculate();
            $balance->save();
        });
    }

    public function commitLeave(Absence $absence): void
    {
        DB::transaction(function () use ($absence): void {
            $absence->loadMissing(['employee', 'leaveType']);

            $employee = $absence->employee;
            $leaveType = $absence->leaveType;

            $balance = $this->getBalance($employee, $leaveType, (int) now()->format('Y'));

            $balance->pending_days = round((float) $balance->pending_days - (float) $absence->duration_days, 2);
            $balance->used_days = round((float) $balance->used_days + (float) $absence->duration_days, 2);
            $balance->recalculate();
            $balance->save();
        });
    }

    public function deductUsed(Absence $absence): void
    {
        DB::transaction(function () use ($absence): void {
            $absence->loadMissing(['employee', 'leaveType']);

            $employee = $absence->employee;
            $leaveType = $absence->leaveType;

            $balance = $this->getBalance($employee, $leaveType, (int) now()->format('Y'));

            $balance->used_days = round((float) $balance->used_days + (float) $absence->duration_days, 2);
            $balance->recalculate();
            $balance->save();
        });
    }

    public function releasePending(Absence $absence): void
    {
        DB::transaction(function () use ($absence): void {
            $absence->loadMissing(['employee', 'leaveType']);

            $employee = $absence->employee;
            $leaveType = $absence->leaveType;

            $balance = $this->getBalance($employee, $leaveType, (int) now()->format('Y'));

            $balance->pending_days = round((float) $balance->pending_days - (float) $absence->duration_days, 2);
            $balance->recalculate();
            $balance->save();
        });
    }

    public function restoreUsed(Absence $absence): void
    {
        DB::transaction(function () use ($absence): void {
            $absence->loadMissing(['employee', 'leaveType']);

            $employee = $absence->employee;
            $leaveType = $absence->leaveType;

            $balance = $this->getBalance($employee, $leaveType, (int) now()->format('Y'));

            $balance->used_days = round((float) $balance->used_days - (float) $absence->duration_days, 2);
            $balance->recalculate();
            $balance->save();
        });
    }
}
