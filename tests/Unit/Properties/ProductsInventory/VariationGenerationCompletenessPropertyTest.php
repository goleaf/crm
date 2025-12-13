<?php

declare(strict_types=1);

// Feature: products-inventory, Property 11: Variation generation completeness

use App\Models\Product;
use App\Models\ProductAttribute;
use App\Models\ProductAttributeValue;
use App\Models\Team;
use App\Services\Products\VariationService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/**
 * Property 11: Variation generation completeness
 * 
 * For any product with N configurable attributes where attribute i has V_i values,
 * generating variations should create exactly V_1 × V_2 × ... × V_N variations
 * with all unique combinations.
 * 
 * Validates: Requirements 4.2
 */
it('generates complete cartesian product of attribute combinations', function () {
    $team = Team::factory()->create();
    $product = Product::factory()->create(['team_id' => $team->id]);
    $variationService = app(VariationService::class);

    // Generate random number of attributes (2-4 for reasonable test execution time)
    $numAttributes = fake()->numberBetween(2, 4);
    $attributes = [];
    $expectedCombinations = 1;

    for ($i = 0; $i < $numAttributes; $i++) {
        // Create attribute with random number of values (2-5)
        $numValues = fake()->numberBetween(2, 5);
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
    expect($variations)->toHaveCount($expectedCombinations);

    // Verify all combinations are unique
    $optionSets = $variations->map(fn($variation) => json_encode($variation->options))->toArray();
    $uniqueOptionSets = array_unique($optionSets);
    expect($uniqueOptionSets)->toHaveCount($expectedCombinations);

    // Verify each variation has all attributes represented
    foreach ($variations as $variation) {
        expect($variation->options)->toHaveCount($numAttributes);
        
        foreach ($attributes as $attribute) {
            expect($variation->options)->toHaveKey($attribute->slug);
            
            // Verify the value is one of the valid values for this attribute
            $validValues = $attribute->values->pluck('value')->toArray();
            expect($validValues)->toContain($variation->options[$attribute->slug]);
        }
    }

    // Verify cartesian product completeness by checking all possible combinations exist
    $allPossibleCombinations = [];
    $attributeSlugs = collect($attributes)->pluck('slug')->toArray();
    
    // Build all possible combinations manually to verify completeness
    $valueSets = [];
    foreach ($attributes as $attribute) {
        $valueSets[$attribute->slug] = $attribute->values->pluck('value')->toArray();
    }
    
    // Generate expected combinations using recursive approach
    $expectedCombinations = generateCartesianProduct($valueSets);
    
    // Convert variations to comparable format
    $actualCombinations = $variations->map(fn($v) => $v->options)->toArray();
    
    // Sort both arrays for comparison
    sort($expectedCombinations);
    sort($actualCombinations);
    
    expect($actualCombinations)->toHaveCount(count($expectedCombinations));
    
    // Verify each expected combination exists in actual results
    foreach ($expectedCombinations as $expectedCombo) {
        $found = false;
        foreach ($actualCombinations as $actualCombo) {
            if (arraysEqual($expectedCombo, $actualCombo)) {
                $found = true;
                break;
            }
        }
        expect($found)->toBeTrue("Expected combination not found: " . json_encode($expectedCombo));
    }
})->repeat(100);

/**
 * Helper function to generate cartesian product of value sets
 */
function generateCartesianProduct(array $sets): array
{
    $result = [[]];
    
    foreach ($sets as $attributeSlug => $values) {
        $temp = [];
        foreach ($result as $combination) {
            foreach ($values as $value) {
                $newCombination = $combination;
                $newCombination[$attributeSlug] = $value;
                $temp[] = $newCombination;
            }
        }
        $result = $temp;
    }
    
    return $result;
}

/**
 * Helper function to compare arrays for equality (order-independent)
 */
function arraysEqual(array $a, array $b): bool
{
    if (count($a) !== count($b)) {
        return false;
    }
    
    foreach ($a as $key => $value) {
        if (!array_key_exists($key, $b) || $b[$key] !== $value) {
            return false;
        }
    }
    
    return true;
}

/**
 * Property 11b: Empty attributes should generate no variations
 */
it('generates no variations when no configurable attributes exist', function () {
    $team = Team::factory()->create();
    $product = Product::factory()->create(['team_id' => $team->id]);
    $variationService = app(VariationService::class);

    // Generate variations with empty attribute array
    $variations = $variationService->generateVariations($product, []);

    expect($variations)->toHaveCount(0);
})->repeat(10);

/**
 * Property 11c: Attributes without values should be skipped
 */
it('skips attributes that have no values defined', function () {
    $team = Team::factory()->create();
    $product = Product::factory()->create(['team_id' => $team->id]);
    $variationService = app(VariationService::class);

    // Create one attribute with values and one without
    $attributeWithValues = ProductAttribute::factory()->create([
        'team_id' => $team->id,
        'name' => 'Color',
        'slug' => 'color',
        'data_type' => 'select',
        'is_configurable' => true,
    ]);

    $attributeWithoutValues = ProductAttribute::factory()->create([
        'team_id' => $team->id,
        'name' => 'Size',
        'slug' => 'size',
        'data_type' => 'select',
        'is_configurable' => true,
    ]);

    // Add values only to the first attribute
    ProductAttributeValue::factory()->create([
        'product_attribute_id' => $attributeWithValues->id,
        'value' => 'Red',
        'sort_order' => 0,
    ]);
    ProductAttributeValue::factory()->create([
        'product_attribute_id' => $attributeWithValues->id,
        'value' => 'Blue',
        'sort_order' => 1,
    ]);

    // Associate both attributes as configurable
    $product->configurableAttributes()->attach([
        $attributeWithValues->id,
        $attributeWithoutValues->id,
    ]);

    // Generate variations
    $variations = $variationService->generateVariations($product, [
        $attributeWithValues->id,
        $attributeWithoutValues->id,
    ]);

    // Should generate variations only for the attribute with values (2 variations for Red and Blue)
    // The attribute without values should be skipped
    expect($variations)->toHaveCount(2);
    
    // Verify each variation only has the attribute with values
    foreach ($variations as $variation) {
        expect($variation->options)->toHaveKey('color');
        expect($variation->options)->not->toHaveKey('size');
        expect(['Red', 'Blue'])->toContain($variation->options['color']);
    }
})->repeat(10);