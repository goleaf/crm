<?php

declare(strict_types=1);

use App\Models\ProductCategory;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('simple category hierarchy test', function (): void {
    $team = Team::factory()->create();
    $user = User::factory()->create();
    $user->teams()->attach($team);

    $this->actingAs($user);

    $parent = ProductCategory::create([
        'team_id' => $team->id,
        'name' => 'Parent Category',
        'sort_order' => 1,
    ]);

    $child = ProductCategory::create([
        'team_id' => $team->id,
        'parent_id' => $parent->id,
        'name' => 'Child Category',
        'sort_order' => 1,
    ]);

    expect($parent->exists())->toBeTrue()
        ->and($child->exists())->toBeTrue()
        ->and($child->parent_id)->toBe($parent->id)
        ->and($parent->children->count())->toBe(1)
        ->and($child->ancestors()->count())->toBe(1);
});
