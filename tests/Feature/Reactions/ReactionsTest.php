<?php

declare(strict_types=1);

use App\Models\Note;
use App\Models\User;
use Binafy\LaravelReaction\Enums\LaravelReactionTypeEnum;
use Binafy\LaravelReaction\Models\Reaction;

beforeEach(function (): void {
    $this->user = User::factory()->withPersonalTeam()->create();
    $this->actingAs($this->user);
});

test('users can react to notes and see counts', function (): void {
    $note = Note::factory()->create([
        'team_id' => $this->user->currentTeam->getKey(),
    ]);

    $this->user->reaction(LaravelReactionTypeEnum::REACTION_LIKE, $note);

    $note->refresh();

    $reaction = $note->reactions()->first();

    expect($note->reactions()->count())->toBe(1)
        ->and($note->getReactionsWithCount()->get('like'))->toBe(1)
        ->and($note->isReacted())->toBeTrue()
        ->and($reaction)->toBeInstanceOf(Reaction::class)
        ->and($reaction?->ip)->not()->toBeNull();
});

test('users can update and remove their reactions', function (): void {
    $note = Note::factory()->create([
        'team_id' => $this->user->currentTeam->getKey(),
    ]);

    $this->user->reaction('like', $note);
    $this->user->reaction('love', $note);

    $note->refresh();

    expect($note->reactions()->count())->toBe(1)
        ->and($note->reactions()->first()?->type)->toBe('love');

    $removed = $this->user->removeReaction('love', $note);

    expect($removed)->toBeTrue()
        ->and($note->reactions()->count())->toBe(0);
});
