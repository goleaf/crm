<?php

declare(strict_types=1);

use App\Models\Lead;
use App\Models\User;

beforeEach(function (): void {
    $this->user = User::factory()->withPersonalTeam()->create();
    $this->team = $this->user->currentTeam;
});

test('sets creator and team on creating', function (): void {
    $this->actingAs($this->user);

    $lead = Lead::factory()->make([
        'creator_id' => null,
        'team_id' => null,
    ]);

    $lead->save();

    expect($lead->creator_id)->toBe($this->user->id)
        ->and($lead->team_id)->toBe($this->team->id);
});

test('sets order column on creating', function (): void {
    $this->actingAs($this->user);

    $lead = Lead::factory()->create();

    expect($lead->order_column)->not()->toBeNull();
});

test('sets last activity at on creating', function (): void {
    $this->actingAs($this->user);

    $lead = Lead::factory()->create();

    expect($lead->last_activity_at)->not()->toBeNull();
});

test('detects duplicates on save', function (): void {
    $this->actingAs($this->user);

    $original = Lead::factory()->create([
        'email' => 'duplicate@example.com',
        'name' => 'John Doe',
        'team_id' => $this->team->id,
    ]);

    $duplicate = Lead::factory()->create([
        'email' => 'duplicate@example.com',
        'name' => 'John Doe',
        'team_id' => $this->team->id,
    ]);

    expect($duplicate->fresh()->duplicate_of_id)->toBe($original->id)
        ->and($duplicate->fresh()->duplicate_score)->toBeGreaterThan(0);
});

test('clears duplicate score when no duplicates found', function (): void {
    $this->actingAs($this->user);

    $lead = Lead::factory()->create([
        'email' => 'unique@example.com',
        'duplicate_score' => 85.0,
        'team_id' => $this->team->id,
    ]);

    $lead->update(['name' => 'Updated Name']);

    expect($lead->fresh()->duplicate_score)->toBeNull();
});

test('does not check duplicates if marked as duplicate', function (): void {
    $this->actingAs($this->user);

    $original = Lead::factory()->create(['team_id' => $this->team->id]);
    $duplicate = Lead::factory()->create([
        'duplicate_of_id' => $original->id,
        'team_id' => $this->team->id,
    ]);

    $duplicate->update(['name' => 'New Name']);

    // Should not trigger duplicate detection
    expect($duplicate->fresh()->duplicate_of_id)->toBe($original->id);
});