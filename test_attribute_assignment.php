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

// Create test data
$team = Team::factory()->create();
$user = User::factory()->create();
$user->teams()->attach($team);
$user->switchTeam($team);

$product = Product::factory()->create(['team_id' => $team->id]);

// Create various types of attributes
$textAttribute = ProductAttribute::factory()->create([
    'team_id' => $team->id,
    'data_type' => ProductAttributeDataType::TEXT,
    'name' => 'Description',
]);

$numberAttribute = ProductAttribute::factory()->create([
    'team_id' => $team->id,
    'data_type' => ProductAttributeDataType::NUMBER,
    'name' => 'Weight',
]);

$booleanAttribute = ProductAttribute::factory()->create([
    'team_id' => $team->id,
    'data_type' => ProductAttributeDataType::BOOLEAN,
    'name' => 'Waterproof',
]);

$selectAttribute = ProductAttribute::factory()->create([
    'team_id' => $team->id,
    'data_type' => ProductAttributeDataType::SELECT,
    'name' => 'Color',
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
    'name' => 'Features',
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
$textValue = 'Sample description text';
$numberValue = 25.5;
$booleanValue = true;
$selectValue = 'Red';
$multiSelectValue = ['Bluetooth', 'WiFi'];

echo "Testing attribute assignments...\n";

try {
    $product->assignAttribute($textAttribute, $textValue);
    echo "✓ Text attribute assigned successfully\n";
} catch (Exception $e) {
    echo '✗ Text attribute assignment failed: ' . $e->getMessage() . "\n";
}

try {
    $product->assignAttribute($numberAttribute, $numberValue);
    echo "✓ Number attribute assigned successfully\n";
} catch (Exception $e) {
    echo '✗ Number attribute assignment failed: ' . $e->getMessage() . "\n";
}

try {
    $product->assignAttribute($booleanAttribute, $booleanValue);
    echo "✓ Boolean attribute assigned successfully\n";
} catch (Exception $e) {
    echo '✗ Boolean attribute assignment failed: ' . $e->getMessage() . "\n";
}

try {
    $product->assignAttribute($selectAttribute, $selectValue);
    echo "✓ Select attribute assigned successfully\n";
} catch (Exception $e) {
    echo '✗ Select attribute assignment failed: ' . $e->getMessage() . "\n";
}

try {
    $product->assignAttribute($multiSelectAttribute, $multiSelectValue);
    echo "✓ Multi-select attribute assigned successfully\n";
} catch (Exception $e) {
    echo '✗ Multi-select attribute assignment failed: ' . $e->getMessage() . "\n";
}

// Test retrieval
echo "\nTesting attribute retrieval...\n";

$retrievedProduct = Product::find($product->id);

echo 'Attribute assignments count: ' . $retrievedProduct->attributeAssignments->count() . "\n";

// Test each value
$retrievedTextValue = $retrievedProduct->getProductAttributeValue($textAttribute);
echo 'Text value: ' . ($retrievedTextValue === $textValue ? '✓ PASS' : '✗ FAIL') . " (Expected: '$textValue', Got: '$retrievedTextValue')\n";

$retrievedNumberValue = $retrievedProduct->getProductAttributeValue($numberAttribute);
echo 'Number value: ' . ($retrievedNumberValue === $numberValue ? '✓ PASS' : '✗ FAIL') . " (Expected: $numberValue, Got: $retrievedNumberValue)\n";

$retrievedBooleanValue = $retrievedProduct->getProductAttributeValue($booleanAttribute);
echo 'Boolean value: ' . ($retrievedBooleanValue === $booleanValue ? '✓ PASS' : '✗ FAIL') . ' (Expected: ' . ($booleanValue ? 'true' : 'false') . ', Got: ' . ($retrievedBooleanValue ? 'true' : 'false') . ")\n";

$retrievedSelectValue = $retrievedProduct->getProductAttributeValue($selectAttribute);
echo 'Select value: ' . ($retrievedSelectValue === $selectValue ? '✓ PASS' : '✗ FAIL') . " (Expected: '$selectValue', Got: '$retrievedSelectValue')\n";

$retrievedMultiSelectValue = $retrievedProduct->getProductAttributeValue($multiSelectAttribute);
echo 'Multi-select value: ' . ($retrievedMultiSelectValue === $multiSelectValue ? '✓ PASS' : '✗ FAIL') . ' (Expected: ' . json_encode($multiSelectValue) . ', Got: ' . json_encode($retrievedMultiSelectValue) . ")\n";

// Test hasProductAttribute
echo "\nTesting hasProductAttribute...\n";
echo 'Has text attribute: ' . ($retrievedProduct->hasProductAttribute($textAttribute) ? '✓ PASS' : '✗ FAIL') . "\n";
echo 'Has number attribute: ' . ($retrievedProduct->hasProductAttribute($numberAttribute) ? '✓ PASS' : '✗ FAIL') . "\n";
echo 'Has boolean attribute: ' . ($retrievedProduct->hasProductAttribute($booleanAttribute) ? '✓ PASS' : '✗ FAIL') . "\n";
echo 'Has select attribute: ' . ($retrievedProduct->hasProductAttribute($selectAttribute) ? '✓ PASS' : '✗ FAIL') . "\n";
echo 'Has multi-select attribute: ' . ($retrievedProduct->hasProductAttribute($multiSelectAttribute) ? '✓ PASS' : '✗ FAIL') . "\n";

// Test getAttributesForDisplay
echo "\nTesting getAttributesForDisplay...\n";
$displayAttributes = $retrievedProduct->getAttributesForDisplay();
echo 'Display attributes count: ' . count($displayAttributes) . "\n";

foreach ($displayAttributes as $displayAttribute) {
    echo '- ' . $displayAttribute['attribute']->name . ': ' . $displayAttribute['display_value'] . "\n";
}

echo "\nTest completed!\n";
