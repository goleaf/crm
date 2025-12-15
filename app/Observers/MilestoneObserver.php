<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\Milestone;
use App\Services\Milestones\DependencyService;
use App\Services\NotificationService;
use Illuminate\Support\Facades\Date;

final class MilestoneObserver
{
    public function updated(Milestone $milestone): void
    {
        $changes = $milestone->getChanges();

        if (array_key_exists('target_date', $changes)) {
            $old = $milestone->getOriginal('target_date');
            $new = $milestone->target_date;

            if (is_string($old)) {
                resolve(DependencyService::class)->cascadeTargetDateChange(
                    $milestone,
                    Date::parse($old),
                    $new,
                );
            }
        }

        if (array_key_exists('owner_id', $changes) && $milestone->owner !== null) {
            resolve(NotificationService::class)->sendActivityAlert(
                $milestone->owner,
                __('notifications.milestones.assigned_title'),
                __('notifications.milestones.assigned_body', [
                    'title' => $milestone->title,
                    'date' => $milestone->target_date->toDateString(),
                ]),
            );
        }
    }
}

