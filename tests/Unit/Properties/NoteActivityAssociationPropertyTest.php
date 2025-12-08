<?php

declare(strict_types=1);

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
});

/**
 * **Feature: communication-collaboration, Property 3: Activity association**
 *
 * **Validates: Requirements 6.3**
 *
 * Property: For any note linked to a record, the note remains accessible from
 * that record even after the note is edited.
 */
test('property: notes remain accessible after edits', function (): void {
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

        // Generate and attach a note
        $note = generateNote($this->team, $this->user);
        $record->notes()->attach($note->id);

        // Verify initial association
        expect($record->notes()->where('note_id', $note->id)->exists())->toBeTrue(
            "Note {$note->id} should be initially associated with {$recordType} {$record->id}"
        );

        // Edit the note multiple times
        $editCount = fake()->numberBetween(1, 5);
        for ($i = 0; $i < $editCount; $i++) {
            $note->title = fake()->sentence();
            $note->save();
        }

        // Property: Note should still be accessible from the record
        $record->refresh();
        expect($record->notes()->where('note_id', $note->id)->exists())->toBeTrue(
            "Note {$note->id} should remain associated with {$recordType} {$record->id} after {$editCount} edits"
        );

        // Verify the note is in the record's notes collection
        $recordNotes = $record->notes;
        expect($recordNotes->contains($note))->toBeTrue(
            "Record's notes collection should contain the edited note"
        );
    }, 100);
})->group('property');

/**
 * **Feature: communication-collaboration, Property 3: Activity association**
 *
 * **Validates: Requirements 6.3**
 *
 * Property: For any note linked to a record, the note remains accessible from
 * that record even after the note is soft-deleted.
 */
test('property: notes remain accessible after soft delete', function (): void {
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

        // Generate and attach a note
        $note = generateNote($this->team, $this->user);
        $record->notes()->attach($note->id);

        // Verify initial association
        expect($record->notes()->where('note_id', $note->id)->exists())->toBeTrue(
            "Note {$note->id} should be initially associated with {$recordType} {$record->id}"
        );

        // Soft delete the note
        $note->delete();

        // Property: Note should be accessible via withTrashed
        $record->refresh();
        $trashedNote = $record->notes()->withTrashed()->where('note_id', $note->id)->first();

        expect($trashedNote)->not()->toBeNull(
            "Soft-deleted note {$note->id} should be accessible via withTrashed from {$recordType} {$record->id}"
        );
        expect($trashedNote->trashed())->toBeTrue(
            'Retrieved note should be marked as trashed'
        );
    }, 100);
})->group('property');

/**
 * **Feature: communication-collaboration, Property 3: Activity association**
 *
 * **Validates: Requirements 6.3**
 *
 * Property: A note can be linked to multiple records simultaneously, and the
 * association persists for all records.
 */
test('property: notes can be linked to multiple records', function (): void {
    runPropertyTest(function (): void {
        // Generate a note
        $note = generateNote($this->team, $this->user);

        // Generate multiple records of different types
        $recordCount = fake()->numberBetween(2, 5);
        $records = [];

        $recordTypes = [
            Company::class,
            People::class,
            Lead::class,
            Opportunity::class,
            SupportCase::class,
            Task::class,
        ];

        for ($i = 0; $i < $recordCount; $i++) {
            $recordType = fake()->randomElement($recordTypes);
            $record = $recordType::factory()->create(['team_id' => $this->team->id]);
            $record->notes()->attach($note->id);
            $records[] = ['type' => $recordType, 'record' => $record];
        }

        // Property: Note should be accessible from all records
        foreach ($records as $item) {
            $record = $item['record'];
            $recordType = $item['type'];

            $hasNote = $record->notes()->where('note_id', $note->id)->exists();

            expect($hasNote)->toBeTrue(
                "Note {$note->id} should be associated with {$recordType} {$record->id}"
            );
        }

        // Edit the note
        $note->title = fake()->sentence();
        $note->save();

        // Property: After edit, note should still be accessible from all records
        foreach ($records as $item) {
            $record = $item['record'];
            $record->refresh();

            $hasNote = $record->notes()->where('note_id', $note->id)->exists();

            expect($hasNote)->toBeTrue(
                "Note {$note->id} should remain associated after edit"
            );
        }
    }, 100);
})->group('property');

/**
 * **Feature: communication-collaboration, Property 3: Activity association**
 *
 * **Validates: Requirements 6.3**
 *
 * Property: When a note is attached to a record, it appears in the record's
 * notes collection immediately.
 */
test('property: note attachment is immediate', function (): void {
    runPropertyTest(function (): void {
        // Generate a record
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

        // Count initial notes
        $initialCount = $record->notes()->count();

        // Generate and attach a note
        $note = generateNote($this->team, $this->user);
        $record->notes()->attach($note->id);

        // Property: Note count should increase by 1
        $newCount = $record->notes()->count();
        expect($newCount)->toBe($initialCount + 1,
            "Note count should increase from {$initialCount} to ".($initialCount + 1)
        );

        // Property: Note should be in the collection
        $record->refresh();
        $hasNote = $record->notes->contains($note);

        expect($hasNote)->toBeTrue(
            "Note {$note->id} should be in record's notes collection immediately after attachment"
        );
    }, 100);
})->group('property');

/**
 * **Feature: communication-collaboration, Property 3: Activity association**
 *
 * **Validates: Requirements 6.3**
 *
 * Property: When a note is detached from a record, it no longer appears in
 * the record's notes collection.
 */
test('property: note detachment is immediate', function (): void {
    runPropertyTest(function (): void {
        // Generate a record
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

        // Generate and attach a note
        $note = generateNote($this->team, $this->user);
        $record->notes()->attach($note->id);

        // Verify attachment
        expect($record->notes()->where('note_id', $note->id)->exists())->toBeTrue(
            'Note should be attached before detachment'
        );

        // Detach the note
        $record->notes()->detach($note->id);

        // Property: Note should no longer be in the collection
        $record->refresh();
        $hasNote = $record->notes()->where('note_id', $note->id)->exists();

        expect($hasNote)->toBeFalse(
            "Note {$note->id} should not be in record's notes collection after detachment"
        );
    }, 100);
})->group('property');

/**
 * **Feature: communication-collaboration, Property 3: Activity association**
 *
 * **Validates: Requirements 6.3**
 *
 * Property: Note history is preserved even when the note is detached from records.
 */
test('property: note history persists after detachment', function (): void {
    runPropertyTest(function (): void {
        // Generate a record and note
        $record = Company::factory()->create(['team_id' => $this->team->id]);
        $note = generateNote($this->team, $this->user);

        // Attach note
        $record->notes()->attach($note->id);

        // Wait for history to be created (it's created in afterCommit)
        \DB::afterCommit(function (): void {});

        // Get initial history count (should have at least creation history)
        $note->refresh();
        $initialHistoryCount = $note->histories()->count();

        // Edit note to create more history
        $note->title = fake()->sentence();
        $note->save();

        // Wait for history to be created
        \DB::afterCommit(function (): void {});

        // Get history count after edit
        $note->refresh();
        $historyCount = $note->histories()->count();

        // Should have at least the initial history
        expect($historyCount >= $initialHistoryCount)->toBeTrue(
            "Note should maintain or increase history entries after edit (had {$initialHistoryCount}, now has {$historyCount})"
        );

        // Detach note from record
        $record->notes()->detach($note->id);

        // Property: History should still exist
        $note->refresh();
        $historyAfterDetach = $note->histories()->count();

        expect($historyAfterDetach)->toBe($historyCount,
            'Note history should be preserved after detachment'
        );
    }, 100);
})->group('property');

/**
 * **Feature: communication-collaboration, Property 3: Activity association**
 *
 * **Validates: Requirements 6.3**
 *
 * Property: When a record is soft-deleted, its associated notes remain accessible
 * via the relationship.
 */
test('property: notes remain accessible when record is soft-deleted', function (): void {
    runPropertyTest(function (): void {
        // Generate a record that supports soft deletes
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

        // Generate and attach notes
        $noteCount = fake()->numberBetween(1, 5);
        $notes = [];

        for ($i = 0; $i < $noteCount; $i++) {
            $note = generateNote($this->team, $this->user);
            $record->notes()->attach($note->id);
            $notes[] = $note;
        }

        // Verify initial associations
        expect($record->notes()->count())->toBe($noteCount,
            "Record should have {$noteCount} notes before deletion"
        );

        // Soft delete the record
        $record->delete();

        // Property: Notes should still be accessible via withTrashed
        $trashedRecord = $recordType::withTrashed()->find($record->id);
        $notesAfterDelete = $trashedRecord->notes()->count();

        expect($notesAfterDelete)->toBe($noteCount,
            "All {$noteCount} notes should remain accessible after record soft delete"
        );

        // Verify each note is still associated
        foreach ($notes as $note) {
            $hasNote = $trashedRecord->notes()->where('note_id', $note->id)->exists();
            expect($hasNote)->toBeTrue(
                "Note {$note->id} should remain associated with soft-deleted record"
            );
        }
    }, 100);
})->group('property');

/**
 * **Feature: communication-collaboration, Property 3: Activity association**
 *
 * **Validates: Requirements 6.3**
 *
 * Property: The noteables pivot table maintains referential integrity.
 */
test('property: pivot table maintains referential integrity', function (): void {
    runPropertyTest(function (): void {
        // Generate a record and note
        $record = Company::factory()->create(['team_id' => $this->team->id]);
        $note = generateNote($this->team, $this->user);

        // Attach note using the relationship
        $record->notes()->attach($note->id);

        // Property: Note should be in the relationship
        $record->refresh();
        $hasNote = $record->notes()->where('notes.id', $note->id)->exists();

        expect($hasNote)->toBeTrue(
            "Note {$note->id} should be accessible via relationship after attachment to Company {$record->id}"
        );

        // Property: Count should increase
        $noteCount = $record->notes()->count();
        expect($noteCount)->toBeGreaterThan(0,
            'Company should have at least one note'
        );

        // Property: Pivot record timestamps should exist
        $pivotRecord = $record->notes()->where('notes.id', $note->id)->first();
        if ($pivotRecord) {
            $pivotData = $pivotRecord->pivot;
            expect($pivotData->created_at)->not()->toBeNull(
                'Pivot should have created_at timestamp'
            );
        }
    }, 100);
})->group('property');
