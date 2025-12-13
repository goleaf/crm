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

echo "Testing Property 12: Variation data independence...\n\n";

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

        // Get two different variations
        $variation1 = $variations->first();
        $variation2 = $variations->skip(1)->first();

        // Store original data
        $originalPrice1 = $variation1->price;
        $originalPrice2 = $variation2->price;
        $originalInventory1 = $variation1->inventory_quantity;
        $originalInventory2 = $variation2->inventory_quantity;

        // Update first variation
        $newPrice1 = 99.99;
        $newInventory1 = 50;
        $updatedVariation1 = $variationService->updateVariation($variation1, [
            'price' => $newPrice1,
            'inventory_quantity' => $newInventory1,
        ]);

        // Verify first variation was updated
        if ($updatedVariation1->price != $newPrice1) {
            throw new Exception("First variation price not updated correctly");
        }
        if ($updatedVariation1->inventory_quantity != $newInventory1) {
            throw new Exception("First variation inventory not updated correctly");
        }

        // Verify second variation was NOT affected
        $variation2->refresh();
        if ($variation2->price != $originalPrice2) {
            throw new Exception("Second variation price was incorrectly modified");
        }
        if ($variation2->inventory_quantity != $originalInventory2) {
            throw new Exception("Second variation inventory was incorrectly modified");
        }

        // Update second variation with different values
        $newPrice2 = 149.99;
        $newInventory2 = 25;
        $updatedVariation2 = $variationService->updateVariation($variation2, [
            'price' => $newPrice2,
            'inventory_quantity' => $newInventory2,
        ]);

        // Verify second variation was updated
        if ($updatedVariation2->price != $newPrice2) {
            throw new Exception("Second variation price not updated correctly");
        }
        if ($updatedVariation2->inventory_quantity != $newInventory2) {
            throw new Exception("Second variation inventory not updated correctly");
        }

        // Verify first variation is still unchanged
        $updatedVariation1->refresh();
        if ($updatedVariation1->price != $newPrice1) {
            throw new Exception("First variation price was incorrectly modified after second update");
        }
        if ($updatedVariation1->inventory_quantity != $newInventory1) {
            throw new Exception("First variation inventory was incorrectly modified after second update");
        }

        // Verify other variations remain unchanged
        $otherVariations = $variations->skip(2);
        foreach ($otherVariations as $otherVariation) {
            $otherVariation->refresh();
            if ($otherVariation->price != $product->price) {
                throw new Exception("Other variation price was incorrectly modified");
            }
            if ($otherVariation->inventory_quantity != 0) {
                throw new Exception("Other variation inventory was incorrectly modified");
            }
        }

        echo "PASS (Variation data independence maintained)\n";
        $passCount++;

    } catch (Exception $e) {
        echo "FAIL - " . $e->getMessage() . "\n";
    }
}

echo "\nProperty 12 Test Results: $passCount/$totalTests passed\n";

if ($passCount === $totalTests) {
    echo "✅ Property 12: Variation data independence - PASSED\n";
} else {
    echo "❌ Property 12: Variation data independence - FAILED\n";
}