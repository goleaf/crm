<?php

declare(strict_types=1);

use App\Models\CalendarEvent;
use App\Models\User;
use App\Services\ZapScheduleService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Zap\Models\Schedule;

uses(RefreshDatabase::class);

it('builds availability schedules from business hours', function (): void {
    \Illuminate\Support\Facades\Date::setTestNow('2025-01-15 09:00:00');

    $user = User::factory()->withPersonalTeam()->create();

    $service = resolve(ZapScheduleService::class);

    $schedules = $service->refreshBusinessHoursAvailability($user);

    expect($schedules)->not->toBeEmpty();

    $availability = $schedules->first();

    expect($availability->schedule_type->value)->toBe('availability')
        ->and($availability->is_recurring)->toBeTrue()
        ->and(data_get($availability->metadata, 'source'))->toBe('business_hours');
});

it('syncs calendar events into zap schedules', function (): void {
    \Illuminate\Support\Facades\Date::setTestNow('2025-01-15 09:00:00');

    $event = CalendarEvent::factory()->create([
        'start_at' => \Illuminate\Support\Facades\Date::now()->addDay(),
        'end_at' => \Illuminate\Support\Facades\Date::now()->addDay()->addHours(2),
    ]);

    $service = resolve(ZapScheduleService::class);

    $schedule = $service->syncCalendarEventSchedule($event);

    expect($schedule)->not->toBeNull();

    $freshEvent = $event->fresh();

    expect($freshEvent?->zap_schedule_id)->toBe($schedule?->getKey())
        ->and($schedule?->schedule_type->value)->toBe('appointment')
        ->and($schedule?->periods)->not->toBeEmpty();
});

it('cleans up schedules when events are removed', function (): void {
    \Illuminate\Support\Facades\Date::setTestNow('2025-01-15 09:00:00');

    $event = CalendarEvent::factory()->create([
        'start_at' => \Illuminate\Support\Facades\Date::now()->addDay(),
        'end_at' => \Illuminate\Support\Facades\Date::now()->addDay()->addHour(),
    ]);

    $service = resolve(ZapScheduleService::class);

    $service->syncCalendarEventSchedule($event);

    $scheduleId = $event->fresh()?->zap_schedule_id;

    $event->delete();

    expect($scheduleId)->not->toBeNull()
        ->and(Schedule::find($scheduleId))->toBeNull();
});
