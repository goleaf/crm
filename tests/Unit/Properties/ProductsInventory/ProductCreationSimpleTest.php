<?php

declare(strict_types=1);

use App\Models\Product;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('simple product creation test', function (): void {
    $team = Team::factory()->create();
    $user = User::factory()->create();
    $user->teams()->attach($team);

    $this->actingAs($user);

    $product = Product::factory()->create([
        'team_id' => $team->id,
        'name' => 'Test Product',
    ]);

    expect($product->exists())->toBeTrue()
        ->and($product->name)->toBe('Test Product')
        ->and($product->team_id)->toBe($team->id);
});
