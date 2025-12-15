<?php

declare(strict_types=1);

namespace App\Services\TimeManagement;

use App\Models\TimeEntry;

final readonly class BillingCalculator
{
    public function getBillingRate(TimeEntry $entry): float
    {
        $custom = $entry->billing_rate;
        if ($custom !== null && (float) $custom > 0) {
            return (float) $custom;
        }

        $entry->loadMissing(['project', 'employee', 'timeCategory']);

        $categoryRate = $entry->timeCategory?->default_billing_rate;
        if ($categoryRate !== null && (float) $categoryRate > 0) {
            return (float) $categoryRate;
        }

        $projectRate = $entry->project?->billing_rate;
        if ($projectRate !== null && (float) $projectRate > 0) {
            return (float) $projectRate;
        }

        $employeeRate = $entry->employee?->default_billing_rate;
        if ($employeeRate !== null && (float) $employeeRate > 0) {
            return (float) $employeeRate;
        }

        $defaultRate = config('time-management.billing.default_rate');
        if ($defaultRate !== null && is_numeric($defaultRate) && (float) $defaultRate > 0) {
            return (float) $defaultRate;
        }

        throw new \DomainException('Unable to determine billing rate for billable time entry.');
    }

    public function calculateBillingAmount(TimeEntry $entry): float
    {
        $rate = $this->getBillingRate($entry);

        return (float) round(($entry->duration_minutes / 60) * $rate, 2);
    }
}
