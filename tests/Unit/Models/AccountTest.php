<?php

declare(strict_types=1);

use App\Models\Account;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('account can link to a parent and expose children', function (): void {
    $parent = Account::factory()->create();
    $child = Account::factory()->create([
        'parent_id' => $parent->getKey(),
    ]);

    expect($child->parent)->not->toBeNull()
        ->and($child->parent->is($parent))->toBeTrue()
        ->and($parent->children->first()?->is($child))->toBeTrue();
});

test('wouldCreateCycle blocks direct and indirect loops', function (): void {
    $root = Account::factory()->create();
    $mid = Account::factory()->create([
        'parent_id' => $root->getKey(),
    ]);
    $leaf = Account::factory()->create([
        'parent_id' => $mid->getKey(),
    ]);

    expect($leaf->wouldCreateCycle($leaf->getKey()))->toBeTrue()
        ->and($root->wouldCreateCycle($leaf->getKey()))->toBeTrue()
        ->and($mid->wouldCreateCycle($root->getKey()))->toBeFalse()
        ->and($leaf->wouldCreateCycle(null))->toBeFalse();
});

test('slug is generated and stays unique when omitted', function (): void {
    $owner = User::factory()->create();
    $team = Team::factory()->create();
    $owner->teams()->syncWithoutDetaching($team);
    $owner->forceFill(['current_team_id' => $team->getKey()])->save();
    $type = array_key_first(config('company.account_types')) ?? 'customer';

    $first = Account::create([
        'name' => 'Acme Corporation',
        'type' => $type,
        'team_id' => $team->getKey(),
        'owner_id' => $owner->getKey(),
    ]);

    $second = Account::create([
        'name' => 'Acme Corporation',
        'type' => $type,
        'team_id' => $team->getKey(),
        'owner_id' => $owner->getKey(),
    ]);

    expect($first->slug)->not->toBeNull()
        ->and($second->slug)->not->toBeNull()
        ->and($first->slug)->not->toBe($second->slug);
});
