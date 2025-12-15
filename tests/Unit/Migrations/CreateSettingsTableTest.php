<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;

uses(RefreshDatabase::class);

it('creates settings table with correct structure', function (): void {
    expect(Schema::hasTable('settings'))->toBeTrue();
});

it('has id column', function (): void {
    expect(Schema::hasColumn('settings', 'id'))->toBeTrue();
});

it('has key column with unique constraint', function (): void {
    expect(Schema::hasColumn('settings', 'key'))->toBeTrue();

    $indexes = Schema::getIndexes('settings');
    $uniqueKeys = array_filter($indexes, fn (array $index): bool => $index['unique'] && in_array('key', $index['columns']));

    expect($uniqueKeys)->not->toBeEmpty();
});

it('has value column', function (): void {
    expect(Schema::hasColumn('settings', 'value'))->toBeTrue();
});

it('has type column with default', function (): void {
    expect(Schema::hasColumn('settings', 'type'))->toBeTrue();
});

it('has group column with default', function (): void {
    expect(Schema::hasColumn('settings', 'group'))->toBeTrue();
});

it('has description column', function (): void {
    expect(Schema::hasColumn('settings', 'description'))->toBeTrue();
});

it('has is_public column with default', function (): void {
    expect(Schema::hasColumn('settings', 'is_public'))->toBeTrue();
});

it('has is_encrypted column with default', function (): void {
    expect(Schema::hasColumn('settings', 'is_encrypted'))->toBeTrue();
});

it('has team_id foreign key column', function (): void {
    expect(Schema::hasColumn('settings', 'team_id'))->toBeTrue();
});

it('has timestamps columns', function (): void {
    expect(Schema::hasColumn('settings', 'created_at'))->toBeTrue()
        ->and(Schema::hasColumn('settings', 'updated_at'))->toBeTrue();
});

it('has composite index on group and key', function (): void {
    $indexes = Schema::getIndexes('settings');
    $compositeIndex = array_filter($indexes, fn (array $index): bool => count($index['columns']) === 2
        && in_array('group', $index['columns'])
        && in_array('key', $index['columns']));

    expect($compositeIndex)->not->toBeEmpty();
});

it('has index on team_id', function (): void {
    $indexes = Schema::getIndexes('settings');
    $teamIdIndex = array_filter($indexes, fn (array $index): bool => in_array('team_id', $index['columns']));

    expect($teamIdIndex)->not->toBeEmpty();
});

it('has foreign key constraint on team_id', function (): void {
    $foreignKeys = Schema::getForeignKeys('settings');
    $teamForeignKey = array_filter($foreignKeys, fn (array $fk): bool => in_array('team_id', $fk['columns']));

    expect($teamForeignKey)->not->toBeEmpty();
});

it('cascades on delete for team_id', function (): void {
    $foreignKeys = Schema::getForeignKeys('settings');
    $teamForeignKey = collect($foreignKeys)->first(fn ($fk): bool => in_array('team_id', $fk['columns']));

    expect($teamForeignKey)->not->toBeNull()
        ->and($teamForeignKey['on_delete'])->toBe('cascade');
});
