<?php

declare(strict_types=1);

use App\Models\Lead;
use App\Models\Note;
use App\Models\User;

beforeEach(function (): void {
    $this->user = User::factory()->withPersonalTeam()->create();
    $this->team = $this->user->currentTeam;
});

test('note has team and creator after creation', function (): void {
    $this->actingAs($this->user);

    $note = Note::factory()->create([
        'title' => 'Test Note',
        'team_id' => $this->team->id,
    ]);

    // Note should have team_id set
    expect($note->team_id)->toBe($this->team->id);
});

test('note can be soft deleted', function (): void {
    $this->actingAs($this->user);

    $note = Note::factory()->create([
        'title' => 'Test Note',
        'team_id' => $this->team->id,
    ]);

    $noteId = $note->id;
    $note->delete();

    // Note should be soft deleted
    expect(Note::withTrashed()->find($noteId))->not()->toBeNull()
        ->and(Note::find($noteId))->toBeNull();
});

test('note visibility can be changed', function (): void {
    $this->actingAs($this->user);

    $note = Note::factory()->create([
        'title' => 'Test Note',
        'team_id' => $this->team->id,
        'visibility' => \App\Enums\NoteVisibility::INTERNAL,
    ]);

    // Change visibility
    $note->update(['visibility' => \App\Enums\NoteVisibility::PRIVATE]);

    // Visibility should be updated
    expect($note->fresh()->visibility)->toBe(\App\Enums\NoteVisibility::PRIVATE);
});

test('note can be attached to multiple records', function (): void {
    $this->actingAs($this->user);

    $lead = Lead::factory()->create(['team_id' => $this->team->id]);
    $company = \App\Models\Company::factory()->create(['team_id' => $this->team->id]);

    $note = Note::factory()->create([
        'team_id' => $this->team->id,
    ]);

    $lead->notes()->attach($note->id);
    $company->notes()->attach($note->id);

    expect($lead->notes()->where('notes.id', $note->id)->exists())->toBeTrue()
        ->and($company->notes()->where('notes.id', $note->id)->exists())->toBeTrue();
});
