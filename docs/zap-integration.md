# Zap Scheduling Integration

This project now ships with [laraveljutsu/zap](https://github.com/ludoguenet/laravel-zap) for schedule management to meet the calendar/availability requirements in `.kiro/specs/communication-collaboration` and the no-overlap guarantees in `.kiro/specs/workflow-automation`.

## What's included
- Package install: `laraveljutsu/zap` (`config/zap.php`, vendor migrations, helper `Zap` facade).
- Database: core Zap tables plus `calendar_events.zap_schedule_id`/`zap_metadata` (`2026_05_28_000100_add_zap_schedule_columns_to_calendar_events.php`).
- Models: `User` now uses `Zap\Models\Concerns\HasSchedules`; `CalendarEvent` stores the linked Zap schedule.
- Service: `App\Services\ZapScheduleService` wraps Zap for business-hours availability, bookable slots, and calendar-event syncing.
- UI: Filament calendar and the public calendar view surface Zap-powered bookable slots; creation/update flows block on Zap conflicts.

## Business-hours availability
- `ZapScheduleService::refreshBusinessHoursAvailability(User $user)` reads `SettingsService::getBusinessHours()` (team-aware) and creates recurring availability schedules with metadata `source=business_hours` and a hash of the hours.
- Existing business-hour schedules are reused when the hash matches, and replaced when hours change.
- Defaults cover one year from `Date::now()`; grouping by identical hours collapses Mon–Fri into a single weekly schedule by default.

## Calendar event syncing
- `CalendarEventObserver` keeps Zap schedules in sync:
  - On create/update it calls `ZapScheduleService::syncCalendarEventSchedule()` (appointment schedules, no-overlap rules, inactive when cancelled).
  - On delete or recurrence regeneration it removes the associated schedule to avoid stale blocks.
  - `zap_schedule_id`/`zap_metadata` are persisted for traceability.
- Controllers/Livewire actions wrap event create/update in DB transactions and surface `ScheduleConflictException`/`InvalidScheduleException` messages back to the user.

## Booking APIs
- Bookable slots: `$service->bookableSlotsForDate($user, $date, $durationMinutes, $bufferMinutes)` and `$service->nextBookableSlot(...)` hydrate availability first, then defer to Zap’s slot engine.
- UI examples:
  - `resources/views/filament/pages/calendar.blade.php` renders Zap slots and the next available window.
  - `resources/views/calendar/index.blade.php` shows today’s slots and the next open time.

## Testing
- `tests/Unit/Services/ZapScheduleServiceTest.php` covers availability generation, event schedule syncing, and cleanup.
- Feature tests assert calendar actions persist `zap_schedule_id` for created/updated events.

## Configuration notes
- Tunables live in `config/zap.php` (no-overlap rules, buffers, validation guards). Adjust `default_rules`, `time_slots.buffer_minutes`, or `validation` thresholds if business hours or lead times change.
- Zap migrations are published under `database/migrations/2024_01_01_*`; keep them aligned with app migrations during upgrades.
