<?php

declare(strict_types=1);

require 'vendor/autoload.php';

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Enums\ProductAttributeDataType;
use App\Models\Product;
use App\Models\ProductAttribute;
use App\Models\Team;
use App\Models\User;

echo "Testing attribute validation functionality...\n";

try {
    // Create test data
    $team = Team::factory()->create();
    $user = User::factory()->create();
    $user->teams()->attach($team);
    $user->switchTeam($team);

    // Create a text attribute
    $attribute = ProductAttribute::factory()->create([
        'team_id' => $team->id,
        'data_type' => ProductAttributeDataType::TEXT,
    ]);

    $product = Product::factory()->create(['team_id' => $team->id]);

    echo "Created test data successfully\n";

    // Test text validation
    $validString = 'test string';
    $invalidNumber = 123;

    $textValid = $attribute->validateValue($validString);
    $numberInvalid = $attribute->validateValue($invalidNumber);

    echo 'Text validation - valid string: ' . ($textValid ? 'PASS' : 'FAIL') . "\n";
    echo 'Text validation - invalid number: ' . ($numberInvalid ? 'FAIL' : 'PASS') . "\n";

    // Test assignment
    $assignment = $product->assignAttribute($attribute, $validString);
    $retrievedValue = $assignment->getValue();

    echo 'Assignment test: ' . ($retrievedValue === $validString ? 'PASS' : 'FAIL') . "\n";

    // Test number attribute
    $numberAttribute = ProductAttribute::factory()->create([
        'team_id' => $team->id,
        'data_type' => ProductAttributeDataType::NUMBER,
    ]);

    $validNumber = 123.45;
    $invalidString = 'not a number';

    $numberValid = $numberAttribute->validateValue($validNumber);
    $stringInvalid = $numberAttribute->validateValue($invalidString);

    echo 'Number validation - valid number: ' . ($numberValid ? 'PASS' : 'FAIL') . "\n";
    echo 'Number validation - invalid string: ' . ($stringInvalid ? 'FAIL' : 'PASS') . "\n";

    echo "\nAll basic functionality tests completed!\n";

} catch (Exception $e) {
    echo 'Error: ' . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}
