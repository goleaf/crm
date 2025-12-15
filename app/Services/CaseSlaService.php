<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\CaseStatus;
use App\Models\SupportCase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Config;

final class CaseSlaService
{
    /**
     * Calculate and set SLA due date for a case based on priority.
     */
    public function calculateSlaDueDate(SupportCase $case): ?Carbon
    {
        $resolutionTime = Config::get("cases.sla.resolution_time.{$case->priority->value}");

        if ($resolutionTime === null) {
            return null;
        }

        return now()->addMinutes($resolutionTime);
    }

    /**
     * Calculate response time SLA for a case.
     */
    public function calculateResponseSlaDueDate(SupportCase $case): ?Carbon
    {
        $responseTime = Config::get("cases.sla.response_time.{$case->priority->value}");

        if ($responseTime === null) {
            return null;
        }

        return $case->created_at->addMinutes($responseTime);
    }

    /**
     * Check if a case has breached its SLA.
     */
    public function checkSlaBreach(SupportCase $case): bool
    {
        if ($case->sla_due_at === null) {
            return false;
        }

        if ($case->resolved_at !== null) {
            return false;
        }

        return now()->isAfter($case->sla_due_at);
    }

    /**
     * Mark a case as SLA breached.
     */
    public function markSlaBreach(SupportCase $case): void
    {
        if (! $case->sla_breached) {
            $case->update([
                'sla_breached' => true,
                'sla_breach_at' => now(),
            ]);
        }
    }

    /**
     * Record first response time for a case.
     */
    public function recordFirstResponse(SupportCase $case): void
    {
        if ($case->first_response_at !== null) {
            return;
        }

        $responseTime = $case->created_at->diffInMinutes(now());

        $case->update([
            'first_response_at' => now(),
            'response_time_minutes' => $responseTime,
        ]);
    }

    /**
     * Record resolution time for a case.
     */
    public function recordResolution(SupportCase $case): void
    {
        if ($case->resolved_at !== null) {
            return;
        }

        $resolutionTime = $case->created_at->diffInMinutes(now());

        $case->update([
            'resolved_at' => now(),
            'resolution_time_minutes' => $resolutionTime,
            'status' => CaseStatus::CLOSED,
        ]);
    }

    /**
     * Update SLA due date when priority changes.
     */
    public function updateSlaDueDate(SupportCase $case): void
    {
        if ($case->resolved_at !== null) {
            return;
        }

        $newSlaDueDate = $this->calculateSlaDueDate($case);

        if ($newSlaDueDate instanceof \Illuminate\Support\Carbon) {
            $case->update([
                'sla_due_at' => $newSlaDueDate,
            ]);
        }
    }

    /**
     * Get all cases that have breached SLA.
     *
     * @return \Illuminate\Database\Eloquent\Collection<int, SupportCase>
     */
    public function getBreachedCases(): \Illuminate\Database\Eloquent\Collection
    {
        return SupportCase::query()
            ->whereNull('resolved_at')
            ->whereNotNull('sla_due_at')
            ->where('sla_due_at', '<', now())
            ->where('sla_breached', false)
            ->get();
    }

    /**
     * Process SLA breaches for all eligible cases.
     */
    public function processSlaBreach(): int
    {
        $breachedCases = $this->getBreachedCases();

        foreach ($breachedCases as $case) {
            $this->markSlaBreach($case);
        }

        return $breachedCases->count();
    }
}
