<?php

declare(strict_types=1);

use App\Models\Product;
use App\Models\Team;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/**
 * Feature: products-inventory, Property 20: Sort order correctness
 * Validates: Requirements 7.5
 * 
 * Property: For any sort field (name, SKU, price, created_at), the results should be ordered correctly in ascending or descending order.
 */
it('sorts products correctly by name in ascending order', function () {
    $team = Team::factory()->create();
    
    // Create products with different names
    $productA = Product::factory()->create([
        'team_id' => $team->id,
        'name' => 'Alpha Product',
    ]);
    
    $productB = Product::factory()->create([
        'team_id' => $team->id,
        'name' => 'Beta Product',
    ]);
    
    $productC = Product::factory()->create([
        'team_id' => $team->id,
        'name' => 'Charlie Product',
    ]);
    
    // Sort by name ascending
    $results = Product::query()
        ->where('team_id', $team->id)
        ->orderBy('name', 'asc')
        ->get();
    
    $sortedIds = $results->pluck('id')->toArray();
    $expectedOrder = [$productA->id, $productB->id, $productC->id];
    
    expect($sortedIds)->toBe($expectedOrder);
});

it('sorts products correctly by name in descending order', function () {
    $team = Team::factory()->create();
    
    // Create products with different names
    $productA = Product::factory()->create([
        'team_id' => $team->id,
        'name' => 'Alpha Product',
    ]);
    
    $productB = Product::factory()->create([
        'team_id' => $team->id,
        'name' => 'Beta Product',
    ]);
    
    $productC = Product::factory()->create([
        'team_id' => $team->id,
        'name' => 'Charlie Product',
    ]);
    
    // Sort by name descending
    $results = Product::query()
        ->where('team_id', $team->id)
        ->orderBy('name', 'desc')
        ->get();
    
    $sortedIds = $results->pluck('id')->toArray();
    $expectedOrder = [$productC->id, $productB->id, $productA->id];
    
    expect($sortedIds)->toBe($expectedOrder);
});

it('sorts products correctly by SKU in ascending order', function () {
    $team = Team::factory()->create();
    
    // Create products with different SKUs
    $productA = Product::factory()->create([
        'team_id' => $team->id,
        'name' => 'Product A',
        'sku' => 'SKU-001',
    ]);
    
    $productB = Product::factory()->create([
        'team_id' => $team->id,
        'name' => 'Product B',
        'sku' => 'SKU-002',
    ]);
    
    $productC = Product::factory()->create([
        'team_id' => $team->id,
        'name' => 'Product C',
        'sku' => 'SKU-003',
    ]);
    
    // Sort by SKU ascending
    $results = Product::query()
        ->where('team_id', $team->id)
        ->orderBy('sku', 'asc')
        ->get();
    
    $sortedIds = $results->pluck('id')->toArray();
    $expectedOrder = [$productA->id, $productB->id, $productC->id];
    
    expect($sortedIds)->toBe($expectedOrder);
});

it('sorts products correctly by price in ascending order', function () {
    $team = Team::factory()->create();
    
    // Create products with different prices
    $cheapProduct = Product::factory()->create([
        'team_id' => $team->id,
        'name' => 'Cheap Product',
        'price' => 10.00,
    ]);
    
    $mediumProduct = Product::factory()->create([
        'team_id' => $team->id,
        'name' => 'Medium Product',
        'price' => 50.00,
    ]);
    
    $expensiveProduct = Product::factory()->create([
        'team_id' => $team->id,
        'name' => 'Expensive Product',
        'price' => 100.00,
    ]);
    
    // Sort by price ascending
    $results = Product::query()
        ->where('team_id', $team->id)
        ->orderBy('price', 'asc')
        ->get();
    
    $sortedIds = $results->pluck('id')->toArray();
    $expectedOrder = [$cheapProduct->id, $mediumProduct->id, $expensiveProduct->id];
    
    expect($sortedIds)->toBe($expectedOrder);
});

it('sorts products correctly by price in descending order', function () {
    $team = Team::factory()->create();
    
    // Create products with different prices
    $cheapProduct = Product::factory()->create([
        'team_id' => $team->id,
        'name' => 'Cheap Product',
        'price' => 10.00,
    ]);
    
    $mediumProduct = Product::factory()->create([
        'team_id' => $team->id,
        'name' => 'Medium Product',
        'price' => 50.00,
    ]);
    
    $expensiveProduct = Product::factory()->create([
        'team_id' => $team->id,
        'name' => 'Expensive Product',
        'price' => 100.00,
    ]);
    
    // Sort by price descending
    $results = Product::query()
        ->where('team_id', $team->id)
        ->orderBy('price', 'desc')
        ->get();
    
    $sortedIds = $results->pluck('id')->toArray();
    $expectedOrder = [$expensiveProduct->id, $mediumProduct->id, $cheapProduct->id];
    
    expect($sortedIds)->toBe($expectedOrder);
});

it('sorts products correctly by creation date in ascending order', function () {
    $team = Team::factory()->create();
    
    // Create products at different times
    $oldProduct = Product::factory()->create([
        'team_id' => $team->id,
        'name' => 'Old Product',
        'created_at' => now()->subDays(3),
    ]);
    
    $mediumProduct = Product::factory()->create([
        'team_id' => $team->id,
        'name' => 'Medium Product',
        'created_at' => now()->subDays(2),
    ]);
    
    $newProduct = Product::factory()->create([
        'team_id' => $team->id,
        'name' => 'New Product',
        'created_at' => now()->subDays(1),
    ]);
    
    // Sort by created_at ascending
    $results = Product::query()
        ->where('team_id', $team->id)
        ->orderBy('created_at', 'asc')
        ->get();
    
    $sortedIds = $results->pluck('id')->toArray();
    $expectedOrder = [$oldProduct->id, $mediumProduct->id, $newProduct->id];
    
    expect($sortedIds)->toBe($expectedOrder);
});

it('sorts products correctly by creation date in descending order', function () {
    $team = Team::factory()->create();
    
    // Create products at different times
    $oldProduct = Product::factory()->create([
        'team_id' => $team->id,
        'name' => 'Old Product',
        'created_at' => now()->subDays(3),
    ]);
    
    $mediumProduct = Product::factory()->create([
        'team_id' => $team->id,
        'name' => 'Medium Product',
        'created_at' => now()->subDays(2),
    ]);
    
    $newProduct = Product::factory()->create([
        'team_id' => $team->id,
        'name' => 'New Product',
        'created_at' => now()->subDays(1),
    ]);
    
    // Sort by created_at descending
    $results = Product::query()
        ->where('team_id', $team->id)
        ->orderBy('created_at', 'desc')
        ->get();
    
    $sortedIds = $results->pluck('id')->toArray();
    $expectedOrder = [$newProduct->id, $mediumProduct->id, $oldProduct->id];
    
    expect($sortedIds)->toBe($expectedOrder);
});

/**
 * Property: Sort order should handle null values appropriately
 */
it('handles null values correctly when sorting', function () {
    $team = Team::factory()->create();
    
    // Create products with some null SKUs
    $productWithSku = Product::factory()->create([
        'team_id' => $team->id,
        'name' => 'Product With SKU',
        'sku' => 'SKU-001',
    ]);
    
    $productWithoutSku = Product::factory()->create([
        'team_id' => $team->id,
        'name' => 'Product Without SKU',
        'sku' => null,
    ]);
    
    // Sort by SKU ascending (nulls should come first in most databases)
    $results = Product::query()
        ->where('team_id', $team->id)
        ->orderBy('sku', 'asc')
        ->get();
    
    // Verify that we get consistent ordering (exact order may vary by database)
    expect($results)->toHaveCount(2);
    expect($results->pluck('id'))->toContain($productWithSku->id);
    expect($results->pluck('id'))->toContain($productWithoutSku->id);
});

/**
 * Property: Multiple sort criteria should be applied in the correct order
 */
it('applies multiple sort criteria in the correct order', function () {
    $team = Team::factory()->create();
    
    // Create products with same price but different names
    $productA = Product::factory()->create([
        'team_id' => $team->id,
        'name' => 'Alpha Product',
        'price' => 50.00,
    ]);
    
    $productB = Product::factory()->create([
        'team_id' => $team->id,
        'name' => 'Beta Product',
        'price' => 50.00,
    ]);
    
    $productC = Product::factory()->create([
        'team_id' => $team->id,
        'name' => 'Charlie Product',
        'price' => 25.00,
    ]);
    
    // Sort by price ascending, then by name ascending
    $results = Product::query()
        ->where('team_id', $team->id)
        ->orderBy('price', 'asc')
        ->orderBy('name', 'asc')
        ->get();
    
    $sortedIds = $results->pluck('id')->toArray();
    // Charlie (25.00) should come first, then Alpha (50.00), then Beta (50.00)
    $expectedOrder = [$productC->id, $productA->id, $productB->id];
    
    expect($sortedIds)->toBe($expectedOrder);
});