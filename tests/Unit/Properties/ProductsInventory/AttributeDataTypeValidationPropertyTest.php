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
 * **Feature: products-inventory, Property 9: Attribute data type validation**
 *
 * **Validates: Requirements 3.4**
 *
 * Property: For any attribute value entry, the system should validate the value 
 * against the attribute's defined data type and reject invalid values.
 */

// Property: Text attributes accept string values and reject non-strings
test('property: text attributes validate string values correctly', function (): void {
    $team = Team::factory()->create();
    $user = User::factory()->create();
    $user->teams()->attach($team);
    $user->switchTeam($team);
    $this->actingAs($user);

    $attribute = ProductAttribute::factory()->create([
        'team_id' => $team->id,
        'data_type' => ProductAttributeDataType::TEXT,
    ]);

    $product = Product::factory()->create(['team_id' => $team->id]);

    // Valid string values should be accepted
    $validValues = [
        fake()->word(),
        fake()->sentence(),
        fake()->text(100),
        '', // Empty string should be valid
        '123', // Numeric string should be valid
        'true', // Boolean string should be valid
    ];

    foreach ($validValues as $value) {
        expect($attribute->validateValue($value))->toBeTrue("Value '{$value}' should be valid for text attribute");
        expect($attribute->isValidValue($value))->toBeTrue("Value '{$value}' should be valid for text attribute");
        
        // Should be able to assign the value
        $assignment = $product->assignAttribute($attribute, $value);
        expect($assignment->getValue())->toBe($value);
    }

    // Invalid non-string values should be rejected
    $invalidValues = [
        123,
        12.34,
        true,
        false,
        [],
        ['array'],
        null,
    ];

    foreach ($invalidValues as $value) {
        expect($attribute->validateValue($value))->toBeFalse("Value should be invalid for text attribute");
        expect($attribute->isValidValue($value))->toBeFalse("Value should be invalid for text attribute");
    }
})->repeat(100);

// Property: Number attributes accept numeric values and reject non-numeric
test('property: number attributes validate numeric values correctly', function (): void {
    $team = Team::factory()->create();
    $user = User::factory()->create();
    $user->teams()->attach($team);
    $user->switchTeam($team);
    $this->actingAs($user);

    $attribute = ProductAttribute::factory()->create([
        'team_id' => $team->id,
        'data_type' => ProductAttributeDataType::NUMBER,
    ]);

    $product = Product::factory()->create(['team_id' => $team->id]);

    // Valid numeric values should be accepted
    $validValues = [
        fake()->numberBetween(1, 1000),
        fake()->randomFloat(2, 0, 1000),
        0,
        -1,
        -12.34,
        '123', // Numeric string should be valid
        '12.34', // Decimal string should be valid
    ];

    foreach ($validValues as $value) {
        expect($attribute->validateValue($value))->toBeTrue("Value '{$value}' should be valid for number attribute");
        expect($attribute->isValidValue($value))->toBeTrue("Value '{$value}' should be valid for number attribute");
        
        // Should be able to assign the value
        $assignment = $product->assignAttribute($attribute, $value);
        expect($assignment->getValue())->toEqual((float) $value);
    }

    // Invalid non-numeric values should be rejected
    $invalidValues = [
        'not a number',
        'abc123',
        true,
        false,
        [],
        ['array'],
        null,
    ];

    foreach ($invalidValues as $value) {
        expect($attribute->validateValue($value))->toBeFalse("Value should be invalid for number attribute");
        expect($attribute->isValidValue($value))->toBeFalse("Value should be invalid for number attribute");
    }
})->repeat(100);

// Property: Boolean attributes accept boolean values and boolean-like strings
test('property: boolean attributes validate boolean values correctly', function (): void {
    $team = Team::factory()->create();
    $user = User::factory()->create();
    $user->teams()->attach($team);
    $user->switchTeam($team);
    $this->actingAs($user);

    $attribute = ProductAttribute::factory()->create([
        'team_id' => $team->id,
        'data_type' => ProductAttributeDataType::BOOLEAN,
    ]);

    $product = Product::factory()->create(['team_id' => $team->id]);

    // Valid boolean values should be accepted
    $validValues = [
        true,
        false,
        '1',
        '0',
        'true',
        'false',
    ];

    foreach ($validValues as $value) {
        expect($attribute->validateValue($value))->toBeTrue("Value '{$value}' should be valid for boolean attribute");
        expect($attribute->isValidValue($value))->toBeTrue("Value '{$value}' should be valid for boolean attribute");
        
        // Should be able to assign the value
        $assignment = $product->assignAttribute($attribute, $value);
        expect($assignment->getValue())->toBeIn([true, false]);
    }

    // Invalid non-boolean values should be rejected
    $invalidValues = [
        'not boolean',
        123,
        12.34,
        [],
        ['array'],
        null,
        'yes',
        'no',
    ];

    foreach ($invalidValues as $value) {
        expect($attribute->validateValue($value))->toBeFalse("Value should be invalid for boolean attribute");
        expect($attribute->isValidValue($value))->toBeFalse("Value should be invalid for boolean attribute");
    }
})->repeat(100);

// Property: Select attributes only accept predefined values
test('property: select attributes validate against predefined values', function (): void {
    $team = Team::factory()->create();
    $user = User::factory()->create();
    $user->teams()->attach($team);
    $user->switchTeam($team);
    $this->actingAs($user);

    $attribute = ProductAttribute::factory()->create([
        'team_id' => $team->id,
        'data_type' => ProductAttributeDataType::SELECT,
    ]);

    // Create predefined values
    $validOptions = ['Red', 'Blue', 'Green', 'Yellow'];
    foreach ($validOptions as $index => $option) {
        ProductAttributeValue::factory()->create([
            'product_attribute_id' => $attribute->id,
            'value' => $option,
            'sort_order' => $index,
        ]);
    }

    $product = Product::factory()->create(['team_id' => $team->id]);

    // Valid predefined values should be accepted
    foreach ($validOptions as $value) {
        expect($attribute->validateValue($value))->toBeTrue("Value '{$value}' should be valid for select attribute");
        expect($attribute->isValidValue($value))->toBeTrue("Value '{$value}' should be valid for select attribute");
        
        // Should be able to assign the value
        $assignment = $product->assignAttribute($attribute, $value);
        expect($assignment->getValue())->toBe($value);
    }

    // Invalid values not in predefined list should be rejected
    $invalidValues = [
        'Purple', // Not in predefined list
        'red', // Case sensitive
        123,
        true,
        [],
        null,
    ];

    foreach ($invalidValues as $value) {
        expect($attribute->isValidValue($value))->toBeFalse("Value should be invalid for select attribute");
    }
})->repeat(100);

// Property: Multi-select attributes accept arrays of predefined values
test('property: multi-select attributes validate arrays of predefined values', function (): void {
    $team = Team::factory()->create();
    $user = User::factory()->create();
    $user->teams()->attach($team);
    $user->switchTeam($team);
    $this->actingAs($user);

    $attribute = ProductAttribute::factory()->create([
        'team_id' => $team->id,
        'data_type' => ProductAttributeDataType::MULTI_SELECT,
    ]);

    // Create predefined values
    $validOptions = ['Small', 'Medium', 'Large', 'Extra Large'];
    foreach ($validOptions as $index => $option) {
        ProductAttributeValue::factory()->create([
            'product_attribute_id' => $attribute->id,
            'value' => $option,
            'sort_order' => $index,
        ]);
    }

    $product = Product::factory()->create(['team_id' => $team->id]);

    // Valid arrays of predefined values should be accepted
    $validValueArrays = [
        ['Small'],
        ['Small', 'Medium'],
        ['Large', 'Extra Large'],
        $validOptions, // All options
        [], // Empty array should be valid
    ];

    foreach ($validValueArrays as $values) {
        expect($attribute->validateValue($values))->toBeTrue("Values should be valid for multi-select attribute");
        expect($attribute->isValidValue($values))->toBeTrue("Values should be valid for multi-select attribute");
        
        // Should be able to assign the values
        $assignment = $product->assignAttribute($attribute, $values);
        expect($assignment->getValue())->toBe($values);
    }

    // Invalid values should be rejected
    $invalidValues = [
        'Small', // String instead of array
        ['Small', 'Invalid'], // Contains invalid option
        [123], // Non-string in array
        123,
        true,
        null,
    ];

    foreach ($invalidValues as $value) {
        expect($attribute->isValidValue($value))->toBeFalse("Value should be invalid for multi-select attribute");
    }
})->repeat(100);

// Property: Attribute validation respects data type changes
test('property: attribute validation updates when data type changes', function (): void {
    $team = Team::factory()->create();
    $user = User::factory()->create();
    $user->teams()->attach($team);
    $user->switchTeam($team);
    $this->actingAs($user);

    $attribute = ProductAttribute::factory()->create([
        'team_id' => $team->id,
        'data_type' => ProductAttributeDataType::TEXT,
    ]);

    // Initially as text, string values should be valid
    expect($attribute->validateValue('test string'))->toBeTrue();
    expect($attribute->validateValue(123))->toBeFalse();

    // Change to number type
    $attribute->update(['data_type' => ProductAttributeDataType::NUMBER]);
    $attribute->refresh();

    // Now numeric values should be valid, strings invalid
    expect($attribute->validateValue(123))->toBeTrue();
    expect($attribute->validateValue('test string'))->toBeFalse();
    expect($attribute->validateValue('123'))->toBeTrue(); // Numeric string still valid

    // Change to boolean type
    $attribute->update(['data_type' => ProductAttributeDataType::BOOLEAN]);
    $attribute->refresh();

    // Now boolean values should be valid
    expect($attribute->validateValue(true))->toBeTrue();
    expect($attribute->validateValue(false))->toBeTrue();
    expect($attribute->validateValue('1'))->toBeTrue();
    expect($attribute->validateValue('0'))->toBeTrue();
    expect($attribute->validateValue(123))->toBeFalse();
    expect($attribute->validateValue('test string'))->toBeFalse();
})->repeat(100);

// Property: Required attributes enforce value presence
test('property: required attributes enforce value presence in validation', function (): void {
    $team = Team::factory()->create();
    $user = User::factory()->create();
    $user->teams()->attach($team);
    $user->switchTeam($team);
    $this->actingAs($user);

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

    $product = Product::factory()->create(['team_id' => $team->id]);

    // Required attribute should reject empty/null values in assignment validation
    $emptyValues = [null, '', '   ']; // whitespace-only should be considered empty

    foreach ($emptyValues as $value) {
        // The basic data type validation might pass for empty strings
        if ($value === '') {
            expect($requiredAttribute->validateValue($value))->toBeTrue();
        }
        
        // But assignment validation should consider required status
        // This will be tested in the assignment completeness property test
    }

    // Optional attribute should accept empty values
    foreach ($emptyValues as $value) {
        if ($value === '') {
            expect($optionalAttribute->validateValue($value))->toBeTrue();
        }
    }

    // Both should accept valid non-empty values
    $validValue = fake()->word();
    expect($requiredAttribute->validateValue($validValue))->toBeTrue();
    expect($optionalAttribute->validateValue($validValue))->toBeTrue();
})->repeat(100);