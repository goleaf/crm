<?php

declare(strict_types=1);

// Feature: products-inventory, Property 13: Soft delete preservation

use App\Models\Product;
use App\Models\ProductVariation;
use App\Models\Team;
use App\Services\Products\VariationService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/**
 * Property 13: Soft delete preservation
 * 
 * For any variation that is disabled, the variation data should remain in the
 * database and be retrievable with deleted_at timestamp, but excluded from
 * active queries.
 * 
 * Validates: Requirements 4.5
 */
it('preserves variation data when soft deleted and excludes from active queries', function () {
    $team = Team::factory()->create();
    $product = Product::factory()->create(['team_id' => $team->id]);
    $variationService = app(VariationService::class);

    // Create multiple variations
    $numVariations = fake()->numberBetween(3, 6);
    $variations = [];
    
    for ($i = 0; $i < $numVariations; $i++) {
        $variation = ProductVariation::factory()->create([
            'product_id' => $product->id,
            'name' => "Variation {$i}",
            'sku' => fake()->unique()->regexify('[A-Z]{3}-[0-9]{3}-V' . $i),
            'price' => fake()->randomFloat(2, 10, 1000),
            'inventory_quantity' => fake()->numberBetween(0, 100),
            'options' => ['variant' => "option-{$i}"],
        ]);
        $variations[] = $variation;
    }

    // Store original data for all variations
    $originalData = [];
    foreach ($variations as $variation) {
        $originalData[$variation->id] = [
            'name' => $variation->name,
            'sku' => $variation->sku,
            'price' => $variation->price,
            'inventory_quantity' => $variation->inventory_quantity,
            'options' => $variation->options,
            'product_id' => $variation->product_id,
        ];
    }

    // Select random variation to delete
    $targetIndex = fake()->numberBetween(0, $numVariations - 1);
    $targetVariation = $variations[$targetIndex];
    $targetId = $targetVariation->id;

    // Verify variation exists in active queries before deletion
    expect(ProductVariation::find($targetId))->not->toBeNull();
    expect($product->variations()->count())->toEqual($numVariations);

    // Soft delete the target variation
    $variationService->deleteVariation($targetVariation);

    // Verify variation is excluded from active queries
    expect(ProductVariation::find($targetId))->toBeNull();
    expect($product->variations()->count())->toEqual($numVariations - 1);

    // Verify variation data is preserved in database with deleted_at timestamp
    $deletedVariation = ProductVariation::withTrashed()->find($targetId);
    expect($deletedVariation)->not->toBeNull();
    expect($deletedVariation->deleted_at)->not->toBeNull();
    expect($deletedVariation->deleted_at)->toBeInstanceOf(\Carbon\Carbon::class);

    // Verify all original data is preserved
    $originalVariationData = $originalData[$targetId];
    expect($deletedVariation->name)->toEqual($originalVariationData['name']);
    expect($deletedVariation->sku)->toEqual($originalVariationData['sku']);
    expect($deletedVariation->price)->toEqual($originalVariationData['price']);
    expect($deletedVariation->inventory_quantity)->toEqual($originalVariationData['inventory_quantity']);
    expect($deletedVariation->options)->toEqual($originalVariationData['options']);
    expect($deletedVariation->product_id)->toEqual($originalVariationData['product_id']);

    // Verify other variations are unaffected
    foreach ($variations as $index => $variation) {
        if ($index === $targetIndex) {
            continue; // Skip the deleted variation
        }
        
        $variation->refresh();
        $originalVariationData = $originalData[$variation->id];
        
        // Should still be accessible via active queries
        expect(ProductVariation::find($variation->id))->not->toBeNull();
        expect($variation->deleted_at)->toBeNull();
        
        // Data should be unchanged
        expect($variation->name)->toEqual($originalVariationData['name']);
        expect($variation->sku)->toEqual($originalVariationData['sku']);
        expect($variation->price)->toEqual($originalVariationData['price']);
        expect($variation->inventory_quantity)->toEqual($originalVariationData['inventory_quantity']);
        expect($variation->options)->toEqual($originalVariationData['options']);
    }

    // Verify deleted variation can be retrieved with withTrashed()
    $allVariationsIncludingDeleted = $product->variations()->withTrashed()->get();
    expect($allVariationsIncludingDeleted)->toHaveCount($numVariations);
    
    $deletedInCollection = $allVariationsIncludingDeleted->firstWhere('id', $targetId);
    expect($deletedInCollection)->not->toBeNull();
    expect($deletedInCollection->deleted_at)->not->toBeNull();

    // Verify only deleted variations are retrieved with onlyTrashed()
    $onlyDeletedVariations = $product->variations()->onlyTrashed()->get();
    expect($onlyDeletedVariations)->toHaveCount(1);
    expect($onlyDeletedVariations->first()->id)->toEqual($targetId);
})->repeat(100);

/**
 * Property 13b: Multiple soft deletes should preserve all data independently
 */
it('preserves data for multiple soft deleted variations independently', function () {
    $team = Team::factory()->create();
    $product = Product::factory()->create(['team_id' => $team->id]);
    $variationService = app(VariationService::class);

    // Create variations
    $totalVariations = fake()->numberBetween(5, 8);
    $variations = [];
    
    for ($i = 0; $i < $totalVariations; $i++) {
        $variation = ProductVariation::factory()->create([
            'product_id' => $product->id,
            'name' => "Variation {$i}",
            'sku' => fake()->unique()->regexify('[A-Z]{3}-[0-9]{3}-V' . $i),
            'price' => fake()->randomFloat(2, 10, 1000),
            'options' => ['variant' => "option-{$i}"],
        ]);
        $variations[] = $variation;
    }

    // Select random number of variations to delete (but not all)
    $numToDelete = fake()->numberBetween(2, $totalVariations - 1);
    $indicesToDelete = fake()->randomElements(range(0, $totalVariations - 1), $numToDelete);
    $variationsToDelete = [];
    
    foreach ($indicesToDelete as $index) {
        $variationsToDelete[] = $variations[$index];
    }

    // Store original data
    $originalData = [];
    foreach ($variations as $variation) {
        $originalData[$variation->id] = [
            'name' => $variation->name,
            'sku' => $variation->sku,
            'price' => $variation->price,
            'options' => $variation->options,
        ];
    }

    // Delete selected variations at different times to test timestamp independence
    $deletedIds = [];
    foreach ($variationsToDelete as $variation) {
        $variationService->deleteVariation($variation);
        $deletedIds[] = $variation->id;
        
        // Small delay to ensure different timestamps (if needed)
        usleep(1000); // 1ms delay
    }

    // Verify correct number of active variations remain
    expect($product->variations()->count())->toEqual($totalVariations - $numToDelete);

    // Verify all deleted variations are preserved with deleted_at timestamps
    $deletedVariations = ProductVariation::withTrashed()->whereIn('id', $deletedIds)->get();
    expect($deletedVariations)->toHaveCount($numToDelete);
    
    foreach ($deletedVariations as $deletedVariation) {
        expect($deletedVariation->deleted_at)->not->toBeNull();
        
        // Verify original data is preserved
        $originalVariationData = $originalData[$deletedVariation->id];
        expect($deletedVariation->name)->toEqual($originalVariationData['name']);
        expect($deletedVariation->sku)->toEqual($originalVariationData['sku']);
        expect($deletedVariation->price)->toEqual($originalVariationData['price']);
        expect($deletedVariation->options)->toEqual($originalVariationData['options']);
    }

    // Verify non-deleted variations are still active and unchanged
    $activeVariations = $product->variations()->get();
    foreach ($activeVariations as $activeVariation) {
        expect($activeVariation->deleted_at)->toBeNull();
        expect($deletedIds)->not->toContain($activeVariation->id);
        
        // Verify data is unchanged
        $originalVariationData = $originalData[$activeVariation->id];
        expect($activeVariation->name)->toEqual($originalVariationData['name']);
        expect($activeVariation->sku)->toEqual($originalVariationData['sku']);
        expect($activeVariation->price)->toEqual($originalVariationData['price']);
        expect($activeVariation->options)->toEqual($originalVariationData['options']);
    }

    // Verify total count with trashed matches original
    expect($product->variations()->withTrashed()->count())->toEqual($totalVariations);
})->repeat(100);

/**
 * Property 13c: Soft deleted variations should not affect parent product
 */
it('does not affect parent product when variations are soft deleted', function () {
    $team = Team::factory()->create();
    $product = Product::factory()->create([
        'team_id' => $team->id,
        'name' => fake()->words(3, true),
        'price' => fake()->randomFloat(2, 50, 500),
        'inventory_quantity' => fake()->numberBetween(50, 200),
    ]);
    $variationService = app(VariationService::class);

    // Store original product data
    $originalProductData = [
        'name' => $product->name,
        'price' => $product->price,
        'inventory_quantity' => $product->inventory_quantity,
        'sku' => $product->sku,
        'status' => $product->status,
    ];

    // Create variations
    $variations = [];
    for ($i = 0; $i < 4; $i++) {
        $variation = ProductVariation::factory()->create([
            'product_id' => $product->id,
            'name' => "Variation {$i}",
            'price' => fake()->randomFloat(2, 10, 100),
            'inventory_quantity' => fake()->numberBetween(10, 50),
            'options' => ['variant' => "option-{$i}"],
        ]);
        $variations[] = $variation;
    }

    // Delete random number of variations
    $numToDelete = fake()->numberBetween(1, 3);
    $variationsToDelete = fake()->randomElements($variations, $numToDelete);
    
    foreach ($variationsToDelete as $variation) {
        $variationService->deleteVariation($variation);
    }

    // Verify parent product data is completely unchanged
    $product->refresh();
    expect($product->name)->toEqual($originalProductData['name']);
    expect($product->price)->toEqual($originalProductData['price']);
    expect($product->inventory_quantity)->toEqual($originalProductData['inventory_quantity']);
    expect($product->sku)->toEqual($originalProductData['sku']);
    expect($product->status)->toEqual($originalProductData['status']);

    // Verify parent product is still active and accessible
    expect(Product::find($product->id))->not->toBeNull();
    expect($product->deleted_at)->toBeNull();
})->repeat(100);

/**
 * Property 13d: Soft deleted variations should maintain relationships
 */
it('maintains product relationship for soft deleted variations', function () {
    $team = Team::factory()->create();
    $product = Product::factory()->create(['team_id' => $team->id]);
    $variationService = app(VariationService::class);

    // Create variation
    $variation = ProductVariation::factory()->create([
        'product_id' => $product->id,
        'name' => 'Test Variation',
        'options' => ['color' => 'Red'],
    ]);

    $originalProductId = $variation->product_id;

    // Soft delete the variation
    $variationService->deleteVariation($variation);

    // Retrieve with trashed and verify relationship is maintained
    $deletedVariation = ProductVariation::withTrashed()->find($variation->id);
    expect($deletedVariation)->not->toBeNull();
    expect($deletedVariation->product_id)->toEqual($originalProductId);

    // Verify the relationship still works
    expect($deletedVariation->product)->not->toBeNull();
    expect($deletedVariation->product->id)->toEqual($product->id);

    // Verify the reverse relationship excludes soft deleted by default
    expect($product->variations()->count())->toEqual(0);
    
    // But includes them with withTrashed
    expect($product->variations()->withTrashed()->count())->toEqual(1);
    expect($product->variations()->withTrashed()->first()->id)->toEqual($variation->id);
})->repeat(100);