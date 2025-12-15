<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;

uses(RefreshDatabase::class);

/**
 * Tests for Union Paginator Indexes Migration
 *
 * Verifies that database indexes are properly created for union query performance.
 */
describe('Union Paginator Indexes Migration', function (): void {
    it('creates composite index on tasks for team and created_at', function (): void {
        $indexes = Schema::getIndexes('tasks');
        $targetIndex = collect($indexes)->first(fn (array $index): bool => $index['name'] === 'idx_tasks_team_created');

        expect($targetIndex)->not->toBeNull();
        expect($targetIndex['columns'])->toContain('team_id');
        expect($targetIndex['columns'])->toContain('created_at');
    });

    it('creates index on tasks for creator_id', function (): void {
        $indexes = Schema::getIndexes('tasks');
        $creatorIndex = collect($indexes)->first(fn (array $index): bool => in_array('creator_id', $index['columns']));

        expect($creatorIndex)->not->toBeNull();
    });

    it('creates composite index on notes for team and created_at', function (): void {
        $indexes = Schema::getIndexes('notes');
        $targetIndex = collect($indexes)->first(fn (array $index): bool => $index['name'] === 'idx_notes_team_created');

        expect($targetIndex)->not->toBeNull();
        expect($targetIndex['columns'])->toContain('team_id');
        expect($targetIndex['columns'])->toContain('created_at');
    });

    it('creates index on notes for creator_id', function (): void {
        $indexes = Schema::getIndexes('notes');
        $creatorIndex = collect($indexes)->first(fn (array $index): bool => in_array('creator_id', $index['columns']));

        expect($creatorIndex)->not->toBeNull();
    });

    it('creates polymorphic index on notes for notable', function (): void {
        $indexes = Schema::getIndexes('notes');
        $notableIndex = collect($indexes)->first(fn (array $index): bool => $index['name'] === 'idx_notes_notable');

        expect($notableIndex)->not->toBeNull();
        expect($notableIndex['columns'])->toContain('notable_type');
        expect($notableIndex['columns'])->toContain('notable_id');
    });

    it('creates composite index on opportunities for team and created_at', function (): void {
        $indexes = Schema::getIndexes('opportunities');
        $targetIndex = collect($indexes)->first(fn (array $index): bool => $index['name'] === 'idx_opportunities_team_created');

        expect($targetIndex)->not->toBeNull();
        expect($targetIndex['columns'])->toContain('team_id');
        expect($targetIndex['columns'])->toContain('created_at');
    });

    it('creates index on opportunities for creator_id', function (): void {
        $indexes = Schema::getIndexes('opportunities');
        $creatorIndex = collect($indexes)->first(fn (array $index): bool => in_array('creator_id', $index['columns']));

        expect($creatorIndex)->not->toBeNull();
    });

    it('creates composite index on cases for team and created_at', function (): void {
        $indexes = Schema::getIndexes('cases');
        $targetIndex = collect($indexes)->first(fn (array $index): bool => $index['name'] === 'idx_cases_team_created');

        expect($targetIndex)->not->toBeNull();
        expect($targetIndex['columns'])->toContain('team_id');
        expect($targetIndex['columns'])->toContain('created_at');
    });

    it('creates index on cases for creator_id', function (): void {
        $indexes = Schema::getIndexes('cases');
        $creatorIndex = collect($indexes)->first(fn (array $index): bool => in_array('creator_id', $index['columns']));

        expect($creatorIndex)->not->toBeNull();
    });

    it('creates composite index on companies for team and name', function (): void {
        $indexes = Schema::getIndexes('companies');
        $targetIndex = collect($indexes)->first(fn (array $index): bool => $index['name'] === 'idx_companies_team_name');

        expect($targetIndex)->not->toBeNull();
        expect($targetIndex['columns'])->toContain('team_id');
        expect($targetIndex['columns'])->toContain('name');
    });

    it('creates index on companies for email', function (): void {
        $indexes = Schema::getIndexes('companies');
        $emailIndex = collect($indexes)->first(fn (array $index): bool => in_array('email', $index['columns']));

        expect($emailIndex)->not->toBeNull();
    });

    it('creates composite index on people for team and name', function (): void {
        $indexes = Schema::getIndexes('people');
        $targetIndex = collect($indexes)->first(fn (array $index): bool => $index['name'] === 'idx_people_team_name');

        expect($targetIndex)->not->toBeNull();
        expect($targetIndex['columns'])->toContain('team_id');
        expect($targetIndex['columns'])->toContain('name');
    });

    it('creates index on people for email', function (): void {
        $indexes = Schema::getIndexes('people');
        $emailIndex = collect($indexes)->first(fn (array $index): bool => in_array('email', $index['columns']));

        expect($emailIndex)->not->toBeNull();
    });

    it('verifies all indexed tables exist', function (): void {
        expect(Schema::hasTable('tasks'))->toBeTrue();
        expect(Schema::hasTable('notes'))->toBeTrue();
        expect(Schema::hasTable('opportunities'))->toBeTrue();
        expect(Schema::hasTable('cases'))->toBeTrue();
        expect(Schema::hasTable('companies'))->toBeTrue();
        expect(Schema::hasTable('people'))->toBeTrue();
    });

    it('verifies all indexed columns exist on tasks', function (): void {
        expect(Schema::hasColumn('tasks', 'team_id'))->toBeTrue();
        expect(Schema::hasColumn('tasks', 'created_at'))->toBeTrue();
        expect(Schema::hasColumn('tasks', 'creator_id'))->toBeTrue();
    });

    it('verifies all indexed columns exist on notes', function (): void {
        expect(Schema::hasColumn('notes', 'team_id'))->toBeTrue();
        expect(Schema::hasColumn('notes', 'created_at'))->toBeTrue();
        expect(Schema::hasColumn('notes', 'creator_id'))->toBeTrue();
        expect(Schema::hasColumn('notes', 'notable_type'))->toBeTrue();
        expect(Schema::hasColumn('notes', 'notable_id'))->toBeTrue();
    });

    it('verifies all indexed columns exist on opportunities', function (): void {
        expect(Schema::hasColumn('opportunities', 'team_id'))->toBeTrue();
        expect(Schema::hasColumn('opportunities', 'created_at'))->toBeTrue();
        expect(Schema::hasColumn('opportunities', 'creator_id'))->toBeTrue();
    });

    it('verifies all indexed columns exist on cases', function (): void {
        expect(Schema::hasColumn('cases', 'team_id'))->toBeTrue();
        expect(Schema::hasColumn('cases', 'created_at'))->toBeTrue();
        expect(Schema::hasColumn('cases', 'creator_id'))->toBeTrue();
    });

    it('verifies all indexed columns exist on companies', function (): void {
        expect(Schema::hasColumn('companies', 'team_id'))->toBeTrue();
        expect(Schema::hasColumn('companies', 'name'))->toBeTrue();
        expect(Schema::hasColumn('companies', 'email'))->toBeTrue();
    });

    it('verifies all indexed columns exist on people', function (): void {
        expect(Schema::hasColumn('people', 'team_id'))->toBeTrue();
        expect(Schema::hasColumn('people', 'name'))->toBeTrue();
        expect(Schema::hasColumn('people', 'email'))->toBeTrue();
    });
});
