<?php

require_once __DIR__ . '/vendor/autoload.php';

use App\Models\Product;
use App\Models\ProductAttribute;
use App\Models\ProductAttributeValue;
use App\Models\Team;
use App\Services\Products\VariationService;

// Bootstrap Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "Testing Property 13: Variation soft delete preservation...\n\n";

$passCount = 0;
$totalTests = 10;

for ($test = 1; $test <= $totalTests; $test++) {
    try {
        echo "Test iteration $test: ";
        
        // Create test data
        $team = Team::factory()->create();
        $product = Product::factory()->create(['team_id' => $team->id]);
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

        // Generate variations
        $variations = $variationService->generateVariations($product, [$colorAttribute->id, $sizeAttribute->id]);

        if ($variations->count() !== 4) {
            throw new Exception("Expected 4 variations, got " . $variations->count());
        }

        // Store original data for all variations
        $originalData = [];
        foreach ($variations as $variation) {
            $originalData[$variation->id] = [
                'name' => $variation->name,
                'sku' => $variation->sku,
                'price' => $variation->price,
                'options' => $variation->options,
                'inventory_quantity' => $variation->inventory_quantity,
            ];
        }

        // Update variations with unique data
        $updatedVariations = [];
        foreach ($variations as $index => $variation) {
            $updatedVariation = $variationService->updateVariation($variation, [
                'price' => 100 + ($index * 10),
                'inventory_quantity' => 50 + ($index * 5),
            ]);
            $updatedVariations[] = $updatedVariation;
        }

        // Verify all variations are active
        $activeCount = $product->variations()->count();
        $totalCount = $product->variations()->withTrashed()->count();
        
        if ($activeCount !== 4) {
            throw new Exception("Expected 4 active variations, got $activeCount");
        }
        if ($totalCount !== 4) {
            throw new Exception("Expected 4 total variations, got $totalCount");
        }

        // Soft delete one variation
        $variationToDelete = $updatedVariations[0];
        $deletedVariationId = $variationToDelete->id;
        $deletedVariationData = [
            'name' => $variationToDelete->name,
            'sku' => $variationToDelete->sku,
            'price' => $variationToDelete->price,
            'options' => $variationToDelete->options,
            'inventory_quantity' => $variationToDelete->inventory_quantity,
        ];

        $variationService->deleteVariation($variationToDelete);

        // Verify counts after soft delete
        $activeCountAfterDelete = $product->variations()->count();
        $totalCountAfterDelete = $product->variations()->withTrashed()->count();
        
        if ($activeCountAfterDelete !== 3) {
            throw new Exception("Expected 3 active variations after delete, got $activeCountAfterDelete");
        }
        if ($totalCountAfterDelete !== 4) {
            throw new Exception("Expected 4 total variations after delete, got $totalCountAfterDelete");
        }

        // Verify the deleted variation is soft deleted (not hard deleted)
        $deletedVariation = $product->variations()->withTrashed()->find($deletedVariationId);
        if (!$deletedVariation) {
            throw new Exception("Deleted variation was hard deleted instead of soft deleted");
        }
        if (!$deletedVariation->trashed()) {
            throw new Exception("Variation was not properly soft deleted");
        }

        // Verify deleted variation data is preserved
        if ($deletedVariation->name !== $deletedVariationData['name']) {
            throw new Exception("Deleted variation name was not preserved");
        }
        if ($deletedVariation->sku !== $deletedVariationData['sku']) {
            throw new Exception("Deleted variation SKU was not preserved");
        }
        if ($deletedVariation->price != $deletedVariationData['price']) {
            throw new Exception("Deleted variation price was not preserved");
        }
        if (json_encode($deletedVariation->options) !== json_encode($deletedVariationData['options'])) {
            throw new Exception("Deleted variation options were not preserved");
        }
        if ($deletedVariation->inventory_quantity != $deletedVariationData['inventory_quantity']) {
            throw new Exception("Deleted variation inventory was not preserved");
        }

        // Verify remaining variations are unaffected
        $remainingVariations = $product->variations()->get();
        if ($remainingVariations->count() !== 3) {
            throw new Exception("Expected 3 remaining active variations");
        }

        foreach ($remainingVariations as $remaining) {
            if ($remaining->id === $deletedVariationId) {
                throw new Exception("Deleted variation still appears in active variations");
            }
            
            // Verify remaining variations still have their updated data
            $found = false;
            foreach ($updatedVariations as $updated) {
                if ($updated->id === $remaining->id && $updated->id !== $deletedVariationId) {
                    if ($remaining->price != $updated->price) {
                        throw new Exception("Remaining variation price was affected by delete");
                    }
                    if ($remaining->inventory_quantity != $updated->inventory_quantity) {
                        throw new Exception("Remaining variation inventory was affected by delete");
                    }
                    $found = true;
                    break;
                }
            }
            if (!$found) {
                throw new Exception("Could not verify remaining variation data");
            }
        }

        // Verify we can still access the deleted variation with withTrashed()
        $allVariationsIncludingDeleted = $product->variations()->withTrashed()->get();
        if ($allVariationsIncludingDeleted->count() !== 4) {
            throw new Exception("Expected 4 variations when including trashed");
        }

        $deletedFound = false;
        foreach ($allVariationsIncludingDeleted as $variation) {
            if ($variation->id === $deletedVariationId) {
                $deletedFound = true;
                if (!$variation->trashed()) {
                    throw new Exception("Deleted variation should be marked as trashed");
                }
                break;
            }
        }
        if (!$deletedFound) {
            throw new Exception("Deleted variation not found in withTrashed() query");
        }

        echo "PASS (Soft delete preserves data and maintains integrity)\n";
        $passCount++;

    } catch (Exception $e) {
        echo "FAIL - " . $e->getMessage() . "\n";
    }
}

echo "\nProperty 13 Test Results: $passCount/$totalTests passed\n";

if ($passCount === $totalTests) {
    echo "✅ Property 13: Variation soft delete preservation - PASSED\n";
} else {
    echo "❌ Property 13: Variation soft delete preservation - FAILED\n";
}