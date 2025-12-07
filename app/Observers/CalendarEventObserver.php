<?php

declare(strict_types=1);

namespace App\Observers;

use App\Enums\CalendarEventStatus;
use App\Models\CalendarEvent;
use App\Models\Team;
use App\Models\User;
use Illuminate\Support\Facades\Date;

final readonly class CalendarEventObserver
{
    public function creating(CalendarEvent $event): void
    {
        $guard = auth('web');

        if ($guard->check()) {
            $user = $guard->user();
            $team = $user instanceof User ? $user->currentTeam : null;

            if ($user instanceof User && $team instanceof Team) {
                $event->creator_id ??= $user->getKey();
                $event->team_id ??= $team->getKey();
            }
        }

        $event->status ??= CalendarEventStatus::SCHEDULED;
        $event->sync_status ??= \App\Enums\CalendarSyncStatus::NOT_SYNCED;
        $event->start_at ??= Date::now();
    }
}
