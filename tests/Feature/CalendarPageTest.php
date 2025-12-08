<?php

declare(strict_types=1);

use App\Filament\Pages\Calendar;
use App\Models\CalendarEvent;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('can render calendar page', function (): void {
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $user->teams()->attach($team);
    $user->update(['current_team_id' => $team->id]);

    $this->actingAs($user);

    \Pest\Livewire\livewire(Calendar::class)
        ->assertSuccessful();
});

it('can switch between calendar views', function (): void {
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $user->teams()->attach($team);
    $user->update(['current_team_id' => $team->id]);

    $this->actingAs($user);

    \Pest\Livewire\livewire(Calendar::class)
        ->call('changeView', 'day')
        ->assertSet('view_mode', 'day')
        ->call('changeView', 'week')
        ->assertSet('view_mode', 'week')
        ->call('changeView', 'month')
        ->assertSet('view_mode', 'month')
        ->call('changeView', 'year')
        ->assertSet('view_mode', 'year');
});

it('can navigate between periods', function (): void {
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $user->teams()->attach($team);
    $user->update(['current_team_id' => $team->id]);

    $this->actingAs($user);

    $currentDate = now()->toDateString();

    \Pest\Livewire\livewire(Calendar::class)
        ->assertSet('current_date', $currentDate)
        ->call('nextPeriod')
        ->assertSet('current_date', now()->addMonth()->toDateString())
        ->call('previousPeriod')
        ->assertSet('current_date', $currentDate)
        ->call('today')
        ->assertSet('current_date', now()->toDateString());
});

it('can filter events by type and status', function (): void {
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $user->teams()->attach($team);
    $user->update(['current_team_id' => $team->id]);

    CalendarEvent::factory()->count(5)->create([
        'team_id' => $team->id,
        'creator_id' => $user->id,
    ]);

    $this->actingAs($user);

    \Pest\Livewire\livewire(Calendar::class)
        ->set('filters.types', ['meeting'])
        ->assertSuccessful()
        ->set('filters.statuses', ['scheduled'])
        ->assertSuccessful();
});

it('can search events', function (): void {
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $user->teams()->attach($team);
    $user->update(['current_team_id' => $team->id]);

    CalendarEvent::factory()->create([
        'team_id' => $team->id,
        'creator_id' => $user->id,
        'title' => 'Important Meeting',
    ]);

    CalendarEvent::factory()->create([
        'team_id' => $team->id,
        'creator_id' => $user->id,
        'title' => 'Regular Call',
    ]);

    $this->actingAs($user);

    \Pest\Livewire\livewire(Calendar::class)
        ->set('filters.search', 'Important')
        ->assertSuccessful();
});

it('can toggle team events visibility', function (): void {
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $user->teams()->attach($team);
    $user->update(['current_team_id' => $team->id]);

    $this->actingAs($user);

    \Pest\Livewire\livewire(Calendar::class)
        ->assertSet('filters.show_team_events', true)
        ->set('filters.show_team_events', false)
        ->assertSet('filters.show_team_events', false)
        ->set('filters.show_team_events', true)
        ->assertSet('filters.show_team_events', true);
});

it('can filter by team members', function (): void {
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $user->teams()->attach($team);
    $user->update(['current_team_id' => $team->id]);

    $otherUser = User::factory()->create();
    $otherUser->teams()->attach($team);

    CalendarEvent::factory()->create([
        'team_id' => $team->id,
        'creator_id' => $user->id,
    ]);

    CalendarEvent::factory()->create([
        'team_id' => $team->id,
        'creator_id' => $otherUser->id,
    ]);

    $this->actingAs($user);

    \Pest\Livewire\livewire(Calendar::class)
        ->set('filters.team_members', [$user->id])
        ->assertSuccessful();
});

it('can create event through header action', function (): void {
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $user->teams()->attach($team);
    $user->update(['current_team_id' => $team->id]);

    $this->actingAs($user);

    \Pest\Livewire\livewire(Calendar::class)
        ->callAction('create_event', data: [
            'title' => 'New Meeting',
            'type' => 'meeting',
            'status' => 'scheduled',
            'start_at' => now()->addDay(),
            'end_at' => now()->addDay()->addHour(),
        ])
        ->assertNotified(__('app.messages.event_created'));

    $this->assertDatabaseHas('calendar_events', [
        'title' => 'New Meeting',
        'team_id' => $team->id,
        'creator_id' => $user->id,
    ]);

    $event = CalendarEvent::where('title', 'New Meeting')->first();

    expect($event?->zap_schedule_id)->not->toBeNull();
});

it('validates required fields when creating event', function (): void {
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $user->teams()->attach($team);
    $user->update(['current_team_id' => $team->id]);

    $this->actingAs($user);

    \Pest\Livewire\livewire(Calendar::class)
        ->callAction('create_event', data: [
            'type' => 'meeting',
        ])
        ->assertHasActionErrors(['title', 'start_at']);
});

it('can update event dates', function (): void {
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $user->teams()->attach($team);
    $user->update(['current_team_id' => $team->id]);

    $event = CalendarEvent::factory()->create([
        'team_id' => $team->id,
        'creator_id' => $user->id,
        'start_at' => now(),
        'end_at' => now()->addHour(),
    ]);

    $this->actingAs($user);

    $newStart = now()->addDay()->toIso8601String();
    $newEnd = now()->addDay()->addHours(2)->toIso8601String();

    \Pest\Livewire\livewire(Calendar::class)
        ->call('updateEvent', $event->id, $newStart, $newEnd)
        ->assertNotified(__('app.messages.event_updated'));

    $event->refresh();
    expect($event->start_at->toDateString())->toBe(now()->addDay()->toDateString())
        ->and($event->zap_schedule_id)->not->toBeNull();
});

it('requires authorization to update event', function (): void {
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $user->teams()->attach($team);
    $user->update(['current_team_id' => $team->id]);

    $otherUser = User::factory()->create();
    $otherTeam = Team::factory()->create();
    $otherUser->teams()->attach($otherTeam);

    $event = CalendarEvent::factory()->create([
        'team_id' => $otherTeam->id,
        'creator_id' => $otherUser->id,
    ]);

    $this->actingAs($user);

    \Pest\Livewire\livewire(Calendar::class)
        ->call('updateEvent', $event->id, now()->toIso8601String())
        ->assertForbidden();
});

it('gets team members correctly', function (): void {
    $user = User::factory()->create();
    $team = Team::factory()->create(['user_id' => $user->id]);
    $user->teams()->attach($team);
    $user->update(['current_team_id' => $team->id]);

    $member1 = User::factory()->create();
    $member1->teams()->attach($team);

    $member2 = User::factory()->create();
    $member2->teams()->attach($team);

    $this->actingAs($user);

    $component = \Pest\Livewire\livewire(Calendar::class);
    $members = $component->instance()->getTeamMembers();

    expect($members)->toHaveCount(3)
        ->and($members->pluck('id')->toArray())->toContain($user->id, $member1->id, $member2->id);
});

it('returns empty collection when no team', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user);

    $component = \Pest\Livewire\livewire(Calendar::class);
    $members = $component->instance()->getTeamMembers();

    expect($members)->toBeEmpty();
});

it('filters events by date range in day view', function (): void {
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $user->teams()->attach($team);
    $user->update(['current_team_id' => $team->id]);

    $todayEvent = CalendarEvent::factory()->create([
        'team_id' => $team->id,
        'creator_id' => $user->id,
        'start_at' => now()->setHour(10),
    ]);

    $tomorrowEvent = CalendarEvent::factory()->create([
        'team_id' => $team->id,
        'creator_id' => $user->id,
        'start_at' => now()->addDay()->setHour(10),
    ]);

    $this->actingAs($user);

    $component = \Pest\Livewire\livewire(Calendar::class)
        ->set('view_mode', 'day')
        ->set('current_date', now()->toDateString());

    $events = $component->instance()->getEvents();

    expect($events)->toHaveCount(1)
        ->and($events->first()->id)->toBe($todayEvent->id);
});

it('filters events by search term', function (): void {
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $user->teams()->attach($team);
    $user->update(['current_team_id' => $team->id]);

    $matchingEvent = CalendarEvent::factory()->create([
        'team_id' => $team->id,
        'creator_id' => $user->id,
        'title' => 'Important Meeting',
        'start_at' => now(),
    ]);

    $nonMatchingEvent = CalendarEvent::factory()->create([
        'team_id' => $team->id,
        'creator_id' => $user->id,
        'title' => 'Regular Call',
        'start_at' => now(),
    ]);

    $this->actingAs($user);

    $component = \Pest\Livewire\livewire(Calendar::class)
        ->set('filters.search', 'Important');

    $events = $component->instance()->getEvents();

    expect($events)->toHaveCount(1)
        ->and($events->first()->id)->toBe($matchingEvent->id);
});

it('shows only user events when show_team_events is false', function (): void {
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $user->teams()->attach($team);
    $user->update(['current_team_id' => $team->id]);

    $otherUser = User::factory()->create();
    $otherUser->teams()->attach($team);

    $userEvent = CalendarEvent::factory()->create([
        'team_id' => $team->id,
        'creator_id' => $user->id,
        'start_at' => now(),
    ]);

    $otherEvent = CalendarEvent::factory()->create([
        'team_id' => $team->id,
        'creator_id' => $otherUser->id,
        'start_at' => now(),
    ]);

    $this->actingAs($user);

    $component = \Pest\Livewire\livewire(Calendar::class)
        ->set('filters.show_team_events', false);

    $events = $component->instance()->getEvents();

    expect($events)->toHaveCount(1)
        ->and($events->first()->id)->toBe($userEvent->id);
});

it('navigates periods correctly in different view modes', function (): void {
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $user->teams()->attach($team);
    $user->update(['current_team_id' => $team->id]);

    $this->actingAs($user);

    // Test day navigation
    $component = \Pest\Livewire\livewire(Calendar::class)
        ->set('view_mode', 'day')
        ->set('current_date', '2024-01-15');

    $component->call('nextPeriod');
    expect($component->get('current_date'))->toBe('2024-01-16');

    $component->call('previousPeriod');
    expect($component->get('current_date'))->toBe('2024-01-15');

    // Test week navigation
    $component->set('view_mode', 'week')
        ->set('current_date', '2024-01-15');

    $component->call('nextPeriod');
    expect($component->get('current_date'))->toBe('2024-01-22');

    // Test year navigation
    $component->set('view_mode', 'year')
        ->set('current_date', '2024-01-15');

    $component->call('nextPeriod');
    expect($component->get('current_date'))->toBe('2025-01-15');
});

it('includes attendees in event creation', function (): void {
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $user->teams()->attach($team);
    $user->update(['current_team_id' => $team->id]);

    $this->actingAs($user);

    \Pest\Livewire\livewire(Calendar::class)
        ->callAction('create_event', data: [
            'title' => 'Team Meeting',
            'type' => 'meeting',
            'status' => 'scheduled',
            'start_at' => now()->addDay(),
            'end_at' => now()->addDay()->addHour(),
            'attendees' => [
                ['name' => 'John Doe', 'email' => 'john@example.com'],
                ['name' => 'Jane Smith', 'email' => 'jane@example.com'],
            ],
        ])
        ->assertNotified(__('app.messages.event_created'));

    $event = CalendarEvent::where('title', 'Team Meeting')->first();
    expect($event->attendees)->toHaveCount(2)
        ->and($event->attendees[0]['name'])->toBe('John Doe');
});
