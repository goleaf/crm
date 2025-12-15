<?php

declare(strict_types=1);

namespace App\Services\TimeManagement;

use App\Enums\TimeEntryApprovalStatus;
use App\Models\Employee;
use App\Models\TimeEntry;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final readonly class TimeEntryService
{
    public function __construct(
        private ValidationService $validator,
        private BillingCalculator $billingCalculator,
    ) {}

    /**
     * @param array<string, mixed> $data
     *
     * @throws ValidationException
     */
    public function createTimeEntry(array $data): TimeEntry
    {
        $validated = $this->validator->validateTimeEntry($data);

        return DB::transaction(function () use ($validated): TimeEntry {
            $entry = new TimeEntry($validated);

            $entry->approval_status = $this->defaultApprovalStatusFor($entry);

            if ($entry->is_billable) {
                $entry->billing_rate = $this->billingCalculator->getBillingRate($entry);
                $entry->billing_amount = $this->billingCalculator->calculateBillingAmount($entry);
            }

            $entry->save();

            return $entry;
        });
    }

    /**
     * @param array<string, mixed> $data
     *
     * @throws ValidationException
     */
    public function updateTimeEntry(TimeEntry $entry, array $data, ?User $actor = null): TimeEntry
    {
        if (! $entry->canBeEdited($actor)) {
            throw ValidationException::withMessages([
                'time_entry' => 'Cannot edit approved time entries.',
            ]);
        }

        $validated = $this->validator->validateTimeEntry(array_merge($entry->toArray(), $data), $entry->getKey());

        return DB::transaction(function () use ($entry, $validated): TimeEntry {
            $entry->fill($validated);

            if ($entry->is_billable) {
                $entry->billing_rate = $this->billingCalculator->getBillingRate($entry);
                $entry->billing_amount = $this->billingCalculator->calculateBillingAmount($entry);
            } else {
                $entry->billing_rate = null;
                $entry->billing_amount = null;
            }

            $entry->save();

            return $entry;
        });
    }

    public function deleteTimeEntry(TimeEntry $entry, ?User $actor = null): bool
    {
        if (! $entry->canBeDeleted($actor)) {
            throw ValidationException::withMessages([
                'time_entry' => 'Cannot edit approved time entries.',
            ]);
        }

        return (bool) $entry->delete();
    }

    public function submitForApproval(TimeEntry $entry): void
    {
        $entry->approval_status = TimeEntryApprovalStatus::PENDING;
        $entry->approved_by = null;
        $entry->approved_at = null;
        $entry->save();
    }

    /**
     * @param array<int, array<string, mixed>> $entries
     *
     * @return Collection<int, TimeEntry>
     */
    public function bulkCreate(array $entries): Collection
    {
        return DB::transaction(function () use ($entries): Collection {
            return collect($entries)->map(fn (array $data): TimeEntry => $this->createTimeEntry($data));
        });
    }

    public function validateOverlap(Employee $employee, Carbon $date, ?Carbon $start, ?Carbon $end): bool
    {
        return $this->validator->checkTimeOverlap($employee, $date, $start, $end);
    }

    public function calculateTotalHours(Employee $employee, Carbon $date): float
    {
        $minutes = TimeEntry::query()
            ->where('employee_id', $employee->getKey())
            ->whereDate('date', $date->toDateString())
            ->sum('duration_minutes');

        return (float) round(((int) $minutes) / 60, 2);
    }

    private function defaultApprovalStatusFor(TimeEntry $entry): TimeEntryApprovalStatus
    {
        $approvalEnabled = (bool) config('time-management.time_entries.approval.enabled', false);

        if ($approvalEnabled || $entry->requiresApproval()) {
            return TimeEntryApprovalStatus::PENDING;
        }

        return TimeEntryApprovalStatus::APPROVED;
    }
}
