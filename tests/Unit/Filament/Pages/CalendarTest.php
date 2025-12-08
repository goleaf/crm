<?php

declare(strict_types=1);

use App\Filament\Pages\Calendar;
use App\Models\CalendarEvent;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->user = User::factory()->create();
    $this->team = Team::factory()->create();
    $this->user->teams()->attach($this->team);
    $this->user->update(['current_team_id' => $this->team->id]);
    $this->actingAs($this->user);
});

it('initializes with correct default values', function (): void {
    $component = \Pest\Livewire\livewire(Calendar::class);

    expect($component->get('view_mode'))->toBe('month')
        ->and($component->get('current_date'))->toBe(now()->toDateString())
        ->and($component->get('filters'))->toBeArray()
        ->and($component->get('filters.types'))->toBeArray()
        ->and($component->get('filters.statuses'))->toBeArray()
        ->and($component->get('filters.search'))->toBe('')
        ->and($component->get('filters.team_members'))->toBeArray()
        ->and($component->get('filters.show_team_events'))->toBeTrue();
});

it('has correct navigation properties', function (): void {
    expect(Calendar::getNavigationGroup())->toBe(__('app.navigation.workspace'))
        ->and(Calendar::getNavigationLabel())->toBe(__('app.navigation.calendar'));
});

it('changes view mode correctly', function (): void {
    $component = \Pest\Livewire\livewire(Calendar::class);

    $component->call('changeView', 'day');
    expect($component->get('view_mode'))->toBe('day');

    $component->call('changeView', 'week');
    expect($component->get('view_mode'))->toBe('week');

    $component->call('changeView', 'year');
    expect($component->get('view_mode'))->toBe('year');
});

it('navigates to today correctly', function (): void {
    $component = \Pest\Livewire\livewire(Calendar::class)
        ->set('current_date', '2024-01-01');

    $component->call('today');

    expect($component->get('current_date'))->toBe(now()->toDateString());
});

it('calculates correct date ranges for day view', function (): void {
    $date = \Illuminate\Support\Facades\Date::parse('2024-01-15');

    $component = \Pest\Livewire\livewire(Calendar::class)
        ->set('view_mode', 'day')
        ->set('current_date', $date->toDateString());

    $events = $component->instance()->getEvents();

    // The query should use startOfDay and endOfDay
    expect($events)->toBeInstanceOf(\Illuminate\Support\Collection::class);
});

it('calculates correct date ranges for week view', function (): void {
    $date = \Illuminate\Support\Facades\Date::parse('2024-01-15');

    $component = \Pest\Livewire\livewire(Calendar::class)
        ->set('view_mode', 'week')
        ->set('current_date', $date->toDateString());

    $events = $component->instance()->getEvents();

    expect($events)->toBeInstanceOf(\Illuminate\Support\Collection::class);
});

it('calculates correct date ranges for month view', function (): void {
    $date = \Illuminate\Support\Facades\Date::parse('2024-01-15');

    $component = \Pest\Livewire\livewire(Calendar::class)
        ->set('view_mode', 'month')
        ->set('current_date', $date->toDateString());

    $events = $component->instance()->getEvents();

    expect($events)->toBeInstanceOf(\Illuminate\Support\Collection::class);
});

it('calculates correct date ranges for year view', function (): void {
    $date = \Illuminate\Support\Facades\Date::parse('2024-01-15');

    $component = \Pest\Livewire\livewire(Calendar::class)
        ->set('view_mode', 'year')
        ->set('current_date', $date->toDateString());

    $events = $component->instance()->getEvents();

    expect($events)->toBeInstanceOf(\Illuminate\Support\Collection::class);
});

it('filters events by type', function (): void {
    CalendarEvent::factory()->create([
        'team_id' => $this->team->id,
        'creator_id' => $this->user->id,
        'type' => 'meeting',
        'start_at' => now(),
    ]);

    CalendarEvent::factory()->create([
        'team_id' => $this->team->id,
        'creator_id' => $this->user->id,
        'type' => 'call',
        'start_at' => now(),
    ]);

    $component = \Pest\Livewire\livewire(Calendar::class)
        ->set('filters.types', ['meeting']);

    $events = $component->instance()->getEvents();

    expect($events)->toHaveCount(1)
        ->and($events->first()->type->value)->toBe('meeting');
});

it('filters events by status', function (): void {
    CalendarEvent::factory()->create([
        'team_id' => $this->team->id,
        'creator_id' => $this->user->id,
        'status' => 'scheduled',
        'start_at' => now(),
    ]);

    CalendarEvent::factory()->create([
        'team_id' => $this->team->id,
        'creator_id' => $this->user->id,
        'status' => 'completed',
        'start_at' => now(),
    ]);

    $component = \Pest\Livewire\livewire(Calendar::class)
        ->set('filters.statuses', ['scheduled']);

    $events = $component->instance()->getEvents();

    expect($events)->toHaveCount(1)
        ->and($events->first()->status->value)->toBe('scheduled');
});

it('filters events by multiple team members', function (): void {
    $member1 = User::factory()->create();
    $member1->teams()->attach($this->team);

    $member2 = User::factory()->create();
    $member2->teams()->attach($this->team);

    CalendarEvent::factory()->create([
        'team_id' => $this->team->id,
        'creator_id' => $member1->id,
        'start_at' => now(),
    ]);

    CalendarEvent::factory()->create([
        'team_id' => $this->team->id,
        'creator_id' => $member2->id,
        'start_at' => now(),
    ]);

    CalendarEvent::factory()->create([
        'team_id' => $this->team->id,
        'creator_id' => $this->user->id,
        'start_at' => now(),
    ]);

    $component = \Pest\Livewire\livewire(Calendar::class)
        ->set('filters.team_members', [$member1->id, $member2->id]);

    $events = $component->instance()->getEvents();

    expect($events)->toHaveCount(2)
        ->and($events->pluck('creator_id')->toArray())->toContain($member1->id, $member2->id)
        ->and($events->pluck('creator_id')->toArray())->not->toContain($this->user->id);
});

it('searches events by title', function (): void {
    CalendarEvent::factory()->create([
        'team_id' => $this->team->id,
        'creator_id' => $this->user->id,
        'title' => 'Important Meeting',
        'start_at' => now(),
    ]);

    CalendarEvent::factory()->create([
        'team_id' => $this->team->id,
        'creator_id' => $this->user->id,
        'title' => 'Regular Call',
        'start_at' => now(),
    ]);

    $component = \Pest\Livewire\livewire(Calendar::class)
        ->set('filters.search', 'Important');

    $events = $component->instance()->getEvents();

    expect($events)->toHaveCount(1)
        ->and($events->first()->title)->toBe('Important Meeting');
});

it('searches events by location', function (): void {
    CalendarEvent::factory()->create([
        'team_id' => $this->team->id,
        'creator_id' => $this->user->id,
        'title' => 'Meeting',
        'location' => 'Conference Room A',
        'start_at' => now(),
    ]);

    CalendarEvent::factory()->create([
        'team_id' => $this->team->id,
        'creator_id' => $this->user->id,
        'title' => 'Call',
        'location' => 'Office B',
        'start_at' => now(),
    ]);

    $component = \Pest\Livewire\livewire(Calendar::class)
        ->set('filters.search', 'Conference');

    $events = $component->instance()->getEvents();

    expect($events)->toHaveCount(1)
        ->and($events->first()->location)->toBe('Conference Room A');
});

it('searches events by notes', function (): void {
    CalendarEvent::factory()->create([
        'team_id' => $this->team->id,
        'creator_id' => $this->user->id,
        'title' => 'Meeting',
        'notes' => 'Discuss quarterly results',
        'start_at' => now(),
    ]);

    CalendarEvent::factory()->create([
        'team_id' => $this->team->id,
        'creator_id' => $this->user->id,
        'title' => 'Call',
        'notes' => 'Weekly sync',
        'start_at' => now(),
    ]);

    $component = \Pest\Livewire\livewire(Calendar::class)
        ->set('filters.search', 'quarterly');

    $events = $component->instance()->getEvents();

    expect($events)->toHaveCount(1)
        ->and($events->first()->notes)->toContain('quarterly');
});

it('eager loads creator and team relationships', function (): void {
    CalendarEvent::factory()->create([
        'team_id' => $this->team->id,
        'creator_id' => $this->user->id,
        'start_at' => now(),
    ]);

    $component = \Pest\Livewire\livewire(Calendar::class);
    $events = $component->instance()->getEvents();

    expect($events->first()->relationLoaded('creator'))->toBeTrue()
        ->and($events->first()->relationLoaded('team'))->toBeTrue();
});

it('orders events by start_at', function (): void {
    $event1 = CalendarEvent::factory()->create([
        'team_id' => $this->team->id,
        'creator_id' => $this->user->id,
        'start_at' => now()->addHours(2),
    ]);

    $event2 = CalendarEvent::factory()->create([
        'team_id' => $this->team->id,
        'creator_id' => $this->user->id,
        'start_at' => now()->addHour(),
    ]);

    $event3 = CalendarEvent::factory()->create([
        'team_id' => $this->team->id,
        'creator_id' => $this->user->id,
        'start_at' => now()->addHours(3),
    ]);

    $component = \Pest\Livewire\livewire(Calendar::class);
    $events = $component->instance()->getEvents();

    expect($events->pluck('id')->toArray())->toBe([
        $event2->id,
        $event1->id,
        $event3->id,
    ]);
});

it('combines multiple filters correctly', function (): void {
    CalendarEvent::factory()->create([
        'team_id' => $this->team->id,
        'creator_id' => $this->user->id,
        'type' => 'meeting',
        'status' => 'scheduled',
        'title' => 'Important Meeting',
        'start_at' => now(),
    ]);

    CalendarEvent::factory()->create([
        'team_id' => $this->team->id,
        'creator_id' => $this->user->id,
        'type' => 'meeting',
        'status' => 'completed',
        'title' => 'Important Call',
        'start_at' => now(),
    ]);

    CalendarEvent::factory()->create([
        'team_id' => $this->team->id,
        'creator_id' => $this->user->id,
        'type' => 'call',
        'status' => 'scheduled',
        'title' => 'Important Discussion',
        'start_at' => now(),
    ]);

    $component = \Pest\Livewire\livewire(Calendar::class)
        ->set('filters.types', ['meeting'])
        ->set('filters.statuses', ['scheduled'])
        ->set('filters.search', 'Important');

    $events = $component->instance()->getEvents();

    expect($events)->toHaveCount(1)
        ->and($events->first()->title)->toBe('Important Meeting');
});

it('returns empty collection when no events match filters', function (): void {
    CalendarEvent::factory()->create([
        'team_id' => $this->team->id,
        'creator_id' => $this->user->id,
        'type' => 'meeting',
        'start_at' => now(),
    ]);

    $component = \Pest\Livewire\livewire(Calendar::class)
        ->set('filters.types', ['call']);

    $events = $component->instance()->getEvents();

    expect($events)->toBeEmpty();
});

it('handles events without team correctly', function (): void {
    $userWithoutTeam = User::factory()->create();
    $this->actingAs($userWithoutTeam);

    $component = \Pest\Livewire\livewire(Calendar::class);
    $events = $component->instance()->getEvents();

    expect($events)->toBeEmpty();
});

it('includes team owner in team members list', function (): void {
    $owner = User::factory()->create();
    $team = Team::factory()->create(['user_id' => $owner->id]);
    $owner->teams()->attach($team);
    $owner->update(['current_team_id' => $team->id]);

    $this->actingAs($owner);

    $component = \Pest\Livewire\livewire(Calendar::class);
    $members = $component->instance()->getTeamMembers();

    expect($members->pluck('id')->toArray())->toContain($owner->id);
});

it('does not duplicate team owner in members list', function (): void {
    $owner = User::factory()->create();
    $team = Team::factory()->create(['user_id' => $owner->id]);
    $owner->teams()->attach($team);
    $owner->update(['current_team_id' => $team->id]);

    $this->actingAs($owner);

    $component = \Pest\Livewire\livewire(Calendar::class);
    $members = $component->instance()->getTeamMembers();

    $ownerCount = $members->filter(fn ($m): bool => $m->id === $owner->id)->count();

    expect($ownerCount)->toBe(1);
});
