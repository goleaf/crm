<?php

declare(strict_types=1);

use App\Models\Product;
use App\Models\Team;

// Feature: products-inventory, Property 17: Custom field validation

it('validates custom field values against their rules and rejects invalid values', function () {
    // Create a team for multi-tenancy
    $team = Team::factory()->create();
    
    // Create various types of custom fields using the helper function
    $textField = createCustomFieldFor(Product::class, 'brand_name', 'text', [], $team);
    $numberField = createCustomFieldFor(Product::class, 'weight', 'number', [], $team);
    $emailField = createCustomFieldFor(Product::class, 'support_email', 'email', [], $team);
    $selectField = createCustomFieldFor(Product::class, 'color', 'select', ['red', 'blue'], $team);
    $booleanField = createCustomFieldFor(Product::class, 'eco_friendly', 'boolean', [], $team);
    
    // Create a product for testing
    $product = Product::factory()->create([
        'team_id' => $team->id,
        'name' => 'Test Product',
    ]);
    
    // Test valid values - should pass validation
    $validData = [
        $textField->id => 'Valid Brand Name',
        $numberField->id => '10.5',
        $emailField->id => 'support@example.com',
        $selectField->id => 'red',
        $booleanField->id => true,
    ];
    
    $validationErrors = $product->validateCustomFields($validData);
    expect($validationErrors)->toBeEmpty();
    
    // Test number field validation - should fail for non-numeric values
    $invalidNumberData = [
        $textField->id => 'Valid Brand Name',
        $numberField->id => 'not-a-number',
    ];
    
    $validationErrors = $product->validateCustomFields($invalidNumberData);
    expect($validationErrors)->toHaveKey($numberField->id);
    expect($validationErrors[$numberField->id])->toContain('number');
    
    // Test email field validation - should fail for invalid email format
    $invalidEmailData = [
        $textField->id => 'Valid Brand Name',
        $emailField->id => 'invalid-email-format',
    ];
    
    $validationErrors = $product->validateCustomFields($invalidEmailData);
    expect($validationErrors)->toHaveKey($emailField->id);
    expect($validationErrors[$emailField->id])->toContain('email');
    
    // Test select field validation - should fail for invalid option
    $invalidSelectData = [
        $textField->id => 'Valid Brand Name',
        $selectField->id => 'invalid-color',
    ];
    
    $validationErrors = $product->validateCustomFields($invalidSelectData);
    expect($validationErrors)->toHaveKey($selectField->id);
    expect($validationErrors[$selectField->id])->toContain('invalid');
    
    // Test boolean field validation - should fail for invalid boolean values
    $invalidBooleanData = [
        $textField->id => 'Valid Brand Name',
        $booleanField->id => 'not-a-boolean',
    ];
    
    $validationErrors = $product->validateCustomFields($invalidBooleanData);
    expect($validationErrors)->toHaveKey($booleanField->id);
    expect($validationErrors[$booleanField->id])->toContain('true or false');
    
    // Test that valid boolean values pass (including string representations)
    $validBooleanData = [
        $textField->id => 'Valid Brand Name',
        $booleanField->id => '1', // String representation should be valid
    ];
    
    $validationErrors = $product->validateCustomFields($validBooleanData);
    expect($validationErrors)->not->toHaveKey($booleanField->id);
    
    // Test that null/empty values are allowed for non-required fields
    $nullValueData = [
        $textField->id => 'Valid Brand Name',
        $numberField->id => null,
        $emailField->id => '',
    ];
    
    $validationErrors = $product->validateCustomFields($nullValueData);
    expect($validationErrors)->not->toHaveKey($numberField->id);
    expect($validationErrors)->not->toHaveKey($emailField->id);
});