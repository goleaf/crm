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

echo "Testing Property 11: Variation generation completeness...\n\n";

$passCount = 0;
$totalTests = 10;

for ($test = 1; $test <= $totalTests; $test++) {
    try {
        echo "Test iteration $test: ";
        
        // Create test data
        $team = Team::factory()->create();
        $product = Product::factory()->create(['team_id' => $team->id]);
        $variationService = app(VariationService::class);

        // Generate random number of attributes (2-3 for reasonable test execution time)
        $numAttributes = rand(2, 3);
        $attributes = [];
        $expectedCombinations = 1;

        for ($i = 0; $i < $numAttributes; $i++) {
            // Create attribute with random number of values (2-3)
            $numValues = rand(2, 3);
            $attribute = ProductAttribute::factory()->create([
                'team_id' => $team->id,
                'name' => "Attribute {$i}",
                'slug' => "attribute-{$i}",
                'data_type' => 'select',
                'is_configurable' => true,
            ]);

            // Create values for this attribute
            for ($j = 0; $j < $numValues; $j++) {
                ProductAttributeValue::factory()->create([
                    'product_attribute_id' => $attribute->id,
                    'value' => "Value {$j}",
                    'sort_order' => $j,
                ]);
            }

            $attributes[] = $attribute;
            $expectedCombinations *= $numValues;
        }

        // Associate attributes as configurable for the product
        $product->configurableAttributes()->attach(collect($attributes)->pluck('id'));

        // Generate variations
        $variations = $variationService->generateVariations($product, collect($attributes)->pluck('id')->toArray());

        // Verify correct number of variations created
        if ($variations->count() !== $expectedCombinations) {
            throw new Exception("Expected $expectedCombinations variations, got " . $variations->count());
        }

        // Verify all combinations are unique
        $optionSets = $variations->map(fn($variation) => json_encode($variation->options))->toArray();
        $uniqueOptionSets = array_unique($optionSets);
        if (count($uniqueOptionSets) !== $expectedCombinations) {
            throw new Exception("Expected $expectedCombinations unique combinations, got " . count($uniqueOptionSets));
        }

        // Verify each variation has all attributes represented
        foreach ($variations as $variation) {
            if (count($variation->options) !== $numAttributes) {
                throw new Exception("Variation should have $numAttributes attributes, got " . count($variation->options));
            }
            
            foreach ($attributes as $attribute) {
                if (!array_key_exists($attribute->slug, $variation->options)) {
                    throw new Exception("Missing attribute {$attribute->slug} in variation options");
                }
                
                // Verify the value is one of the valid values for this attribute
                $validValues = $attribute->values->pluck('value')->toArray();
                if (!in_array($variation->options[$attribute->slug], $validValues)) {
                    throw new Exception("Invalid value for attribute {$attribute->slug}");
                }
            }
        }

        echo "PASS (Generated $expectedCombinations variations from $numAttributes attributes)\n";
        $passCount++;

    } catch (Exception $e) {
        echo "FAIL - " . $e->getMessage() . "\n";
    }
}

echo "\nProperty 11 Test Results: $passCount/$totalTests passed\n";

if ($passCount === $totalTests) {
    echo "✅ Property 11: Variation generation completeness - PASSED\n";
} else {
    echo "❌ Property 11: Variation generation completeness - FAILED\n";
}