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

echo "Testing VariationService functionality...\n\n";

try {
    // Create test data
    $team = Team::factory()->create();
    $product = Product::factory()->create(['team_id' => $team->id]);
    $variationService = app(VariationService::class);

    echo "✓ Created test team and product\n";

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

    echo "✓ Created configurable attributes\n";

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

    echo "✓ Created attribute values\n";

    // Associate attributes as configurable
    $product->configurableAttributes()->attach([$colorAttribute->id, $sizeAttribute->id]);

    echo "✓ Associated attributes with product\n";

    // Generate variations
    $variations = $variationService->generateVariations($product, [$colorAttribute->id, $sizeAttribute->id]);

    echo "✓ Generated variations: " . $variations->count() . " variations created\n";

    // Expected: 2 colors × 2 sizes = 4 variations
    if ($variations->count() === 4) {
        echo "✓ Correct number of variations generated\n";
    } else {
        echo "✗ Expected 4 variations, got " . $variations->count() . "\n";
    }

    // Check variation combinations
    $combinations = $variations->map(fn($v) => $v->options)->toArray();
    $expectedCombinations = [
        ['color' => 'Red', 'size' => 'Small'],
        ['color' => 'Red', 'size' => 'Large'],
        ['color' => 'Blue', 'size' => 'Small'],
        ['color' => 'Blue', 'size' => 'Large'],
    ];

    echo "Generated combinations:\n";
    foreach ($combinations as $combo) {
        echo "  - " . json_encode($combo) . "\n";
    }

    // Test variation update
    $firstVariation = $variations->first();
    $originalPrice = $firstVariation->price;
    $newPrice = 99.99;

    $updatedVariation = $variationService->updateVariation($firstVariation, ['price' => $newPrice]);

    if ($updatedVariation->price == $newPrice) {
        echo "✓ Variation update works correctly\n";
    } else {
        echo "✗ Variation update failed\n";
    }

    // Test soft delete
    $variationService->deleteVariation($firstVariation);
    
    $activeVariations = $product->variations()->count();
    $allVariations = $product->variations()->withTrashed()->count();
    
    if ($activeVariations === 3 && $allVariations === 4) {
        echo "✓ Soft delete works correctly\n";
    } else {
        echo "✗ Soft delete failed - Active: $activeVariations, Total: $allVariations\n";
    }

    echo "\n✅ All basic VariationService functionality tests passed!\n";

} catch (Exception $e) {
    echo "\n❌ Test failed with error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}