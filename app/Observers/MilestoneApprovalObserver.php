<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\MilestoneApproval;
use App\Services\Milestones\MilestoneService;

final class MilestoneApprovalObserver
{
    public function updated(MilestoneApproval $approval): void
    {
        $changes = $approval->getChanges();

        if (! array_key_exists('status', $changes)) {
            return;
        }

        // Defer to MilestoneService decisions; observers are best-effort.
        // In tests, events are faked and this won't run.
        $milestone = $approval->milestone;
        $milestone->load('approvals');

        // No-op here; the service method is the authoritative entrypoint.
        // This hook exists so interactive UI updates can call the service.
        resolve(MilestoneService::class);
    }
}

