<?php

declare(strict_types=1);

use App\Models\Company;
use App\Models\NotableEntry;
use App\Models\User;

describe('HasNotableEntries trait', function (): void {
    it('creates notables with the parent team and optional creator', function (): void {
        $company = Company::factory()->create();
        $user = User::factory()->create();

        $note = $company->addNotableNote('Followed up with the customer', $user);

        expect($note)->toBeInstanceOf(NotableEntry::class);
        expect($note->note)->toBe('Followed up with the customer');
        expect($note->team_id)->toBe($company->team_id);
        expect($note->creator_type)->toBe($user->getMorphClass());
        expect($note->creator_id)->toBe($user->getKey());
        expect($company->notableNotesCount())->toBe(1);
    });

    it('returns notes in descending order and exposes the latest helper', function (): void {
        $company = Company::factory()->create();

        $first = $company->addNotableNote('First note');
        $second = $company->addNotableNote('Second note');

        $company->notables()->whereKey($first->getKey())->update(['created_at' => now()->subDays(2)]);
        $company->notables()->whereKey($second->getKey())->update(['created_at' => now()->subDay()]);

        $notes = $company->notableNotes();

        expect($notes->first()->id)->toBe($second->id);
        expect($notes->last()->id)->toBe($first->id);
        expect($company->latestNotableNote()?->id)->toBe($second->id);
    });

    it('searches notes by content', function (): void {
        $company = Company::factory()->create();

        $company->addNotableNote('Important call scheduled');
        $company->addNotableNote('Routine check-in');

        $results = $company->searchNotableNotes('call');

        expect($results)->toHaveCount(1);
        expect($results->first()->note)->toContain('call');
    });

    it('filters notes using date helpers', function (): void {
        $company = Company::factory()->create();

        $old = $company->addNotableNote('Old note');
        $recent = $company->addNotableNote('Recent note');

        $company->notables()->whereKey($old->getKey())->update(['created_at' => now()->subDays(10)]);
        $company->notables()->whereKey($recent->getKey())->update(['created_at' => now()->subDay()]);

        expect($company->notableNotesThisWeek()->pluck('id'))->toContain($recent->id);
        expect($company->notableNotesInRange(now()->subDays(2), now())->pluck('id'))->toContain($recent->id);
        expect($company->notableNotesInRange(now()->subDays(2), now())->pluck('id'))->not->toContain($old->id);
    });
});