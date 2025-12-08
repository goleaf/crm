<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\CalendarEventStatus;
use App\Enums\CalendarEventType;
use App\Models\CalendarEvent;
use App\Services\ZapScheduleService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Zap\Exceptions\InvalidScheduleException;
use Zap\Exceptions\ScheduleConflictException;

final class CalendarController extends Controller
{
    public function index(Request $request): View
    {
        $team = $request->user()?->currentTeam;
        $user = $request->user();
        $zapScheduleService = resolve(ZapScheduleService::class);

        $events = CalendarEvent::query()
            ->when($team, fn ($query) => $query->whereBelongsTo($team))
            ->oldest('start_at')
            ->whereDate('start_at', '>=', now()->subDays(1))
            ->limit(50)
            ->get();

        $bookableSlots = [];
        $nextSlot = null;

        if ($user) {
            $bookableSlots = $zapScheduleService->bookableSlotsForDate($user, now()->toDateString(), 60, 15);
            $nextSlot = $zapScheduleService->nextBookableSlot($user, now()->toDateString(), 60, 15);
        }

        return view('calendar.index', [
            'events' => $events,
            'eventTypes' => CalendarEventType::cases(),
            'bookableSlots' => $bookableSlots,
            'nextBookableSlot' => $nextSlot,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $user = $request->user();
        $team = $user?->currentTeam;
        $zapScheduleService = resolve(ZapScheduleService::class);

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'type' => ['required', 'in:'.collect(CalendarEventType::cases())->pluck('value')->implode(',')],
            'start_at' => ['required', 'date'],
            'end_at' => ['nullable', 'date', 'after_or_equal:start_at'],
            'location' => ['nullable', 'string', 'max:255'],
            'meeting_url' => ['nullable', 'url', 'max:255'],
            'attendees' => ['nullable', 'array'],
            'attendees.*.name' => ['required_with:attendees', 'string', 'max:255'],
            'attendees.*.email' => ['nullable', 'email', 'max:255'],
        ]);

        try {
            DB::transaction(function () use ($validated, $team, $user, $zapScheduleService): void {
                $event = CalendarEvent::create([
                    ...$validated,
                    'team_id' => $team?->getKey(),
                    'creator_id' => $user?->getKey(),
                    'status' => CalendarEventStatus::SCHEDULED,
                ]);

                $zapScheduleService->syncCalendarEventSchedule($event);
            });
        } catch (ScheduleConflictException|InvalidScheduleException $exception) {
            return back()
                ->withErrors(['schedule' => $exception->getMessage()])
                ->withInput();
        }

        return to_route('calendar')->with('status', 'Event scheduled.');
    }

    public function exportIcal(Request $request): \Symfony\Component\HttpFoundation\Response
    {
        $user = $request->user();
        $team = $user?->currentTeam;

        $events = CalendarEvent::query()
            ->when($team, fn ($query) => $query->whereBelongsTo($team))
            ->whereDate('start_at', '>=', now()->subMonths(3))
            ->whereDate('start_at', '<=', now()->addMonths(6))
            ->oldest('start_at')
            ->get();

        $ical = "BEGIN:VCALENDAR\r\n";
        $ical .= "VERSION:2.0\r\n";
        $brand = brand_name();
        $host = parse_url((string) config('app.url'), PHP_URL_HOST) ?: 'localhost';

        $ical .= "PRODID:-//{$brand}//Calendar//EN\r\n";
        $ical .= "CALSCALE:GREGORIAN\r\n";
        $ical .= "METHOD:PUBLISH\r\n";
        $ical .= "X-WR-CALNAME:{$brand} Calendar\r\n";
        $ical .= "X-WR-TIMEZONE:UTC\r\n";

        foreach ($events as $event) {
            $ical .= "BEGIN:VEVENT\r\n";
            $ical .= 'UID:'.md5($event->id.'@'.$host)."\r\n";
            $ical .= 'DTSTAMP:'.$event->created_at->format('Ymd\THis\Z')."\r\n";
            $ical .= 'DTSTART:'.$event->start_at->format('Ymd\THis\Z')."\r\n";

            if ($event->end_at) {
                $ical .= 'DTEND:'.$event->end_at->format('Ymd\THis\Z')."\r\n";
            }

            $ical .= 'SUMMARY:'.str_replace(["\r", "\n"], ' ', $event->title)."\r\n";

            if ($event->location) {
                $ical .= 'LOCATION:'.str_replace(["\r", "\n"], ' ', $event->location)."\r\n";
            }

            if ($event->notes) {
                $ical .= 'DESCRIPTION:'.str_replace(["\r", "\n"], ' ', $event->notes)."\r\n";
            }

            $ical .= 'STATUS:'.match ($event->status->value) {
                'scheduled' => 'TENTATIVE',
                'confirmed' => 'CONFIRMED',
                'cancelled' => 'CANCELLED',
                default => 'TENTATIVE',
            }."\r\n";

            $ical .= "END:VEVENT\r\n";
        }

        $ical .= "END:VCALENDAR\r\n";

        return response($ical, 200, [
            'Content-Type' => 'text/calendar; charset=utf-8',
            'Content-Disposition' => 'attachment; filename="calendar.ics"',
        ]);
    }
}
