<?php

declare(strict_types=1);

namespace App\Services\TimeManagement;

use App\Enums\AbsenceStatus;
use App\Models\Absence;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Date;
use Illuminate\Validation\ValidationException;

final readonly class AbsenceService
{
    public function __construct(
        private LeaveBalanceService $balanceService,
        private ValidationService $validator,
    ) {}

    /**
     * @param array<string, mixed> $data
     *
     * @throws ValidationException
     */
    public function createAbsence(array $data): Absence
    {
        $validated = $this->validator->validateAbsence($data);

        return DB::transaction(function () use ($validated): Absence {
            $absence = new Absence($validated);
            $absence->status = AbsenceStatus::PENDING;
            $absence->save();

            $absence->loadMissing(['leaveType']);

            if ($absence->leaveType->requires_approval) {
                $this->balanceService->reserveLeave($absence);

                return $absence;
            }

            $this->approveAbsence($absence, null);

            return $absence->fresh();
        });
    }

    /**
     * @param array<string, mixed> $data
     *
     * @throws ValidationException
     */
    public function updateAbsence(Absence $absence, array $data): Absence
    {
        if ($absence->status !== AbsenceStatus::PENDING) {
            throw ValidationException::withMessages([
                'absence' => 'Only pending absences can be updated.',
            ]);
        }

        $oldDays = (float) $absence->duration_days;

        $validated = $this->validator->validateAbsence(array_merge($absence->toArray(), $data), $absence->getKey());

        return DB::transaction(function () use ($absence, $validated, $oldDays): Absence {
            $absence->fill($validated);
            $absence->save();

            $absence->loadMissing(['leaveType']);

            if ($absence->leaveType->requires_approval) {
                $absenceDelta = (float) $absence->duration_days - $oldDays;

                if ($absenceDelta !== 0.0) {
                    $balance = $this->balanceService->getBalance($absence->employee, $absence->leaveType, (int) now()->format('Y'));

                    $balance->pending_days = (float) $balance->pending_days + $absenceDelta;
                    $balance->recalculate();
                    $balance->save();
                }
            }

            return $absence;
        });
    }

    public function cancelAbsence(Absence $absence, string $reason): void
    {
        if (! $absence->canBeCancelled()) {
            return;
        }

        DB::transaction(function () use ($absence, $reason): void {
            $absence->loadMissing(['leaveType']);

            if ($absence->status === AbsenceStatus::PENDING && $absence->leaveType->requires_approval) {
                $this->balanceService->releasePending($absence);
            }

            if ($absence->status === AbsenceStatus::APPROVED) {
                $this->balanceService->restoreUsed($absence);
            }

            $absence->status = AbsenceStatus::CANCELLED;
            $absence->notes = trim(($absence->notes ?? '') . "\nCancelled: " . $reason);
            $absence->save();
        });
    }

    public function approveAbsence(Absence $absence, ?User $approver): void
    {
        if (! $absence->canBeApproved()) {
            throw ValidationException::withMessages([
                'absence' => 'Only pending absences can be approved.',
            ]);
        }

        DB::transaction(function () use ($absence, $approver): void {
            $absence->loadMissing(['leaveType']);

            $absence->status = AbsenceStatus::APPROVED;
            $absence->approved_by = $approver?->getKey();
            $absence->approved_at = Date::now();
            $absence->save();

            if ($absence->leaveType->requires_approval) {
                $this->balanceService->commitLeave($absence);
            } else {
                $this->balanceService->deductUsed($absence);
            }
        });
    }

    public function rejectAbsence(Absence $absence, ?User $approver, string $reason): void
    {
        if ($absence->status !== AbsenceStatus::PENDING) {
            throw ValidationException::withMessages([
                'absence' => 'Only pending absences can be rejected.',
            ]);
        }

        DB::transaction(function () use ($absence, $approver, $reason): void {
            $absence->loadMissing(['leaveType']);

            if ($absence->leaveType->requires_approval) {
                $this->balanceService->releasePending($absence);
            }

            $absence->status = AbsenceStatus::REJECTED;
            $absence->approved_by = $approver?->getKey();
            $absence->approved_at = Date::now();
            $absence->rejected_reason = $reason;
            $absence->save();
        });
    }

    /**
     * @return Collection<int, Absence>
     */
    public function checkOverlap(Employee $employee, Carbon $start, Carbon $end): Collection
    {
        return Absence::query()
            ->where('employee_id', $employee->getKey())
            ->whereDate('start_date', '<=', $end->toDateString())
            ->whereDate('end_date', '>=', $start->toDateString())
            ->get();
    }
}
