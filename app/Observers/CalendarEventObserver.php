<?php

declare(strict_types=1);

namespace App\Observers;

use App\Enums\CalendarEventStatus;
use App\Models\CalendarEvent;
use App\Models\Team;
use App\Models\User;
use App\Services\ActivityService;
use App\Services\RecurrenceService;
use App\Services\ZapScheduleService;
use Illuminate\Support\Facades\Date;

final readonly class CalendarEventObserver
{
    public function __construct(private ZapScheduleService $zapScheduleService) {}

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

    public function created(CalendarEvent $event): void
    {
        // Generate recurring instances if this is a recurring event
        if ($event->isRecurring() && ! $event->isRecurringInstance()) {
            $recurrenceService = resolve(RecurrenceService::class);
            $instances = $recurrenceService->generateInstances($event);

            // Use individual saves for reliability
            // TODO: Optimize with batch insert in future (see docs/performance-calendar-events-implementation-notes.md)
            foreach ($instances as $instance) {
                $instance->save();
            }
        }

        // Log activity
        resolve(ActivityService::class)->log(
            $event,
            'created',
            [
                'title' => $event->title,
                'type' => $event->type->value,
                'start_at' => $event->start_at?->toIso8601String(),
            ]
        );

        if ($event->zap_schedule_id === null) {
            $this->syncZapSchedule($event);
        }
    }

    public function updated(CalendarEvent $event): void
    {
        // If recurrence rule changed, regenerate instances
        if ($event->isRecurring() && ! $event->isRecurringInstance() && $event->wasChanged('recurrence_rule')) {
            $recurrenceService = resolve(RecurrenceService::class);

            // Delete old instances
            $recurrenceService->deleteInstances($event);

            // Generate new instances
            $instances = $recurrenceService->generateInstances($event);

            // Use individual saves for reliability
            // TODO: Optimize with batch insert in future (see docs/performance-calendar-events-implementation-notes.md)
            foreach ($instances as $instance) {
                $instance->save();
            }
        }

        if ($event->wasChanged('creator_id')) {
            $this->zapScheduleService->deleteCalendarEventSchedule($event);
        }

        if ($event->wasChanged([
            'start_at',
            'end_at',
            'status',
            'is_all_day',
            'title',
            'agenda',
            'notes',
            'creator_id',
        ])) {
            $this->syncZapSchedule($event);
        }

        // Log activity
        $changes = $event->getChanges();

        if (! empty($changes)) {
            resolve(ActivityService::class)->log(
                $event,
                'updated',
                $changes
            );
        }
    }

    public function deleting(CalendarEvent $event): void
    {
        // Delete all recurring instances when parent is deleted
        if ($event->isRecurring() && ! $event->isRecurringInstance()) {
            $recurrenceService = resolve(RecurrenceService::class);
            $recurrenceService->deleteInstances($event);
        }
    }

    public function deleted(CalendarEvent $event): void
    {
        // Log activity
        resolve(ActivityService::class)->log(
            $event,
            'deleted',
            ['title' => $event->title]
        );

        $this->zapScheduleService->deleteCalendarEventSchedule($event);
    }

    private function syncZapSchedule(CalendarEvent $event): void
    {
        try {
            $this->zapScheduleService->syncCalendarEventSchedule($event);
        } catch (\Throwable $exception) {
            report($exception);
        }
    }
}
