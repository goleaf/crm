<?php

declare(strict_types=1);

namespace App\Services\TimeManagement;

use App\Enums\TimeEntryApprovalStatus;
use App\Models\Employee;
use App\Models\TimeCategory;
use App\Models\TimeEntry;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final readonly class TimeClockService
{
    public function __construct(
        private BillingCalculator $billingCalculator,
        private TimesheetService $timesheetService,
    ) {}

    /**
     * @param array{project_id:int, task_id:int, time_category_id?:int|null, description?:string|null, is_billable?:bool|null} $data
     */
    public function clockIn(Employee $employee, User $actor, array $data): TimeEntry
    {
        $active = TimeEntry::query()
            ->where('employee_id', $employee->getKey())
            ->whereNotNull('start_time')
            ->whereNull('end_time')
            ->latest('start_time')
            ->first();

        if ($active instanceof TimeEntry) {
            throw ValidationException::withMessages([
                'time_entry' => 'You are already clocked in.',
            ]);
        }

        $now = Date::now();
        $timezone = $actor->timezone ?? config('app.timezone');

        $entry = new TimeEntry([
            'team_id' => $employee->team_id,
            'employee_id' => $employee->getKey(),
            'date' => $now->toDateString(),
            'start_time' => $now,
            'end_time' => null,
            'duration_minutes' => 0,
            'description' => (string) ($data['description'] ?? 'Work session'),
            'notes' => null,
            'project_id' => $data['project_id'],
            'task_id' => $data['task_id'],
            'time_category_id' => $data['time_category_id'] ?? null,
            'is_billable' => (bool) ($data['is_billable'] ?? $this->defaultBillableFor($data['time_category_id'] ?? null)),
            'approval_status' => TimeEntryApprovalStatus::PENDING,
            'timezone' => $timezone,
        ]);

        if ($entry->is_billable) {
            $entry->billing_rate = $this->billingCalculator->getBillingRate($entry);
            $entry->billing_amount = 0.0;
        }

        $entry->timesheet_id = $this->timesheetService
            ->getOrCreateForDate($employee, Carbon::parse($entry->date))
            ->getKey();

        $entry->save();

        return $entry;
    }

    public function clockOut(Employee $employee, User $actor, ?string $notes = null): TimeEntry
    {
        /** @var TimeEntry|null $entry */
        $entry = TimeEntry::query()
            ->where('employee_id', $employee->getKey())
            ->whereNotNull('start_time')
            ->whereNull('end_time')
            ->latest('start_time')
            ->first();

        if (! $entry instanceof TimeEntry) {
            throw ValidationException::withMessages([
                'time_entry' => 'No active work session found.',
            ]);
        }

        $entry->end_time = Date::now();
        $entry->notes = $notes;

        DB::transaction(function () use ($entry): void {
            $entry->approval_status = TimeEntryApprovalStatus::PENDING;
            $entry->approved_by = null;
            $entry->approved_at = null;

            if ($entry->is_billable) {
                $entry->billing_rate = $this->billingCalculator->getBillingRate($entry);
                $entry->billing_amount = $this->billingCalculator->calculateBillingAmount($entry);
            } else {
                $entry->billing_rate = null;
                $entry->billing_amount = null;
            }

            $entry->save();
        });

        return $entry->fresh();
    }

    private function defaultBillableFor(?int $timeCategoryId): bool
    {
        if ($timeCategoryId === null) {
            return false;
        }

        $category = TimeCategory::query()->find($timeCategoryId);

        return $category instanceof TimeCategory && $category->is_billable_default;
    }
}
