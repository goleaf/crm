<?php

declare(strict_types=1);

namespace App\Services\TimeManagement;

use App\Enums\TimeEntryApprovalStatus;
use App\Enums\TimesheetStatus;
use App\Models\Employee;
use App\Models\TimeEntry;
use App\Models\Timesheet;
use App\Models\User;
use App\Services\ActivityService;
use App\Services\NotificationService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final readonly class TimesheetService
{
    public function __construct(
        private ManagerAssignmentService $managerAssignments,
        private ActivityService $activity,
        private NotificationService $notifications,
    ) {}

    public function getOrCreateForDate(Employee $employee, Carbon $date): Timesheet
    {
        $period = $this->getWeeklyPeriod($date);

        /** @var Timesheet|null $timesheet */
        $timesheet = Timesheet::query()
            ->where('employee_id', $employee->getKey())
            ->whereDate('period_start', $period['start']->toDateString())
            ->whereDate('period_end', $period['end']->toDateString())
            ->first();

        if ($timesheet instanceof Timesheet) {
            return $timesheet;
        }

        $manager = $this->managerAssignments->getManagerForDate($employee, $period['end']);

        return Timesheet::create([
            'team_id' => $employee->team_id,
            'employee_id' => $employee->getKey(),
            'manager_id' => $manager?->getKey(),
            'period_start' => $period['start']->toDateString(),
            'period_end' => $period['end']->toDateString(),
            'status' => TimesheetStatus::DRAFT,
            'deadline_at' => $this->computeDeadlineAt($period['end'])->toDateTimeString(),
        ]);
    }

    public function submit(Timesheet $timesheet, User $actor): Timesheet
    {
        $employeeUserId = $timesheet->employee->user_id;

        if ($employeeUserId !== null && $employeeUserId !== $actor->getKey()) {
            throw ValidationException::withMessages([
                'timesheet' => 'You can only submit your own timesheets.',
            ]);
        }

        if ($timesheet->status === TimesheetStatus::APPROVED) {
            throw ValidationException::withMessages([
                'timesheet' => 'Approved timesheets must be unlocked before resubmission.',
            ]);
        }

        $this->validateMinimumDailyHours($timesheet);

        $manager = $this->managerAssignments->getManagerForDate($timesheet->employee, $timesheet->period_end);

        $timesheet->status = TimesheetStatus::PENDING;
        $timesheet->submitted_at = Date::now();
        $timesheet->manager_id = $manager?->getKey();
        $timesheet->rejected_at = null;
        $timesheet->rejected_by = null;
        $timesheet->rejection_reason = null;
        $timesheet->locked_at = null;
        $timesheet->locked_by = null;
        $timesheet->save();

        $this->activity->log($timesheet, 'submitted', [
            'status' => $timesheet->status->value,
        ]);

        return $timesheet->fresh();
    }

    public function approve(Timesheet $timesheet, User $approver): Timesheet
    {
        if ($timesheet->status !== TimesheetStatus::PENDING) {
            throw ValidationException::withMessages([
                'timesheet' => 'Only pending timesheets can be approved.',
            ]);
        }

        DB::transaction(function () use ($timesheet, $approver): void {
            $approvedAt = Date::now();

            $timesheet->status = TimesheetStatus::APPROVED;
            $timesheet->approved_at = $approvedAt;
            $timesheet->approved_by = $approver->getKey();
            $timesheet->locked_at = $approvedAt;
            $timesheet->locked_by = $approver->getKey();
            $timesheet->save();

            $this->timeEntriesQuery($timesheet)->update([
                'approval_status' => TimeEntryApprovalStatus::APPROVED->value,
                'approved_by' => $approver->getKey(),
                'approved_at' => $approvedAt,
            ]);
        });

        $this->activity->log($timesheet, 'approved', [
            'status' => $timesheet->status->value,
            'approved_by' => $approver->getKey(),
        ]);

        $employeeUser = $timesheet->employee->user;
        if ($employeeUser instanceof User) {
            $this->notifications->sendActivityAlert(
                $employeeUser,
                title: 'Timesheet approved',
                message: 'Your timesheet has been approved.',
                url: null,
            );
        }

        return $timesheet->fresh();
    }

    public function reject(Timesheet $timesheet, User $approver, string $reason): Timesheet
    {
        $reason = trim($reason);

        if (mb_strlen($reason) < 10) {
            throw ValidationException::withMessages([
                'rejection_reason' => 'Rejection reason must be at least 10 characters.',
            ]);
        }

        if ($timesheet->status !== TimesheetStatus::PENDING) {
            throw ValidationException::withMessages([
                'timesheet' => 'Only pending timesheets can be rejected.',
            ]);
        }

        DB::transaction(function () use ($timesheet, $approver, $reason): void {
            $reviewedAt = Date::now();

            $timesheet->status = TimesheetStatus::REJECTED;
            $timesheet->rejected_at = $reviewedAt;
            $timesheet->rejected_by = $approver->getKey();
            $timesheet->rejection_reason = $reason;
            $timesheet->locked_at = null;
            $timesheet->locked_by = null;
            $timesheet->save();

            $this->timeEntriesQuery($timesheet)->update([
                'approval_status' => TimeEntryApprovalStatus::REJECTED->value,
                'approved_by' => $approver->getKey(),
                'approved_at' => $reviewedAt,
            ]);
        });

        $this->activity->log($timesheet, 'rejected', [
            'status' => $timesheet->status->value,
            'rejected_by' => $approver->getKey(),
            'rejection_reason' => $reason,
        ]);

        $employeeUser = $timesheet->employee->user;
        if ($employeeUser instanceof User) {
            $this->notifications->sendActivityAlert(
                $employeeUser,
                title: 'Timesheet rejected',
                message: "Your timesheet was rejected: {$reason}",
                url: null,
            );
        }

        return $timesheet->fresh();
    }

    public function unlock(Timesheet $timesheet, User $actor): Timesheet
    {
        if ($timesheet->status !== TimesheetStatus::APPROVED) {
            throw ValidationException::withMessages([
                'timesheet' => 'Only approved timesheets can be unlocked.',
            ]);
        }

        DB::transaction(function () use ($timesheet): void {
            $timesheet->status = TimesheetStatus::PENDING;
            $timesheet->approved_at = null;
            $timesheet->approved_by = null;
            $timesheet->locked_at = null;
            $timesheet->locked_by = null;
            $timesheet->save();

            $this->timeEntriesQuery($timesheet)->update([
                'approval_status' => TimeEntryApprovalStatus::PENDING->value,
                'approved_by' => null,
                'approved_at' => null,
            ]);
        });

        $this->activity->log($timesheet, 'unlocked', [
            'status' => $timesheet->status->value,
        ]);

        return $timesheet->fresh();
    }

    /**
     * @return array{total_minutes:int,billable_minutes:int,non_billable_minutes:int,daily_minutes:array<string,int>}
     */
    public function getTotals(Timesheet $timesheet): array
    {
        $entries = $this->timeEntriesQuery($timesheet)->get(['date', 'duration_minutes', 'is_billable']);

        $total = 0;
        $billable = 0;
        $daily = [];

        foreach ($entries as $entry) {
            $minutes = (int) $entry->duration_minutes;
            $total += $minutes;

            if ((bool) $entry->is_billable) {
                $billable += $minutes;
            }

            $key = Carbon::parse((string) $entry->date)->toDateString();
            $daily[$key] = ($daily[$key] ?? 0) + $minutes;
        }

        return [
            'total_minutes' => $total,
            'billable_minutes' => $billable,
            'non_billable_minutes' => $total - $billable,
            'daily_minutes' => $daily,
        ];
    }

    /**
     * @return array{start:Carbon,end:Carbon}
     */
    private function getWeeklyPeriod(Carbon $date): array
    {
        $firstDayOfWeek = (int) config('time-management.timesheets.period.first_day_of_week', Carbon::MONDAY);
        $start = $date->copy()->startOfWeek($firstDayOfWeek)->startOfDay();

        return [
            'start' => $start,
            'end' => $start->copy()->addDays(6)->endOfDay(),
        ];
    }

    private function computeDeadlineAt(Carbon $periodEnd): Carbon
    {
        $offsetDays = (int) config('time-management.timesheets.submission.deadline_offset_days', 1);
        $time = (string) config('time-management.timesheets.submission.deadline_time', '17:00');

        [$hour, $minute] = array_pad(explode(':', $time, 2), 2, '0');

        return $periodEnd->copy()->addDays($offsetDays)->setTime((int) $hour, (int) $minute)->startOfMinute();
    }

    private function validateMinimumDailyHours(Timesheet $timesheet): void
    {
        $minDailyMinutes = (int) config('time-management.timesheets.validation.min_daily_minutes', 0);

        if ($minDailyMinutes < 1) {
            return;
        }

        $totals = $this->getTotals($timesheet);
        $daily = $totals['daily_minutes'];

        $cursor = $timesheet->period_start->copy()->startOfDay();
        $end = $timesheet->period_end->copy()->startOfDay();

        while ($cursor->lessThanOrEqualTo($end)) {
            if ($cursor->isWeekday()) {
                $minutes = $daily[$cursor->toDateString()] ?? 0;

                if ($minutes < $minDailyMinutes) {
                    throw ValidationException::withMessages([
                        'timesheet' => sprintf(
                            'Minimum daily hours not met for %s.',
                            $cursor->format('Y-m-d'),
                        ),
                    ]);
                }
            }

            $cursor = $cursor->addDay();
        }
    }

    private function timeEntriesQuery(Timesheet $timesheet)
    {
        return TimeEntry::query()
            ->where('employee_id', $timesheet->employee_id)
            ->whereBetween('date', [
                $timesheet->period_start->toDateString(),
                $timesheet->period_end->toDateString(),
            ]);
    }
}
