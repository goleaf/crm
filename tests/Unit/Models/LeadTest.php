<?php

declare(strict_types=1);

use App\Models\Lead;
use App\Models\Note;
use App\Models\Task;
use App\Models\Team;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('builds an activity timeline with related records', function (): void {
    $team = Team::factory()->create();

    $lead = Lead::factory()->create([
        'team_id' => $team->id,
        'qualified_at' => now()->subDay(),
        'converted_at' => now(),
    ]);

    $note = Note::factory()->create(['team_id' => $team->id]);
    $note->leads()->attach($lead);

    $task = Task::factory()->create(['team_id' => $team->id]);
    $task->leads()->attach($lead);

    $types = $lead->getActivityTimeline()->pluck('type');

    expect($types)->toContain('lead')
        ->and($types)->toContain('qualification')
        ->and($types)->toContain('note')
        ->and($types)->toContain('task')
        ->and($types)->toContain('conversion');
});

it('automatically marks duplicates when matching data exists', function (): void {
    $team = Team::factory()->create();

    $original = Lead::factory()->create([
        'team_id' => $team->id,
        'email' => 'dup@example.com',
        'phone' => '+1 555 000 1234',
    ]);

    $duplicate = Lead::factory()->create([
        'team_id' => $team->id,
        'email' => 'dup@example.com',
        'phone' => '+1 (555) 000-1234',
    ])->fresh();

    expect($duplicate->duplicate_of_id)->toBe($original->getKey())
        ->and($duplicate->duplicate_score)->toBeGreaterThanOrEqual(60.0);
});
