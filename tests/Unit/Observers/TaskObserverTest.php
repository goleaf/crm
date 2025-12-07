<?php

declare(strict_types=1);

use App\Models\Lead;
use App\Models\Task;
use App\Models\User;

beforeEach(function () {
    $this->user = User::factory()->withPersonalTeam()->create();
    $this->team = $this->user->currentTeam;
});

test('sets creator and team on creating', function () {
    $this->actingAs($this->user);

    $lead = Lead::factory()->create(['team_id' => $this->team->id]);

    $task = Task::factory()->make([
        'creator_id' => null,
        'team_id' => null,
    ]);

    $task->taskable()->associate($lead);
    $task->save();

    expect($task->creator_id)->toBe($this->user->id)
        ->and($task->team_id)->toBe($this->team->id);
});

test('does not set creator when not authenticated', function () {
    $lead = Lead::factory()->create();

    $task = Task::factory()->make([
        'creator_id' => null,
        'team_id' => null,
    ]);

    $task->taskable()->associate($lead);
    $task->save();

    expect($task->creator_id)->toBeNull()
        ->and($task->team_id)->toBeNull();
});

test('invalidates related summaries on save', function () {
    $this->actingAs($this->user);

    $lead = Lead::factory()->create(['team_id' => $this->team->id]);

    $lead->aiSummaries()->create([
        'team_id' => $this->team->id,
        'summary' => 'Test summary',
        'model' => 'gpt-4',
    ]);

    $task = Task::factory()->create([
        'taskable_type' => Lead::class,
        'taskable_id' => $lead->id,
        'team_id' => $this->team->id,
    ]);

    expect($lead->fresh()->aiSummaries()->count())->toBe(0);
});

test('invalidates related summaries on delete', function () {
    $this->actingAs($this->user);

    $lead = Lead::factory()->create(['team_id' => $this->team->id]);

    $task = Task::factory()->create([
        'taskable_type' => Lead::class,
        'taskable_id' => $lead->id,
        'team_id' => $this->team->id,
    ]);

    $lead->aiSummaries()->create([
        'team_id' => $this->team->id,
        'summary' => 'Test summary',
        'model' => 'gpt-4',
    ]);

    $task->delete();

    expect($lead->fresh()->aiSummaries()->count())->toBe(0);
});
