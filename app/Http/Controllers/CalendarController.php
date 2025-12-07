<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\CalendarEventStatus;
use App\Enums\CalendarEventType;
use App\Models\CalendarEvent;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

final class CalendarController extends Controller
{
    public function index(Request $request): View
    {
        $team = $request->user()?->currentTeam;

        $events = CalendarEvent::query()
            ->when($team, fn ($query) => $query->whereBelongsTo($team))
            ->orderBy('start_at')
            ->whereDate('start_at', '>=', now()->subDays(1))
            ->limit(50)
            ->get();

        return view('calendar.index', [
            'events' => $events,
            'eventTypes' => CalendarEventType::cases(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $user = $request->user();
        $team = $user?->currentTeam;

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

        CalendarEvent::create([
            ...$validated,
            'team_id' => $team?->getKey(),
            'creator_id' => $user?->getKey(),
            'status' => CalendarEventStatus::SCHEDULED,
        ]);

        return redirect()->route('calendar')->with('status', 'Event scheduled.');
    }
}
