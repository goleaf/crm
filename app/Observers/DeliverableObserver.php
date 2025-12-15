<?php

declare(strict_types=1);

namespace App\Observers;

use App\Enums\DeliverableStatus;
use App\Models\Deliverable;
use App\Services\Milestones\MilestoneService;

final class DeliverableObserver
{
    public function saving(Deliverable $deliverable): void
    {
        if ($deliverable->status !== DeliverableStatus::COMPLETED) {
            return;
        }

        if (! $deliverable->hasCompletionEvidence() && ! $deliverable->requires_approval) {
            throw new \DomainException(__('app.messages.deliverable_completion_evidence_required'));
        }

        if ($deliverable->completed_at === null) {
            $deliverable->completed_at = now();
        }
    }

    public function saved(Deliverable $deliverable): void
    {
        if (! $deliverable->milestone instanceof \App\Models\Milestone) {
            return;
        }

        resolve(MilestoneService::class)->syncStatusFromDeliverables($deliverable->milestone);
    }
}

