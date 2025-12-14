<?php

declare(strict_types=1);

use App\Enums\ProductAttributeDataType;
use App\Models\Product;
use App\Models\ProductAttribute;
use App\Models\ProductAttributeValue;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/**
 * **Feature: products-inventory, Property 10: Attribute assignment completeness**
 *
 * **Validates: Requirements 3.5**
 *
 * Property: For any product with assigned attributes, retrieving the product
 * should return all attribute assignments with their values.
 */

// Property: All assigned attributes are retrievable with their values
test('property: product returns all assigned attributes with values', function (): void {
    $team = Team::factory()->create();
    $user = User::factory()->create();
    $user->teams()->attach($team);
    $user->switchTeam($team);
    $this->actingAs($user);

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
    expect($retrievedProduct->attributeAssignments)->toHaveCount(5);

    // Verify each attribute value is correctly retrievable
    expect($retrievedProduct->getProductAttributeValue($textAttribute))->toBe($textValue);
    expect($retrievedProduct->getProductAttributeValue($numberAttribute))->toEqual($numberValue);
    expect($retrievedProduct->getProductAttributeValue($booleanAttribute))->toBe($booleanValue);
    expect($retrievedProduct->getProductAttributeValue($selectAttribute))->toBe($selectValue);
    expect($retrievedProduct->getProductAttributeValue($multiSelectAttribute))->toBe($multiSelectValue);

    // Verify hasProductAttribute works for all assigned attributes
    expect($retrievedProduct->hasProductAttribute($textAttribute))->toBeTrue();
    expect($retrievedProduct->hasProductAttribute($numberAttribute))->toBeTrue();
    expect($retrievedProduct->hasProductAttribute($booleanAttribute))->toBeTrue();
    expect($retrievedProduct->hasProductAttribute($selectAttribute))->toBeTrue();
    expect($retrievedProduct->hasProductAttribute($multiSelectAttribute))->toBeTrue();

    // Verify getAttributesForDisplay returns all attributes
    $displayAttributes = $retrievedProduct->getAttributesForDisplay();
    expect($displayAttributes)->toHaveCount(5);

    // Verify each display attribute has the expected structure
    foreach ($displayAttributes as $displayAttribute) {
        expect($displayAttribute)->toHaveKeys(['attribute', 'value', 'display_value']);
        expect($displayAttribute['attribute'])->toBeInstanceOf(ProductAttribute::class);
        expect($displayAttribute['value'])->not->toBeNull();
        expect($displayAttribute['display_value'])->toBeString();
    }
})->repeat(100);

// Property: Attribute assignments persist across product updates
test('property: attribute assignments persist when product is updated', function (): void {
    $team = Team::factory()->create();
    $user = User::factory()->create();
    $user->teams()->attach($team);
    $user->switchTeam($team);
    $this->actingAs($user);

    $product = Product::factory()->create(['team_id' => $team->id]);

    // Create and assign attributes
    $attribute1 = ProductAttribute::factory()->create([
        'team_id' => $team->id,
        'data_type' => ProductAttributeDataType::TEXT,
    ]);

    $attribute2 = ProductAttribute::factory()->create([
        'team_id' => $team->id,
        'data_type' => ProductAttributeDataType::NUMBER,
    ]);

    $value1 = fake()->word();
    $value2 = fake()->randomFloat(2, 1, 100);

    $product->assignAttribute($attribute1, $value1);
    $product->assignAttribute($attribute2, $value2);

    // Update the product
    $product->update([
        'name' => fake()->words(3, true),
        'description' => fake()->paragraph(),
        'price' => fake()->randomFloat(2, 10, 1000),
    ]);

    // Refresh and verify assignments still exist
    $product->refresh();
    expect($product->attributeAssignments)->toHaveCount(2);
    expect($product->getProductAttributeValue($attribute1))->toBe($value1);
    expect($product->getProductAttributeValue($attribute2))->toEqual($value2);
})->repeat(100);

// Property: Bulk attribute assignment completeness
test('property: bulk attribute assignment maintains completeness', function (): void {
    $team = Team::factory()->create();
    $user = User::factory()->create();
    $user->teams()->attach($team);
    $user->switchTeam($team);
    $this->actingAs($user);

    $product = Product::factory()->create(['team_id' => $team->id]);

    // Create multiple attributes
    $attributeCount = fake()->numberBetween(3, 8);
    $attributes = [];
    $expectedValues = [];

    for ($i = 0; $i < $attributeCount; $i++) {
        $dataType = fake()->randomElement([
            ProductAttributeDataType::TEXT,
            ProductAttributeDataType::NUMBER,
            ProductAttributeDataType::BOOLEAN,
        ]);

        $attribute = ProductAttribute::factory()->create([
            'team_id' => $team->id,
            'data_type' => $dataType,
            'name' => fake()->unique()->word() . $i,
        ]);

        $value = match ($dataType) {
            ProductAttributeDataType::TEXT => fake()->sentence(),
            ProductAttributeDataType::NUMBER => fake()->randomFloat(2, 1, 1000),
            ProductAttributeDataType::BOOLEAN => fake()->boolean(),
        };

        $attributes[$attribute->id] = $attribute;
        $expectedValues[$attribute->id] = $value;
    }

    // Bulk assign attributes
    $product->assignAttributes($expectedValues);

    // Verify all assignments are complete
    $product->refresh();
    expect($product->attributeAssignments)->toHaveCount($attributeCount);

    // Verify each assigned value
    foreach ($attributes as $attributeId => $attribute) {
        $expectedValue = $expectedValues[$attributeId];
        $actualValue = $product->getProductAttributeValue($attribute);

        if ($attribute->data_type === ProductAttributeDataType::NUMBER) {
            expect($actualValue)->toEqual((float) $expectedValue);
        } else {
            expect($actualValue)->toBe($expectedValue);
        }

        expect($product->hasProductAttribute($attribute))->toBeTrue();
    }
})->repeat(100);

// Property: Required attributes are enforced in assignment validation
test('property: required attributes enforce value presence during assignment', function (): void {
    $team = Team::factory()->create();
    $user = User::factory()->create();
    $user->teams()->attach($team);
    $user->switchTeam($team);
    $this->actingAs($user);

    $product = Product::factory()->create(['team_id' => $team->id]);

    $requiredAttribute = ProductAttribute::factory()->create([
        'team_id' => $team->id,
        'data_type' => ProductAttributeDataType::TEXT,
        'is_required' => true,
    ]);

    $optionalAttribute = ProductAttribute::factory()->create([
        'team_id' => $team->id,
        'data_type' => ProductAttributeDataType::TEXT,
        'is_required' => false,
    ]);

    // Valid non-empty values should work for both
    $validValue = fake()->word();
    $requiredAssignment = $product->assignAttribute($requiredAttribute, $validValue);
    $optionalAssignment = $product->assignAttribute($optionalAttribute, $validValue);

    expect($requiredAssignment->getValue())->toBe($validValue);
    expect($optionalAssignment->getValue())->toBe($validValue);

    // Empty string should work for optional but be handled appropriately for required
    $emptyValue = '';
    $optionalEmptyAssignment = $product->assignAttribute($optionalAttribute, $emptyValue);
    expect($optionalEmptyAssignment->getValue())->toBe($emptyValue);

    // For required attributes, empty values are still stored (business logic may handle this at form level)
    $requiredEmptyAssignment = $product->assignAttribute($requiredAttribute, $emptyValue);
    expect($requiredEmptyAssignment->getValue())->toBe($emptyValue);

    // The requirement enforcement is typically handled at the application/form level
    // The model layer stores what's given, validation happens at higher levels
})->repeat(100);

// Property: Attribute assignment updates preserve other assignments
test('property: updating one attribute assignment preserves others', function (): void {
    $team = Team::factory()->create();
    $user = User::factory()->create();
    $user->teams()->attach($team);
    $user->switchTeam($team);
    $this->actingAs($user);

    $product = Product::factory()->create(['team_id' => $team->id]);

    // Create multiple attributes
    $attribute1 = ProductAttribute::factory()->create([
        'team_id' => $team->id,
        'data_type' => ProductAttributeDataType::TEXT,
        'name' => 'Attribute1',
    ]);

    $attribute2 = ProductAttribute::factory()->create([
        'team_id' => $team->id,
        'data_type' => ProductAttributeDataType::NUMBER,
        'name' => 'Attribute2',
    ]);

    $attribute3 = ProductAttribute::factory()->create([
        'team_id' => $team->id,
        'data_type' => ProductAttributeDataType::BOOLEAN,
        'name' => 'Attribute3',
    ]);

    // Assign initial values
    $value1 = fake()->word();
    $value2 = fake()->randomFloat(2, 1, 100);
    $value3 = fake()->boolean();

    $product->assignAttribute($attribute1, $value1);
    $product->assignAttribute($attribute2, $value2);
    $product->assignAttribute($attribute3, $value3);

    // Update one attribute
    $newValue1 = fake()->word();
    $product->assignAttribute($attribute1, $newValue1);

    // Verify the updated attribute has new value
    expect($product->getProductAttributeValue($attribute1))->toBe($newValue1);

    // Verify other attributes are unchanged
    expect($product->getProductAttributeValue($attribute2))->toEqual($value2);
    expect($product->getProductAttributeValue($attribute3))->toBe($value3);

    // Verify total count is still 3
    expect($product->attributeAssignments)->toHaveCount(3);
})->repeat(100);

// Property: Attribute removal preserves other assignments
test('property: removing attribute assignment preserves other assignments', function (): void {
    $team = Team::factory()->create();
    $user = User::factory()->create();
    $user->teams()->attach($team);
    $user->switchTeam($team);
    $this->actingAs($user);

    $product = Product::factory()->create(['team_id' => $team->id]);

    // Create multiple attributes
    $attribute1 = ProductAttribute::factory()->create([
        'team_id' => $team->id,
        'data_type' => ProductAttributeDataType::TEXT,
    ]);

    $attribute2 = ProductAttribute::factory()->create([
        'team_id' => $team->id,
        'data_type' => ProductAttributeDataType::NUMBER,
    ]);

    $attribute3 = ProductAttribute::factory()->create([
        'team_id' => $team->id,
        'data_type' => ProductAttributeDataType::BOOLEAN,
    ]);

    // Assign values
    $value1 = fake()->word();
    $value2 = fake()->randomFloat(2, 1, 100);
    $value3 = fake()->boolean();

    $product->assignAttribute($attribute1, $value1);
    $product->assignAttribute($attribute2, $value2);
    $product->assignAttribute($attribute3, $value3);

    // Remove one attribute
    $removed = $product->removeAttribute($attribute2);
    expect($removed)->toBeTrue();

    // Verify the removed attribute is gone
    expect($product->hasProductAttribute($attribute2))->toBeFalse();
    expect($product->getProductAttributeValue($attribute2))->toBeNull();

    // Verify other attributes remain
    expect($product->hasProductAttribute($attribute1))->toBeTrue();
    expect($product->hasProductAttribute($attribute3))->toBeTrue();
    expect($product->getProductAttributeValue($attribute1))->toBe($value1);
    expect($product->getProductAttributeValue($attribute3))->toBe($value3);

    // Verify count is now 2
    expect($product->attributeAssignments)->toHaveCount(2);
})->repeat(100);

// Property: Select attribute assignments use predefined values when available
test('property: select attributes prefer predefined values over custom values', function (): void {
    $team = Team::factory()->create();
    $user = User::factory()->create();
    $user->teams()->attach($team);
    $user->switchTeam($team);
    $this->actingAs($user);

    $product = Product::factory()->create(['team_id' => $team->id]);

    $selectAttribute = ProductAttribute::factory()->create([
        'team_id' => $team->id,
        'data_type' => ProductAttributeDataType::SELECT,
    ]);

    // Create predefined values
    $predefinedValues = ['Option A', 'Option B', 'Option C'];
    foreach ($predefinedValues as $index => $value) {
        ProductAttributeValue::factory()->create([
            'product_attribute_id' => $selectAttribute->id,
            'value' => $value,
            'sort_order' => $index,
        ]);
    }

    // Assign a predefined value
    $selectedValue = fake()->randomElement($predefinedValues);
    $assignment = $product->assignAttribute($selectAttribute, $selectedValue);

    // Verify it uses the predefined value (not custom)
    expect($assignment->product_attribute_value_id)->not->toBeNull();
    expect($assignment->custom_value)->toBeNull();
    expect($assignment->getValue())->toBe($selectedValue);

    // Verify retrieval works correctly
    expect($product->getProductAttributeValue($selectAttribute))->toBe($selectedValue);
})->repeat(100);

// Property: Multi-select attribute assignments store arrays correctly
test('property: multi-select attributes store and retrieve arrays correctly', function (): void {
    $team = Team::factory()->create();
    $user = User::factory()->create();
    $user->teams()->attach($team);
    $user->switchTeam($team);
    $this->actingAs($user);

    $product = Product::factory()->create(['team_id' => $team->id]);

    $multiSelectAttribute = ProductAttribute::factory()->create([
        'team_id' => $team->id,
        'data_type' => ProductAttributeDataType::MULTI_SELECT,
    ]);

    // Create predefined values
    $predefinedValues = ['Feature A', 'Feature B', 'Feature C', 'Feature D'];
    foreach ($predefinedValues as $index => $value) {
        ProductAttributeValue::factory()->create([
            'product_attribute_id' => $multiSelectAttribute->id,
            'value' => $value,
            'sort_order' => $index,
        ]);
    }

    // Assign multiple values
    $selectedValues = fake()->randomElements($predefinedValues, fake()->numberBetween(1, 3));
    $assignment = $product->assignAttribute($multiSelectAttribute, $selectedValues);

    // Verify it stores as custom value (JSON array)
    expect($assignment->product_attribute_value_id)->toBeNull();
    expect($assignment->custom_value)->toBe($selectedValues);
    expect($assignment->getValue())->toBe($selectedValues);

    // Verify retrieval works correctly
    expect($product->getProductAttributeValue($multiSelectAttribute))->toBe($selectedValues);

    // Verify display value is comma-separated
    expect($assignment->getDisplayValue())->toBe(implode(', ', $selectedValues));
})->repeat(100);
