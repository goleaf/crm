<?php

declare(strict_types=1);

namespace App\Services\TimeManagement;

use App\Models\Absence;
use App\Models\Employee;
use App\Models\LeaveType;
use App\Models\Project;
use App\Models\TimeCategory;
use App\Models\TimeEntry;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

final readonly class ValidationService
{
    public function __construct(private LeaveBalanceService $leaveBalanceService) {}

    /**
     * @param array<string, mixed> $data
     *
     * @return array<string, mixed>
     *
     * @throws ValidationException
     */
    public function validateTimeEntry(array $data, ?int $excludeId = null): array
    {
        $validated = Validator::make($data, [
            'employee_id' => ['required', 'integer', 'exists:employees,id'],
            'date' => ['required', 'date'],
            'start_time' => ['nullable', 'date'],
            'end_time' => ['nullable', 'date'],
            'duration_minutes' => ['nullable', 'integer', 'min:1', 'max:1440'],
            'description' => ['required', 'string', 'max:1000'],
            'notes' => ['nullable', 'string'],
            'is_billable' => ['sometimes', 'boolean'],
            'billing_rate' => ['nullable', 'numeric', 'min:0.01'],
            'project_id' => ['nullable', 'integer', 'exists:projects,id'],
            'task_id' => ['nullable', 'integer', 'exists:tasks,id'],
            'company_id' => ['nullable', 'integer', 'exists:companies,id'],
            'time_category_id' => ['nullable', 'integer', 'exists:time_categories,id'],
            'invoice_id' => ['nullable', 'integer', 'exists:invoices,id'],
        ])->validate();

        $start = isset($validated['start_time']) ? Carbon::parse((string) $validated['start_time']) : null;
        $end = isset($validated['end_time']) ? Carbon::parse((string) $validated['end_time']) : null;

        if (($start === null) !== ($end === null)) {
            throw ValidationException::withMessages([
                'start_time' => 'Start time and end time must be provided together.',
                'end_time' => 'Start time and end time must be provided together.',
            ]);
        }

        if ($start !== null && $end !== null) {
            if ($end->lessThanOrEqualTo($start)) {
                throw ValidationException::withMessages([
                    'end_time' => 'End time must be after start time.',
                ]);
            }

            $validated['duration_minutes'] = $end->diffInMinutes($start);
        }

        if (! isset($validated['duration_minutes'])) {
            throw ValidationException::withMessages([
                'duration_minutes' => 'Duration is required when start/end times are not provided.',
            ]);
        }

        if (
            empty($validated['project_id'])
            && empty($validated['company_id'])
            && empty($validated['task_id'])
            && empty($validated['time_category_id'])
        ) {
            throw ValidationException::withMessages([
                'project_id' => 'Time entries must be associated with a project, client, task, or category.',
            ]);
        }

        $isBillable = (bool) ($validated['is_billable'] ?? false);

        if ($isBillable && empty($validated['project_id']) && empty($validated['company_id'])) {
            throw ValidationException::withMessages([
                'project_id' => 'Billable time entries must be associated with a project or client.',
            ]);
        }

        if (! empty($validated['time_category_id'])) {
            $category = TimeCategory::query()->findOrFail((int) $validated['time_category_id']);

            if (! $category->is_active) {
                $isAllowedHistorical = false;

                if ($excludeId !== null) {
                    $existing = TimeEntry::query()->find($excludeId);
                    $isAllowedHistorical = $existing instanceof TimeEntry && $existing->time_category_id === $category->id;
                }

                if (! $isAllowedHistorical) {
                    throw ValidationException::withMessages([
                        'time_category_id' => 'Selected job role is inactive.',
                    ]);
                }
            }
        }

        if ($start !== null && $end !== null) {
            $employee = Employee::query()->findOrFail((int) $validated['employee_id']);
            $date = Carbon::parse((string) $validated['date']);

            $overlapping = TimeEntry::query()
                ->where('employee_id', $employee->getKey())
                ->whereDate('date', $date->toDateString())
                ->whereNotNull('start_time')
                ->whereNotNull('end_time')
                ->where('start_time', '<', $end)
                ->where('end_time', '>', $start)
                ->when($excludeId !== null, fn (Builder $query): Builder => $query->whereKeyNot($excludeId))
                ->first();

            if ($overlapping instanceof TimeEntry) {
                $from = $overlapping->start_time?->format('H:i') ?? '?';
                $to = $overlapping->end_time?->format('H:i') ?? '?';

                throw ValidationException::withMessages([
                    'start_time' => "Time entry overlaps with existing entry from {$from} to {$to}.",
                ]);
            }
        }

        if (! empty($validated['project_id'])) {
            $employee = Employee::query()->findOrFail((int) $validated['employee_id']);
            $project = Project::query()->findOrFail((int) $validated['project_id']);

            if (! $this->employeeHasProjectAccess($employee, $project)) {
                throw ValidationException::withMessages([
                    'project_id' => 'You do not have access to this project.',
                ]);
            }
        }

        if (! empty($validated['project_id']) && ! empty($validated['task_id'])) {
            $project = Project::query()->findOrFail((int) $validated['project_id']);

            $belongs = $project->tasks()->whereKey((int) $validated['task_id'])->exists();
            if (! $belongs) {
                throw ValidationException::withMessages([
                    'task_id' => 'Task must belong to the selected project.',
                ]);
            }
        }

        return $validated;
    }

    /**
     * @param array<string, mixed> $data
     *
     * @return array<string, mixed>
     *
     * @throws ValidationException
     */
    public function validateAbsence(array $data, ?int $excludeId = null): array
    {
        $validated = Validator::make($data, [
            'employee_id' => ['required', 'integer', 'exists:employees,id'],
            'leave_type_id' => ['required', 'integer', 'exists:leave_types,id'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date'],
            'reason' => ['nullable', 'string'],
            'notes' => ['nullable', 'string'],
        ])->validate();

        $start = Carbon::parse((string) $validated['start_date'])->startOfDay();
        $end = Carbon::parse((string) $validated['end_date'])->startOfDay();

        if ($end->lessThan($start)) {
            throw ValidationException::withMessages([
                'end_date' => 'End date must be on or after start date.',
            ]);
        }

        $employee = Employee::query()->findOrFail((int) $validated['employee_id']);
        $leaveType = LeaveType::query()->findOrFail((int) $validated['leave_type_id']);

        $overlaps = $this->checkAbsenceOverlap($employee, $start, $end, $excludeId);
        if ($overlaps) {
            throw ValidationException::withMessages([
                'start_date' => 'Absence overlaps with an existing absence.',
            ]);
        }

        $durationDays = (new Absence([
            'start_date' => $start,
            'end_date' => $end,
        ]))->calculateDuration()['days'];

        if (! $this->checkLeaveBalance($employee, $leaveType, (float) $durationDays)) {
            throw ValidationException::withMessages([
                'start_date' => sprintf(
                    'Insufficient leave balance. Available: %s days, Requested: %s days',
                    (string) $this->leaveBalanceService->getBalance($employee, $leaveType, (int) now()->format('Y'))->available_days,
                    (string) $durationDays,
                ),
            ]);
        }

        return $validated;
    }

    public function checkTimeOverlap(Employee $employee, Carbon $date, ?Carbon $start, ?Carbon $end, ?int $excludeId = null): bool
    {
        if ($start === null || $end === null) {
            return false;
        }

        $query = TimeEntry::query()
            ->where('employee_id', $employee->getKey())
            ->whereDate('date', $date->toDateString())
            ->whereNotNull('start_time')
            ->whereNotNull('end_time')
            ->where('start_time', '<', $end)
            ->where('end_time', '>', $start);

        if ($excludeId !== null) {
            $query->whereKeyNot($excludeId);
        }

        return $query->exists();
    }

    public function checkAbsenceOverlap(Employee $employee, Carbon $start, Carbon $end, ?int $excludeId = null): bool
    {
        $query = Absence::query()
            ->where('employee_id', $employee->getKey())
            ->whereDate('start_date', '<=', $end->toDateString())
            ->whereDate('end_date', '>=', $start->toDateString());

        if ($excludeId !== null) {
            $query->whereKeyNot($excludeId);
        }

        return $query->exists();
    }

    public function validateDuration(int $minutes): bool
    {
        return $minutes >= 1 && $minutes <= 1440;
    }

    public function validateDateRange(Carbon $start, Carbon $end): bool
    {
        return $end->greaterThanOrEqualTo($start);
    }

    public function checkLeaveBalance(Employee $employee, LeaveType $leaveType, float $days): bool
    {
        $balance = $this->leaveBalanceService->getBalance($employee, $leaveType, (int) now()->format('Y'));

        return $balance->hasAvailableBalance($days);
    }

    private function employeeHasProjectAccess(Employee $employee, Project $project): bool
    {
        if ($employee->user_id === null) {
            return true;
        }

        return $project->teamMembers()->whereKey($employee->user_id)->exists();
    }
}
