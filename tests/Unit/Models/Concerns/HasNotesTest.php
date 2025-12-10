<?php

declare(strict_types=1);

use App\Models\Company;
use App\Models\Note;
use App\Models\People;

describe('HasNotes trait', function (): void {
    it('can add notes to a model', function (): void {
        $company = Company::factory()->create();
        $note = Note::factory()->create();

        $company->addNote($note);

        expect($company->notes)->toHaveCount(1);
        expect($company->hasNote($note))->toBeTrue();
    });

    it('can remove notes from a model', function (): void {
        $company = Company::factory()->create();
        $note = Note::factory()->create();

        $company->addNote($note);
        expect($company->notes)->toHaveCount(1);

        $company->removeNote($note);
        expect($company->fresh()->notes)->toHaveCount(0);
        expect($company->hasNote($note))->toBeFalse();
    });

    it('can sync notes on a model', function (): void {
        $company = Company::factory()->create();
        $note1 = Note::factory()->create();
        $note2 = Note::factory()->create();
        $note3 = Note::factory()->create();

        $company->addNote($note1);
        $company->addNote($note2);
        expect($company->fresh()->notes)->toHaveCount(2);

        $company->syncNotes([$note2, $note3]);
        $company->refresh();

        expect($company->notes)->toHaveCount(2);
        expect($company->hasNote($note1))->toBeFalse();
        expect($company->hasNote($note2))->toBeTrue();
        expect($company->hasNote($note3))->toBeTrue();
    });

    it('orders notes by creation date descending', function (): void {
        $company = Company::factory()->create();
        $note1 = Note::factory()->create(['created_at' => now()->subDays(2)]);
        $note2 = Note::factory()->create(['created_at' => now()->subDay()]);
        $note3 = Note::factory()->create(['created_at' => now()]);

        $company->addNote($note1);
        $company->addNote($note2);
        $company->addNote($note3);

        $notes = $company->fresh()->notes;

        expect($notes->first()->id)->toBe($note3->id);
        expect($notes->last()->id)->toBe($note1->id);
    });

    it('can sync notes using note ids', function (): void {
        $company = Company::factory()->create();
        $note1 = Note::factory()->create();
        $note2 = Note::factory()->create();

        $company->syncNotes([$note1->id, $note2->id]);

        expect($company->fresh()->notes)->toHaveCount(2);
        expect($company->hasNote($note1))->toBeTrue();
        expect($company->hasNote($note2))->toBeTrue();
    });

    it('can sync notes with mixed note instances and ids', function (): void {
        $company = Company::factory()->create();
        $note1 = Note::factory()->create();
        $note2 = Note::factory()->create();
        $note3 = Note::factory()->create();

        $company->syncNotes([$note1, $note2->id, $note3]);

        expect($company->fresh()->notes)->toHaveCount(3);
        expect($company->hasNote($note1))->toBeTrue();
        expect($company->hasNote($note2))->toBeTrue();
        expect($company->hasNote($note3))->toBeTrue();
    });

    it('prevents duplicate note attachments', function (): void {
        $company = Company::factory()->create();
        $note = Note::factory()->create();

        $company->addNote($note);
        $company->addNote($note);
        $company->addNote($note);

        expect($company->fresh()->notes)->toHaveCount(1);
    });

    it('can check if model has a specific note', function (): void {
        $company = Company::factory()->create();
        $note1 = Note::factory()->create();
        $note2 = Note::factory()->create();

        $company->addNote($note1);

        expect($company->hasNote($note1))->toBeTrue();
        expect($company->hasNote($note2))->toBeFalse();
    });

    it('can remove a note that is not attached', function (): void {
        $company = Company::factory()->create();
        $note = Note::factory()->create();

        $company->removeNote($note);

        expect($company->fresh()->notes)->toHaveCount(0);
    });

    it('syncs empty array removes all notes', function (): void {
        $company = Company::factory()->create();
        $note1 = Note::factory()->create();
        $note2 = Note::factory()->create();

        $company->addNote($note1);
        $company->addNote($note2);
        expect($company->fresh()->notes)->toHaveCount(2);

        $company->syncNotes([]);

        expect($company->fresh()->notes)->toHaveCount(0);
    });

    it('includes timestamps on pivot table', function (): void {
        $company = Company::factory()->create();
        $note = Note::factory()->create();

        $company->addNote($note);

        $pivot = $company->notes()->first()->pivot;

        expect($pivot->created_at)->not->toBeNull();
        expect($pivot->updated_at)->not->toBeNull();
    });

    it('works with different model types', function (): void {
        $people = People::factory()->create();
        $note = Note::factory()->create();

        $people->addNote($note);

        expect($people->notes)->toHaveCount(1);
        expect($people->hasNote($note))->toBeTrue();
    });

    it('handles multiple models sharing the same note', function (): void {
        $company = Company::factory()->create();
        $people = People::factory()->create();
        $note = Note::factory()->create();

        $company->addNote($note);
        $people->addNote($note);

        expect($company->hasNote($note))->toBeTrue();
        expect($people->hasNote($note))->toBeTrue();
        expect($company->notes)->toHaveCount(1);
        expect($people->notes)->toHaveCount(1);
    });

    it('maintains note relationships after model refresh', function (): void {
        $company = Company::factory()->create();
        $note = Note::factory()->create();

        $company->addNote($note);
        $company->refresh();

        expect($company->notes)->toHaveCount(1);
        expect($company->hasNote($note))->toBeTrue();
    });

    it('can sync notes multiple times', function (): void {
        $company = Company::factory()->create();
        $note1 = Note::factory()->create();
        $note2 = Note::factory()->create();
        $note3 = Note::factory()->create();

        $company->syncNotes([$note1, $note2]);
        expect($company->fresh()->notes)->toHaveCount(2);

        $company->syncNotes([$note2, $note3]);
        expect($company->fresh()->notes)->toHaveCount(2);
        expect($company->hasNote($note1))->toBeFalse();
        expect($company->hasNote($note2))->toBeTrue();
        expect($company->hasNote($note3))->toBeTrue();
    });

    it('handles sync with duplicate ids in array', function (): void {
        $company = Company::factory()->create();
        $note1 = Note::factory()->create();
        $note2 = Note::factory()->create();

        $company->syncNotes([$note1->id, $note1->id, $note2->id, $note2->id]);

        expect($company->fresh()->notes)->toHaveCount(2);
    });

    it('returns correct notes count property', function (): void {
        $company = Company::factory()->create();
        $note1 = Note::factory()->create();
        $note2 = Note::factory()->create();

        $company->addNote($note1);
        $company->addNote($note2);

        $company = Company::withCount('notes')->find($company->id);

        expect($company->notes_count)->toBe(2);
    });

    it('eager loads notes relationship efficiently', function (): void {
        $companies = Company::factory()->count(3)->create();
        $notes = Note::factory()->count(5)->create();

        foreach ($companies as $company) {
            $company->syncNotes($notes->random(2)->all());
        }

        $loadedCompanies = Company::with('notes')->get();

        expect($loadedCompanies)->toHaveCount(3);
        foreach ($loadedCompanies as $company) {
            expect($company->relationLoaded('notes'))->toBeTrue();
        }
    });

    it('filters notes by creation date correctly', function (): void {
        $company = Company::factory()->create();
        $oldNote = Note::factory()->create(['created_at' => now()->subWeek()]);
        $recentNote = Note::factory()->create(['created_at' => now()]);

        $company->addNote($oldNote);
        $company->addNote($recentNote);

        $notes = $company->notes()
            ->wherePivot('created_at', '>=', now()->subDay())
            ->get();

        expect($notes)->toHaveCount(1);
        expect($notes->first()->id)->toBe($recentNote->id);
    });

    it('handles soft deleted notes correctly', function (): void {
        $company = Company::factory()->create();
        $note = Note::factory()->create();

        $company->addNote($note);
        expect($company->notes)->toHaveCount(1);

        $note->delete();

        expect($company->fresh()->notes)->toHaveCount(0);
        expect($company->notes()->withTrashed()->count())->toBe(1);
    });

    it('can detach all notes at once', function (): void {
        $company = Company::factory()->create();
        $notes = Note::factory()->count(5)->create();

        foreach ($notes as $note) {
            $company->addNote($note);
        }

        expect($company->fresh()->notes)->toHaveCount(5);

        $company->notes()->detach();

        expect($company->fresh()->notes)->toHaveCount(0);
    });

    it('maintains correct order when adding notes at different times', function (): void {
        $company = Company::factory()->create();

        $note1 = Note::factory()->create(['created_at' => now()->subHours(3)]);
        $company->addNote($note1);

        \Illuminate\Support\Sleep::sleep(1);

        $note2 = Note::factory()->create(['created_at' => now()->subHours(2)]);
        $company->addNote($note2);

        \Illuminate\Support\Sleep::sleep(1);

        $note3 = Note::factory()->create(['created_at' => now()->subHour()]);
        $company->addNote($note3);

        $notes = $company->fresh()->notes;

        expect($notes->pluck('id')->toArray())->toBe([
            $note3->id,
            $note2->id,
            $note1->id,
        ]);
    });

    it('works correctly with models that have team scoping', function (): void {
        $team = \App\Models\Team::factory()->create();
        $company = Company::factory()->for($team, 'team')->create();
        $note = Note::factory()->for($team, 'team')->create();

        $company->addNote($note);

        expect($company->notes)->toHaveCount(1);
        expect($company->notes->first()->team_id)->toBe($team->id);
    });
});