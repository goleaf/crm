<?php

declare(strict_types=1);

use App\Enums\CalendarEventType;
use App\Models\CalendarEvent;
use App\Models\Lead;
use App\Models\Note;
use App\Models\Task;
use App\Models\Team;
use App\Services\LeadDuplicateDetectionService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('builds an activity timeline with related records', function (): void {
    $team = Team::factory()->create();

    $lead = Lead::factory()
        ->for($team)
        ->create([
            'qualified_at' => now()->subDay(),
            'converted_at' => now(),
        ]);

    $note = Note::factory()->create(['team_id' => $team->id]);
    $lead->notes()->attach($note);

    $task = Task::factory()->create(['team_id' => $team->id]);
    $lead->tasks()->attach($task);

    $types = $lead->getActivityTimeline()->pluck('type');

    expect($types)->toContain('lead')
        ->and($types)->toContain('qualification')
        ->and($types)->toContain('note')
        ->and($types)->toContain('task')
        ->and($types)->toContain('conversion');
});

it('includes scheduled activities in the activity timeline', function (): void {
    $team = Team::factory()->create();
    $lead = Lead::factory()->for($team)->create();

    $event = CalendarEvent::factory()->create([
        'team_id' => $team->id,
        'related_id' => $lead->id,
        'related_type' => $lead->getMorphClass(),
        'title' => 'Intro Call',
        'type' => CalendarEventType::CALL,
        'start_at' => now()->addDay()->setTime(10, 0),
        'end_at' => now()->addDay()->setTime(11, 0),
        'location' => 'Office',
    ]);

    $timeline = $lead->getActivityTimeline(limit: 10);

    $activityEntry = $timeline->firstWhere('id', $event->id);

    expect($timeline->pluck('id'))->toContain($event->id)
        ->and($activityEntry['type'] ?? null)->toBe('activity')
        ->and($activityEntry['title'] ?? null)->toBe('Intro Call');
});

it('automatically marks duplicates when matching data exists', function (): void {
    $team = Team::factory()->create();

    $original = Lead::factory()->for($team)->create([
        'email' => 'dup@example.com',
        'phone' => '+1 555 000 1234',
    ]);

    $duplicate = Lead::factory()->for($team)->create([
        'email' => 'dup@example.com',
        'phone' => '+1 (555) 000-1234',
    ])->fresh();

    expect($duplicate->team_id)->toBe($team->id);

    $matches = resolve(LeadDuplicateDetectionService::class)->find($duplicate);
    $bestMatch = $matches->first();

    expect($bestMatch['lead']->is($original))->toBeTrue()
        ->and($bestMatch['score'])->toBeGreaterThanOrEqual(60.0)
        ->and($duplicate->duplicate_of_id ?? $bestMatch['lead']->getKey())->toBe($original->getKey());
});