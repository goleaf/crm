<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\CalendarEventStatus;
use App\Models\CalendarEvent;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Date;
use Zap\Enums\ScheduleTypes;
use Zap\Facades\Zap;
use Zap\Models\Schedule;
use Zap\Services\ScheduleService;

final readonly class ZapScheduleService
{
    public function __construct(
        private ScheduleService $scheduleService,
        private SettingsService $settingsService,
    ) {}

    /**
     * Ensure the user has availability schedules based on configured business hours.
     *
     * @return Collection<int, Schedule>
     */
    public function refreshBusinessHoursAvailability(User $user): Collection
    {
        $teamId = $user->currentTeam?->getKey();
        $businessHours = $this->settingsService->getBusinessHours($teamId);
        $hoursHash = md5(json_encode($businessHours));

        $existing = $user->availabilitySchedules()
            ->where('metadata->source', 'business_hours')
            ->when($teamId, fn ($query) => $query->where('metadata->team_id', $teamId))
            ->get();

        $canReuseExisting = $existing->isNotEmpty() && $existing->every(
            fn (Schedule $schedule): bool => data_get($schedule->metadata, 'hours_hash') === $hoursHash
                && ! empty(data_get($schedule->metadata, 'day_names')),
        );

        if ($canReuseExisting) {
            return $existing;
        }

        foreach ($existing as $schedule) {
            $this->scheduleService->delete($schedule);
        }

        $groupedHours = collect($businessHours)
            ->filter(fn (mixed $value): bool => is_array($value) && isset($value['start'], $value['end']))
            ->groupBy(
                fn (array $hours): string => $hours['start'] . '-' . $hours['end'],
                preserveKeys: true,
            );

        if ($groupedHours->isEmpty()) {
            return collect();
        }

        $startDate = Date::now()->toDateString();
        $endDate = Date::now()->addYear()->toDateString();

        return $groupedHours->map(function (Collection $days, string $timeRange) use (
            $user,
            $teamId,
            $hoursHash,
            $startDate,
            $endDate
        ): Schedule {
            [$startTime, $endTime] = explode('-', $timeRange);
            $dayNames = array_keys($days->toArray());

            return Zap::for($user)
                ->named('Business Hours: ' . implode(', ', array_map(ucfirst(...), $dayNames)))
                ->availability()
                ->between($startDate, $endDate)
                ->weekly($dayNames)
                ->addPeriod($startTime, $endTime)
                ->withMetadata([
                    'source' => 'business_hours',
                    'team_id' => $teamId,
                    'hours_hash' => $hoursHash,
                    'day_names' => $dayNames,
                ])
                ->save();
        })->values();
    }

    public function syncCalendarEventSchedule(CalendarEvent $event): ?Schedule
    {
        $creator = $event->creator;

        if ($creator === null) {
            return null;
        }

        $start = $event->start_at ?? Date::now();
        $end = $event->end_at
            ?? ($event->is_all_day ? $start->copy()->endOfDay() : $start->copy()->addMinutes($event->durationMinutes() ?? 60));

        $startDate = $start->toDateString();
        $endDate = $start->isSameDay($end)
            ? $start->copy()->addDay()->toDateString()
            : $end->toDateString();

        $periods = [[
            'date' => $startDate,
            'start_time' => $event->is_all_day ? '00:00' : $start->format('H:i'),
            'end_time' => $event->is_all_day ? '23:59' : $end->format('H:i'),
            'metadata' => [
                'event_id' => $event->getKey(),
                'team_id' => $event->team_id,
            ],
        ]];

        $attributes = [
            'name' => $event->title,
            'description' => $event->agenda ?? $event->notes,
            'schedule_type' => ScheduleTypes::APPOINTMENT,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'is_active' => $event->status !== CalendarEventStatus::CANCELLED,
            'metadata' => [
                'calendar_event_id' => $event->getKey(),
                'event_type' => $event->type->value,
                'status' => $event->status->value,
                'team_id' => $event->team_id,
            ],
        ];

        $schedule = $this->findExistingSchedule($event);

        if ($schedule instanceof Schedule) {
            $schedule = $this->scheduleService->update($schedule, $attributes, $periods);
        } else {
            $schedule = $this->scheduleService->create($creator, $attributes, $periods);
        }

        $event->forceFill([
            'zap_schedule_id' => $schedule->getKey(),
            'zap_metadata' => array_merge($event->zap_metadata ?? [], [
                'synced_at' => Date::now()->toIso8601String(),
            ]),
        ])->saveQuietly();

        return $schedule;
    }

    public function deleteCalendarEventSchedule(CalendarEvent $event): void
    {
        $schedule = $this->findExistingSchedule($event);

        if ($schedule instanceof Schedule) {
            $this->scheduleService->delete($schedule);
        }

        Schedule::query()
            ->whereKey($event->zap_schedule_id)
            ->orWhere('metadata->calendar_event_id', $event->getKey())
            ->delete();

        if ($event->zap_schedule_id !== null) {
            $event->forceFill([
                'zap_schedule_id' => null,
                'zap_metadata' => $event->zap_metadata,
            ])->saveQuietly();
        }
    }

    public function bookableSlotsForDate(User $user, string|Carbon $date, ?int $duration = null, ?int $bufferMinutes = null): array
    {
        $this->refreshBusinessHoursAvailability($user);

        $dateString = $date instanceof Carbon ? $date->toDateString() : $date;

        return $user->getBookableSlots(
            $dateString,
            $this->resolveSlotDuration($duration),
            $this->resolveBufferMinutes($bufferMinutes),
        );
    }

    public function nextBookableSlot(User $user, ?string $afterDate = null, ?int $duration = null, ?int $bufferMinutes = null): ?array
    {
        $this->refreshBusinessHoursAvailability($user);

        return $user->getNextBookableSlot(
            $afterDate,
            $this->resolveSlotDuration($duration),
            $this->resolveBufferMinutes($bufferMinutes),
        );
    }

    private function findExistingSchedule(CalendarEvent $event): ?Schedule
    {
        if ($event->zap_schedule_id === null) {
            return null;
        }

        return Schedule::find($event->zap_schedule_id);
    }

    private function resolveSlotDuration(?int $duration): int
    {
        return max(1, $duration ?? (int) config('zap.time_slots.default_slot_duration_minutes', 60));
    }

    private function resolveBufferMinutes(?int $bufferMinutes): int
    {
        return max(0, $bufferMinutes ?? (int) config('zap.time_slots.buffer_minutes', 0));
    }
}
