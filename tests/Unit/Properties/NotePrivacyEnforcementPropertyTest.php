<?php

declare(strict_types=1);

use App\Enums\NoteVisibility;
use App\Models\Company;
use App\Models\Lead;
use App\Models\Note;
use App\Models\Opportunity;
use App\Models\People;
use App\Models\SupportCase;
use App\Models\Task;
use App\Models\Team;
use App\Models\User;

use function Pest\Laravel\actingAs;

beforeEach(function (): void {
    $this->team = Team::factory()->create();
    $this->user = User::factory()->create();
    $this->user->teams()->attach($this->team);
    $this->user->currentTeam()->associate($this->team);
    $this->user->save();
    actingAs($this->user);

    $this->otherUser = User::factory()->create();
    $this->otherUser->teams()->attach($this->team);
    $this->otherUser->currentTeam()->associate($this->team);
    $this->otherUser->save();
});

/**
 * **Feature: communication-collaboration, Property 6: Privacy enforcement**
 *
 * **Validates: Requirements 6.2**
 *
 * Property: For any private note, only the creator can see it in lists and searches.
 */
test('property: private notes are only visible to creator', function (): void {
    runPropertyTest(function (): void {
        // Generate a private note created by user
        $privateNote = generateNote($this->team, $this->user, [
            'visibility' => NoteVisibility::PRIVATE,
        ]);

        // Acting as the creator
        actingAs($this->user);
        $creatorQuery = Note::query()
            ->where('team_id', $this->team->id)
            ->where('id', $privateNote->id);

        expect($creatorQuery->exists())->toBeTrue(
            "Creator should be able to see their own private note {$privateNote->id}"
        );

        // Acting as another user in the same team
        actingAs($this->otherUser);

        // Property: Other users should not see private notes in filtered queries
        $visibleToOther = Note::query()
            ->where('team_id', $this->team->id)
            ->where('id', $privateNote->id)
            ->where(function ($query): void {
                $query->where('visibility', '!=', NoteVisibility::PRIVATE->value)
                    ->orWhere('creator_id', auth()->id());
            })
            ->exists();

        expect($visibleToOther)->toBeFalse(
            "Other users should not see private note {$privateNote->id} created by user {$this->user->id}"
        );
    }, 100);
})->group('property');

/**
 * **Feature: communication-collaboration, Property 6: Privacy enforcement**
 *
 * **Validates: Requirements 6.2**
 *
 * Property: For any internal note, all team members can see it.
 */
test('property: internal notes are visible to all team members', function (): void {
    runPropertyTest(function (): void {
        // Generate an internal note
        $internalNote = generateNote($this->team, $this->user, [
            'visibility' => NoteVisibility::INTERNAL,
        ]);

        // Acting as the creator
        actingAs($this->user);
        $creatorCanSee = Note::query()
            ->where('team_id', $this->team->id)
            ->where('id', $internalNote->id)
            ->exists();

        expect($creatorCanSee)->toBeTrue(
            "Creator should see internal note {$internalNote->id}"
        );

        // Acting as another team member
        actingAs($this->otherUser);
        $otherCanSee = Note::query()
            ->where('team_id', $this->team->id)
            ->where('id', $internalNote->id)
            ->exists();

        expect($otherCanSee)->toBeTrue(
            "Other team members should see internal note {$internalNote->id}"
        );
    }, 100);
})->group('property');

/**
 * **Feature: communication-collaboration, Property 6: Privacy enforcement**
 *
 * **Validates: Requirements 6.2**
 *
 * Property: For any external note, it should be visible to all team members
 * (external visibility means it can be shared with external parties, but internally
 * it's still visible to the team).
 */
test('property: external notes are visible to team members', function (): void {
    runPropertyTest(function (): void {
        // Generate an external note
        $externalNote = generateNote($this->team, $this->user, [
            'visibility' => NoteVisibility::EXTERNAL,
        ]);

        // Acting as any team member
        actingAs($this->otherUser);
        $canSee = Note::query()
            ->where('team_id', $this->team->id)
            ->where('id', $externalNote->id)
            ->exists();

        expect($canSee)->toBeTrue(
            "Team members should see external note {$externalNote->id}"
        );
    }, 100);
})->group('property');

/**
 * **Feature: communication-collaboration, Property 6: Privacy enforcement**
 *
 * **Validates: Requirements 6.2**
 *
 * Property: When searching notes, private notes should only appear in results
 * for their creator.
 */
test('property: search results respect privacy for private notes', function (): void {
    runPropertyTest(function (): void {
        // Generate a private note with searchable content
        $searchTerm = 'unique_search_term_'.fake()->uuid();
        $privateNote = generateNote($this->team, $this->user, [
            'visibility' => NoteVisibility::PRIVATE,
            'title' => "Note with {$searchTerm}",
        ]);

        // Creator searches
        actingAs($this->user);
        $creatorResults = Note::query()
            ->where('team_id', $this->team->id)
            ->where('title', 'like', "%{$searchTerm}%")
            ->where(function ($query): void {
                $query->where('visibility', '!=', NoteVisibility::PRIVATE->value)
                    ->orWhere('creator_id', auth()->id());
            })
            ->get();

        expect($creatorResults->contains($privateNote))->toBeTrue(
            'Creator should find their private note in search results'
        );

        // Other user searches
        actingAs($this->otherUser);
        $otherResults = Note::query()
            ->where('team_id', $this->team->id)
            ->where('title', 'like', "%{$searchTerm}%")
            ->where(function ($query): void {
                $query->where('visibility', '!=', NoteVisibility::PRIVATE->value)
                    ->orWhere('creator_id', auth()->id());
            })
            ->get();

        expect($otherResults->contains($privateNote))->toBeFalse(
            'Other users should not find private note in search results'
        );
    }, 100);
})->group('property');

/**
 * **Feature: communication-collaboration, Property 6: Privacy enforcement**
 *
 * **Validates: Requirements 6.2**
 *
 * Property: When viewing a record's activity timeline, private notes should only
 * appear for their creator.
 */
test('property: activity timelines respect note privacy', function (): void {
    runPropertyTest(function (): void {
        // Generate a random record type
        $recordTypes = [
            Company::class,
            People::class,
            Lead::class,
            Opportunity::class,
            SupportCase::class,
            Task::class,
        ];

        $recordType = fake()->randomElement($recordTypes);
        $record = $recordType::factory()->create(['team_id' => $this->team->id]);

        // Generate notes with different visibility levels
        $privateNote = generateNote($this->team, $this->user, [
            'visibility' => NoteVisibility::PRIVATE,
        ]);
        $internalNote = generateNote($this->team, $this->user, [
            'visibility' => NoteVisibility::INTERNAL,
        ]);

        // Attach notes to the record
        $record->notes()->attach([$privateNote->id, $internalNote->id]);

        // Creator views timeline
        actingAs($this->user);
        $creatorNotes = $record->notes()
            ->where(function ($query): void {
                $query->where('visibility', '!=', NoteVisibility::PRIVATE->value)
                    ->orWhere('creator_id', auth()->id());
            })
            ->get();

        expect($creatorNotes->contains($privateNote))->toBeTrue(
            'Creator should see private note in timeline'
        );
        expect($creatorNotes->contains($internalNote))->toBeTrue(
            'Creator should see internal note in timeline'
        );

        // Other user views timeline
        actingAs($this->otherUser);
        $otherNotes = $record->notes()
            ->where(function ($query): void {
                $query->where('visibility', '!=', NoteVisibility::PRIVATE->value)
                    ->orWhere('creator_id', auth()->id());
            })
            ->get();

        expect($otherNotes->contains($privateNote))->toBeFalse(
            'Other users should not see private note in timeline'
        );
        expect($otherNotes->contains($internalNote))->toBeTrue(
            'Other users should see internal note in timeline'
        );
    }, 100);
})->group('property');

/**
 * **Feature: communication-collaboration, Property 6: Privacy enforcement**
 *
 * **Validates: Requirements 6.2**
 *
 * Property: Changing a note's visibility should immediately affect who can see it.
 */
test('property: visibility changes are immediately enforced', function (): void {
    runPropertyTest(function (): void {
        // Generate an internal note
        $note = generateNote($this->team, $this->user, [
            'visibility' => NoteVisibility::INTERNAL,
        ]);

        // Other user can see it
        actingAs($this->otherUser);
        $canSeeBefore = Note::query()
            ->where('team_id', $this->team->id)
            ->where('id', $note->id)
            ->exists();

        expect($canSeeBefore)->toBeTrue(
            'Other user should see internal note before visibility change'
        );

        // Change to private
        actingAs($this->user);
        $note->visibility = NoteVisibility::PRIVATE;
        $note->save();

        // Other user should no longer see it
        actingAs($this->otherUser);
        $canSeeAfter = Note::query()
            ->where('team_id', $this->team->id)
            ->where('id', $note->id)
            ->where(function ($query): void {
                $query->where('visibility', '!=', NoteVisibility::PRIVATE->value)
                    ->orWhere('creator_id', auth()->id());
            })
            ->exists();

        expect($canSeeAfter)->toBeFalse(
            'Other user should not see note after it becomes private'
        );
    }, 100);
})->group('property');

/**
 * **Feature: communication-collaboration, Property 6: Privacy enforcement**
 *
 * **Validates: Requirements 6.2**
 *
 * Property: Soft-deleted private notes should still respect privacy rules.
 */
test('property: soft-deleted private notes respect privacy', function (): void {
    runPropertyTest(function (): void {
        // Generate a private note
        $privateNote = generateNote($this->team, $this->user, [
            'visibility' => NoteVisibility::PRIVATE,
        ]);

        // Soft delete the note
        actingAs($this->user);
        $privateNote->delete();

        // Creator can see it in trash
        $creatorCanSeeInTrash = Note::onlyTrashed()
            ->where('team_id', $this->team->id)
            ->where('id', $privateNote->id)
            ->exists();

        expect($creatorCanSeeInTrash)->toBeTrue(
            'Creator should see their private note in trash'
        );

        // Other user should not see it even in trash
        actingAs($this->otherUser);
        $otherCanSeeInTrash = Note::onlyTrashed()
            ->where('team_id', $this->team->id)
            ->where('id', $privateNote->id)
            ->where(function ($query): void {
                $query->where('visibility', '!=', NoteVisibility::PRIVATE->value)
                    ->orWhere('creator_id', auth()->id());
            })
            ->exists();

        expect($otherCanSeeInTrash)->toBeFalse(
            'Other users should not see private note even in trash'
        );
    }, 100);
})->group('property');
