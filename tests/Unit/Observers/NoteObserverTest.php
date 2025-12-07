<?php

declare(strict_types=1);

use App\Enums\NoteHistoryEvent;
use App\Models\Lead;
use App\Models\Note;
use App\Models\User;

beforeEach(function () {
    $this->user = User::factory()->withPersonalTeam()->create();
    $this->team = $this->user->currentTeam;
});

test('sets creator and team on creating', function () {
    $this->actingAs($this->user);

    $lead = Lead::factory()->create(['team_id' => $this->team->id]);

    $note = Note::factory()->make([
        'creator_id' => null,
        'team_id' => null,
    ]);

    $note->notable()->associate($lead);
    $note->save();

    expect($note->creator_id)->toBe($this->user->id)
        ->and($note->team_id)->toBe($this->team->id);
});

test('records history on created', function () {
    $this->actingAs($this->user);

    $lead = Lead::factory()->create(['team_id' => $this->team->id]);

    $note = Note::factory()->create([
        'notable_type' => Lead::class,
        'notable_id' => $lead->id,
        'team_id' => $this->team->id,
    ]);

    expect($note->histories()->count())->toBe(1)
        ->and($note->histories()->first()->event)->toBe(NoteHistoryEvent::CREATED);
});

test('records history on updated', function () {
    $this->actingAs($this->user);

    $lead = Lead::factory()->create(['team_id' => $this->team->id]);

    $note = Note::factory()->create([
        'notable_type' => Lead::class,
        'notable_id' => $lead->id,
        'team_id' => $this->team->id,
    ]);

    $note->update(['content' => 'Updated content']);

    expect($note->histories()->count())->toBe(2)
        ->and($note->histories()->latest()->first()->event)->toBe(NoteHistoryEvent::UPDATED);
});

test('records history on deleted', function () {
    $this->actingAs($this->user);

    $lead = Lead::factory()->create(['team_id' => $this->team->id]);

    $note = Note::factory()->create([
        'notable_type' => Lead::class,
        'notable_id' => $lead->id,
        'team_id' => $this->team->id,
    ]);

    $noteId = $note->id;
    $note->delete();

    $history = \App\Models\NoteHistory::where('note_id', $noteId)
        ->where('event', NoteHistoryEvent::DELETED)
        ->first();

    expect($history)->not()->toBeNull();
});

test('invalidates related summaries on save', function () {
    $this->actingAs($this->user);

    $lead = Lead::factory()->create(['team_id' => $this->team->id]);

    $lead->aiSummaries()->create([
        'team_id' => $this->team->id,
        'summary' => 'Test summary',
        'model' => 'gpt-4',
    ]);

    $note = Note::factory()->create([
        'notable_type' => Lead::class,
        'notable_id' => $lead->id,
        'team_id' => $this->team->id,
    ]);

    expect($lead->fresh()->aiSummaries()->count())->toBe(0);
});
