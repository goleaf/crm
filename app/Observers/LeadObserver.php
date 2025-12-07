<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\Lead;
use App\Services\LeadDuplicateDetectionService;
use Relaticle\Flowforge\Services\Rank;

final readonly class LeadObserver
{
    public function creating(Lead $lead): void
    {
        if (auth('web')->check()) {
            $lead->creator_id ??= auth('web')->id();
            $lead->team_id ??= auth('web')->user()->currentTeam->getKey();
        }

        $lead->order_column ??= Rank::forEmptySequence()->get();
        $lead->last_activity_at ??= now();
    }

    /**
     * Handle the Lead "saved" event.
     * Invalidate AI summary when lead data changes.
     */
    public function saved(Lead $lead): void
    {
        $lead->invalidateAiSummary();

        if ($lead->duplicate_of_id !== null) {
            return;
        }

        if (! $lead->wasRecentlyCreated && ! $lead->wasChanged(['email', 'phone', 'mobile', 'name', 'company_name'])) {
            return;
        }

        $this->refreshDuplicateSignals($lead);
    }

    private function refreshDuplicateSignals(Lead $lead): void
    {
        $duplicates = app(LeadDuplicateDetectionService::class)
            ->find($lead, threshold: 60.0, limit: 1);

        if ($duplicates->isEmpty()) {
            if ($lead->duplicate_score !== null) {
                $lead->forceFill([
                    'duplicate_score' => null,
                ])->saveQuietly();
            }

            return;
        }

        $match = $duplicates->first();

        $lead->forceFill([
            'duplicate_of_id' => $match['lead']->getKey(),
            'duplicate_score' => $match['score'],
        ])->saveQuietly();
    }
}
