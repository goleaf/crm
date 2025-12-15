<?php

declare(strict_types=1);

require_once 'vendor/autoload.php';

use App\Enums\ProductAttributeDataType;
use App\Models\Product;
use App\Models\ProductAttribute;
use App\Models\ProductAttributeValue;
use App\Models\Team;
use App\Models\User;

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

// Set up database
Artisan::call('migrate:fresh');

echo "Running Property 10: Attribute assignment completeness test...\n";

$iterations = 10; // Reduced for quick testing
$passed = 0;
$failed = 0;

for ($i = 1; $i <= $iterations; $i++) {
    try {
        // Create fresh test data for each iteration
        $team = Team::factory()->create();
        $user = User::factory()->create();
        $user->teams()->attach($team);
        $user->switchTeam($team);

        $product = Product::factory()->create(['team_id' => $team->id]);

        // Create various types of attributes
        $textAttribute = ProductAttribute::factory()->create([
            'team_id' => $team->id,
            'data_type' => ProductAttributeDataType::TEXT,
            'name' => 'Description' . $i,
        ]);

        $numberAttribute = ProductAttribute::factory()->create([
            'team_id' => $team->id,
            'data_type' => ProductAttributeDataType::NUMBER,
            'name' => 'Weight' . $i,
        ]);

        $booleanAttribute = ProductAttribute::factory()->create([
            'team_id' => $team->id,
            'data_type' => ProductAttributeDataType::BOOLEAN,
            'name' => 'Waterproof' . $i,
        ]);

        $selectAttribute = ProductAttribute::factory()->create([
            'team_id' => $team->id,
            'data_type' => ProductAttributeDataType::SELECT,
            'name' => 'Color' . $i,
        ]);

        // Create predefined values for select attribute
        $colorValues = ['Red', 'Blue', 'Green'];
        foreach ($colorValues as $index => $color) {
            ProductAttributeValue::factory()->create([
                'product_attribute_id' => $selectAttribute->id,
                'value' => $color,
                'sort_order' => $index,
            ]);
        }

        $multiSelectAttribute = ProductAttribute::factory()->create([
            'team_id' => $team->id,
            'data_type' => ProductAttributeDataType::MULTI_SELECT,
            'name' => 'Features' . $i,
        ]);

        // Create predefined values for multi-select attribute
        $featureValues = ['Bluetooth', 'WiFi', 'GPS', 'Camera'];
        foreach ($featureValues as $index => $feature) {
            ProductAttributeValue::factory()->create([
                'product_attribute_id' => $multiSelectAttribute->id,
                'value' => $feature,
                'sort_order' => $index,
            ]);
        }

        // Assign values to all attributes
        $textValue = fake()->sentence();
        $numberValue = fake()->randomFloat(2, 1, 100);
        $booleanValue = fake()->boolean();
        $selectValue = fake()->randomElement($colorValues);
        $multiSelectValue = fake()->randomElements($featureValues, fake()->numberBetween(1, 3));

        $product->assignAttribute($textAttribute, $textValue);
        $product->assignAttribute($numberAttribute, $numberValue);
        $product->assignAttribute($booleanAttribute, $booleanValue);
        $product->assignAttribute($selectAttribute, $selectValue);
        $product->assignAttribute($multiSelectAttribute, $multiSelectValue);

        // Retrieve the product fresh from database
        $retrievedProduct = Product::find($product->id);

        // Verify all assignments are retrievable
        if ($retrievedProduct->attributeAssignments->count() !== 5) {
            throw new Exception('Expected 5 assignments, got ' . $retrievedProduct->attributeAssignments->count());
        }

        // Verify each attribute value is correctly retrievable
        if ($retrievedProduct->getProductAttributeValue($textAttribute) !== $textValue) {
            throw new Exception('Text value mismatch');
        }

        if ((float) $retrievedProduct->getProductAttributeValue($numberAttribute) !== (float) $numberValue) {
            throw new Exception('Number value mismatch');
        }

        if ($retrievedProduct->getProductAttributeValue($booleanAttribute) !== $booleanValue) {
            throw new Exception('Boolean value mismatch');
        }

        if ($retrievedProduct->getProductAttributeValue($selectAttribute) !== $selectValue) {
            throw new Exception('Select value mismatch');
        }

        if ($retrievedProduct->getProductAttributeValue($multiSelectAttribute) !== $multiSelectValue) {
            throw new Exception('Multi-select value mismatch');
        }

        // Verify hasProductAttribute works for all assigned attributes
        if (! $retrievedProduct->hasProductAttribute($textAttribute) ||
            ! $retrievedProduct->hasProductAttribute($numberAttribute) ||
            ! $retrievedProduct->hasProductAttribute($booleanAttribute) ||
            ! $retrievedProduct->hasProductAttribute($selectAttribute) ||
            ! $retrievedProduct->hasProductAttribute($multiSelectAttribute)) {
            throw new Exception('hasProductAttribute check failed');
        }

        // Verify getAttributesForDisplay returns all attributes
        $displayAttributes = $retrievedProduct->getAttributesForDisplay();
        if (count($displayAttributes) !== 5) {
            throw new Exception('Expected 5 display attributes, got ' . count($displayAttributes));
        }

        // Verify each display attribute has the expected structure
        foreach ($displayAttributes as $displayAttribute) {
            if (! isset($displayAttribute['attribute']) ||
                ! isset($displayAttribute['value']) ||
                ! isset($displayAttribute['display_value'])) {
                throw new Exception('Display attribute missing required keys');
            }

            if (! ($displayAttribute['attribute'] instanceof ProductAttribute)) {
                throw new Exception("Display attribute 'attribute' is not a ProductAttribute instance");
            }

            if ($displayAttribute['value'] === null) {
                throw new Exception("Display attribute 'value' is null");
            }

            if (! is_string($displayAttribute['display_value'])) {
                throw new Exception("Display attribute 'display_value' is not a string");
            }
        }

        $passed++;
        echo "Iteration $i: ✓ PASS\n";

    } catch (Exception $e) {
        $failed++;
        echo "Iteration $i: ✗ FAIL - " . $e->getMessage() . "\n";
    }
}

echo "\nResults:\n";
echo "Passed: $passed/$iterations\n";
echo "Failed: $failed/$iterations\n";

if ($failed === 0) {
    echo "✓ Property 10: Attribute assignment completeness - ALL TESTS PASSED\n";
} else {
    echo "✗ Property 10: Attribute assignment completeness - SOME TESTS FAILED\n";
}
