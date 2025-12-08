<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\CalendarEvent;
use Carbon\Carbon;
use Illuminate\Support\Collection;

final class RecurrenceService
{
    /**
     * Generate recurring instances for a calendar event.
     *
     * @return Collection<int, CalendarEvent>
     */
    public function generateInstances(CalendarEvent $event, int $maxInstances = 100): Collection
    {
        if (! $event->isRecurring()) {
            return collect();
        }

        $instances = collect();
        $currentDate = \Illuminate\Support\Facades\Date::parse($event->start_at);
        $endDate = $event->recurrence_end_date ? \Illuminate\Support\Facades\Date::parse($event->recurrence_end_date) : $currentDate->copy()->addYear();
        $count = 0;

        while ($currentDate->lte($endDate) && $count < $maxInstances) {
            // Skip the first occurrence (it's the parent event)
            if ($count > 0) {
                $instance = $this->createInstance($event, $currentDate);
                $instances->push($instance);
            }

            $currentDate = $this->getNextOccurrence($currentDate, $event->recurrence_rule);
            $count++;
        }

        return $instances;
    }

    /**
     * Create a recurring instance from a parent event.
     */
    private function createInstance(CalendarEvent $parent, Carbon $startDate): CalendarEvent
    {
        $duration = $parent->durationMinutes() ?? 60;
        $endDate = $startDate->copy()->addMinutes($duration);

        return new CalendarEvent([
            'team_id' => $parent->team_id,
            'creator_id' => $parent->creator_id,
            'title' => $parent->title,
            'type' => $parent->type,
            'status' => $parent->status,
            'is_all_day' => $parent->is_all_day,
            'start_at' => $startDate,
            'end_at' => $endDate,
            'location' => $parent->location,
            'room_booking' => $parent->room_booking,
            'meeting_url' => $parent->meeting_url,
            'reminder_minutes_before' => $parent->reminder_minutes_before,
            'attendees' => $parent->attendees,
            'related_id' => $parent->related_id,
            'related_type' => $parent->related_type,
            'notes' => $parent->notes,
            'agenda' => $parent->agenda,
            'recurrence_parent_id' => $parent->id,
            'creation_source' => $parent->creation_source,
        ]);
    }

    /**
     * Calculate the next occurrence based on recurrence rule.
     */
    private function getNextOccurrence(Carbon $current, ?string $rule): Carbon
    {
        return match ($rule) {
            'DAILY' => $current->copy()->addDay(),
            'WEEKLY' => $current->copy()->addWeek(),
            'MONTHLY' => $current->copy()->addMonth(),
            'YEARLY' => $current->copy()->addYear(),
            default => $current->copy()->addDay(),
        };
    }

    /**
     * Update all instances of a recurring event efficiently using batch update.
     */
    public function updateInstances(CalendarEvent $parent, array $updates): void
    {
        if (! $parent->isRecurring()) {
            return;
        }

        // Batch update all future instances for better performance
        $parent->recurrenceInstances()
            ->where('start_at', '>=', now())
            ->update(array_merge($updates, ['updated_at' => now()]));
    }

    /**
     * Delete all instances of a recurring event efficiently using batch delete.
     */
    public function deleteInstances(CalendarEvent $parent): void
    {
        if (! $parent->isRecurring()) {
            return;
        }

        $instances = $parent->recurrenceInstances()->get();

        $scheduleService = resolve(ZapScheduleService::class);

        foreach ($instances as $instance) {
            $scheduleService->deleteCalendarEventSchedule($instance);
        }

        // Batch soft delete instead of individual deletes for better performance
        $parent->recurrenceInstances()->update(['deleted_at' => now()]);
    }
}
