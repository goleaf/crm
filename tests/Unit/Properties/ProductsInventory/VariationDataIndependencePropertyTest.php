<?php

declare(strict_types=1);

// Feature: products-inventory, Property 12: Variation data independence

use App\Models\Product;
use App\Models\ProductAttribute;
use App\Models\ProductAttributeValue;
use App\Models\ProductVariation;
use App\Models\Team;
use App\Services\Products\VariationService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/**
 * Property 12: Variation data independence
 * 
 * For any product variation, updating its SKU, price, or inventory should not
 * affect other variations or the parent product.
 * 
 * Validates: Requirements 4.3
 */
it('maintains data independence when updating variation properties', function () {
    $team = Team::factory()->create();
    $product = Product::factory()->create([
        'team_id' => $team->id,
        'price' => fake()->randomFloat(2, 10, 1000),
        'inventory_quantity' => fake()->numberBetween(0, 100),
    ]);
    $variationService = app(VariationService::class);

    // Create configurable attributes
    $colorAttribute = ProductAttribute::factory()->create([
        'team_id' => $team->id,
        'name' => 'Color',
        'slug' => 'color',
        'data_type' => 'select',
        'is_configurable' => true,
    ]);

    $sizeAttribute = ProductAttribute::factory()->create([
        'team_id' => $team->id,
        'name' => 'Size',
        'slug' => 'size',
        'data_type' => 'select',
        'is_configurable' => true,
    ]);

    // Create attribute values
    ProductAttributeValue::factory()->create([
        'product_attribute_id' => $colorAttribute->id,
        'value' => 'Red',
        'sort_order' => 0,
    ]);
    ProductAttributeValue::factory()->create([
        'product_attribute_id' => $colorAttribute->id,
        'value' => 'Blue',
        'sort_order' => 1,
    ]);
    ProductAttributeValue::factory()->create([
        'product_attribute_id' => $sizeAttribute->id,
        'value' => 'Small',
        'sort_order' => 0,
    ]);
    ProductAttributeValue::factory()->create([
        'product_attribute_id' => $sizeAttribute->id,
        'value' => 'Large',
        'sort_order' => 1,
    ]);

    // Associate attributes as configurable
    $product->configurableAttributes()->attach([$colorAttribute->id, $sizeAttribute->id]);

    // Generate variations (should create 4: Red-Small, Red-Large, Blue-Small, Blue-Large)
    $variations = $variationService->generateVariations($product, [$colorAttribute->id, $sizeAttribute->id]);
    expect($variations)->toHaveCount(4);

    // Store original data for comparison
    $originalProductData = [
        'price' => $product->price,
        'inventory_quantity' => $product->inventory_quantity,
        'sku' => $product->sku,
    ];

    $originalVariationsData = $variations->map(function ($variation) {
        return [
            'id' => $variation->id,
            'price' => $variation->price,
            'inventory_quantity' => $variation->inventory_quantity,
            'sku' => $variation->sku,
            'options' => $variation->options,
        ];
    })->toArray();

    // Select one variation to update
    $targetVariation = $variations->first();
    $otherVariations = $variations->slice(1);

    // Generate new random values for the target variation
    $newPrice = fake()->randomFloat(2, 10, 1000);
    $newInventory = fake()->numberBetween(0, 100);
    $newSku = fake()->unique()->regexify('[A-Z]{3}-[0-9]{4}');

    // Update the target variation
    $updatedVariation = $variationService->updateVariation($targetVariation, [
        'price' => $newPrice,
        'inventory_quantity' => $newInventory,
        'sku' => $newSku,
    ]);

    // Verify the target variation was updated
    expect($updatedVariation->price)->toEqual($newPrice);
    expect($updatedVariation->inventory_quantity)->toEqual($newInventory);
    expect($updatedVariation->sku)->toEqual($newSku);

    // Verify parent product data is unchanged
    $product->refresh();
    expect($product->price)->toEqual($originalProductData['price']);
    expect($product->inventory_quantity)->toEqual($originalProductData['inventory_quantity']);
    expect($product->sku)->toEqual($originalProductData['sku']);

    // Verify other variations are unchanged
    foreach ($otherVariations as $variation) {
        $variation->refresh();
        $originalData = collect($originalVariationsData)->firstWhere('id', $variation->id);
        
        expect($variation->price)->toEqual($originalData['price']);
        expect($variation->inventory_quantity)->toEqual($originalData['inventory_quantity']);
        expect($variation->sku)->toEqual($originalData['sku']);
        expect($variation->options)->toEqual($originalData['options']);
    }

    // Verify the target variation's options (attribute combinations) are unchanged
    $targetVariation->refresh();
    $originalTargetData = collect($originalVariationsData)->firstWhere('id', $targetVariation->id);
    expect($targetVariation->options)->toEqual($originalTargetData['options']);
})->repeat(100);

/**
 * Property 12b: Inventory adjustments should be independent
 */
it('maintains inventory independence when adjusting variation inventory', function () {
    $team = Team::factory()->create();
    $product = Product::factory()->create([
        'team_id' => $team->id,
        'track_inventory' => true,
        'inventory_quantity' => 100,
    ]);

    // Create two variations manually for simpler test
    $variation1 = ProductVariation::factory()->create([
        'product_id' => $product->id,
        'name' => 'Red Small',
        'track_inventory' => true,
        'inventory_quantity' => 50,
        'reserved_quantity' => 5,
        'options' => ['color' => 'Red', 'size' => 'Small'],
    ]);

    $variation2 = ProductVariation::factory()->create([
        'product_id' => $product->id,
        'name' => 'Blue Large',
        'track_inventory' => true,
        'inventory_quantity' => 30,
        'reserved_quantity' => 3,
        'options' => ['color' => 'Blue', 'size' => 'Large'],
    ]);

    // Store original values
    $originalVariation1Inventory = $variation1->inventory_quantity;
    $originalVariation1Reserved = $variation1->reserved_quantity;
    $originalVariation2Inventory = $variation2->inventory_quantity;
    $originalVariation2Reserved = $variation2->reserved_quantity;
    $originalProductInventory = $product->inventory_quantity;

    // Adjust inventory for variation1 only
    $inventoryAdjustment = fake()->numberBetween(-20, 20);
    $reservedAdjustment = fake()->numberBetween(-5, 10);

    $variation1->update([
        'inventory_quantity' => max(0, $originalVariation1Inventory + $inventoryAdjustment),
        'reserved_quantity' => max(0, $originalVariation1Reserved + $reservedAdjustment),
    ]);

    // Verify variation1 was updated
    $variation1->refresh();
    expect($variation1->inventory_quantity)->toEqual(max(0, $originalVariation1Inventory + $inventoryAdjustment));
    expect($variation1->reserved_quantity)->toEqual(max(0, $originalVariation1Reserved + $reservedAdjustment));

    // Verify variation2 is unchanged
    $variation2->refresh();
    expect($variation2->inventory_quantity)->toEqual($originalVariation2Inventory);
    expect($variation2->reserved_quantity)->toEqual($originalVariation2Reserved);

    // Verify parent product inventory is unchanged (variations manage their own inventory)
    $product->refresh();
    expect($product->inventory_quantity)->toEqual($originalProductInventory);
})->repeat(100);

/**
 * Property 12c: Price updates should be independent
 */
it('maintains price independence when updating variation prices', function () {
    $team = Team::factory()->create();
    $basePrice = fake()->randomFloat(2, 50, 500);
    $product = Product::factory()->create([
        'team_id' => $team->id,
        'price' => $basePrice,
    ]);

    // Create multiple variations with different prices
    $numVariations = fake()->numberBetween(3, 6);
    $variations = [];
    $originalPrices = [];

    for ($i = 0; $i < $numVariations; $i++) {
        $price = fake()->randomFloat(2, 10, 1000);
        $variation = ProductVariation::factory()->create([
            'product_id' => $product->id,
            'name' => "Variation {$i}",
            'price' => $price,
            'options' => ['variant' => "option-{$i}"],
        ]);
        
        $variations[] = $variation;
        $originalPrices[$variation->id] = $price;
    }

    // Select random variation to update
    $targetIndex = fake()->numberBetween(0, $numVariations - 1);
    $targetVariation = $variations[$targetIndex];
    $newPrice = fake()->randomFloat(2, 10, 1000);

    // Update target variation price
    $variationService = app(VariationService::class);
    $updatedVariation = $variationService->updateVariation($targetVariation, [
        'price' => $newPrice,
    ]);

    // Verify target variation price was updated
    expect($updatedVariation->price)->toEqual($newPrice);

    // Verify parent product price is unchanged
    $product->refresh();
    expect($product->price)->toEqual($basePrice);

    // Verify all other variations' prices are unchanged
    foreach ($variations as $index => $variation) {
        if ($index === $targetIndex) {
            continue; // Skip the updated variation
        }
        
        $variation->refresh();
        expect($variation->price)->toEqual($originalPrices[$variation->id]);
    }
})->repeat(100);

/**
 * Property 12d: SKU updates should be independent and unique
 */
it('maintains SKU independence and uniqueness when updating variation SKUs', function () {
    $team = Team::factory()->create();
    $product = Product::factory()->create([
        'team_id' => $team->id,
        'sku' => fake()->unique()->regexify('[A-Z]{3}-[0-9]{3}'),
    ]);

    // Create variations with unique SKUs
    $variation1 = ProductVariation::factory()->create([
        'product_id' => $product->id,
        'name' => 'Variation 1',
        'sku' => fake()->unique()->regexify('[A-Z]{3}-[0-9]{3}-V1'),
        'options' => ['variant' => 'option-1'],
    ]);

    $variation2 = ProductVariation::factory()->create([
        'product_id' => $product->id,
        'name' => 'Variation 2',
        'sku' => fake()->unique()->regexify('[A-Z]{3}-[0-9]{3}-V2'),
        'options' => ['variant' => 'option-2'],
    ]);

    // Store original SKUs
    $originalProductSku = $product->sku;
    $originalVariation1Sku = $variation1->sku;
    $originalVariation2Sku = $variation2->sku;

    // Update variation1 SKU
    $newSku = fake()->unique()->regexify('[A-Z]{3}-[0-9]{3}-NEW');
    $variationService = app(VariationService::class);
    $updatedVariation = $variationService->updateVariation($variation1, [
        'sku' => $newSku,
    ]);

    // Verify variation1 SKU was updated
    expect($updatedVariation->sku)->toEqual($newSku);

    // Verify parent product SKU is unchanged
    $product->refresh();
    expect($product->sku)->toEqual($originalProductSku);

    // Verify variation2 SKU is unchanged
    $variation2->refresh();
    expect($variation2->sku)->toEqual($originalVariation2Sku);

    // Verify all SKUs remain unique
    $allSkus = [
        $product->sku,
        $updatedVariation->sku,
        $variation2->sku,
    ];
    $uniqueSkus = array_unique(array_filter($allSkus)); // Filter out nulls
    expect($uniqueSkus)->toHaveCount(count(array_filter($allSkus)));
})->repeat(100);