<?php

declare(strict_types=1);

use App\Enums\ProductAttributeDataType;
use App\Models\Product;
use App\Models\ProductAttribute;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('simple property test', function (): void {
    $team = Team::factory()->create();
    $user = User::factory()->create();
    $user->teams()->attach($team);
    $user->switchTeam($team);
    $this->actingAs($user);

    $attribute = ProductAttribute::factory()->create([
        'team_id' => $team->id,
        'data_type' => ProductAttributeDataType::TEXT,
    ]);

    $product = Product::factory()->create(['team_id' => $team->id]);

    // Simple test
    expect($attribute->validateValue('test string'))->toBeTrue();
    expect($attribute->validateValue(123))->toBeFalse();
});
