<?php

// Test script for Property 14: Inventory adjustment audit trail

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "=== Testing Property 14: Inventory Adjustment Audit Trail ===\n";

$passCount = 0;
$totalTests = 10;

for ($i = 1; $i <= $totalTests; $i++) {
    echo "Running test iteration $i/$totalTests... ";
    
    try {
        // Run the property test
        $result = shell_exec('php artisan test tests/Unit/Properties/ProductsInventory/InventoryAdjustmentAuditTrailPropertyTest.php --stop-on-failure 2>&1');
        
        if (strpos($result, 'PASSED') !== false && strpos($result, 'FAILED') === false) {
            echo "PASSED\n";
            $passCount++;
        } else {
            echo "FAILED\n";
            echo "Error output: " . substr($result, 0, 200) . "...\n";
        }
    } catch (Exception $e) {
        echo "FAILED - Exception: " . $e->getMessage() . "\n";
    }
    
    // Small delay between tests
    usleep(100000);
}

echo "\n=== Results ===\n";
echo "Passed: $passCount/$totalTests\n";
echo "Success Rate: " . round(($passCount / $totalTests) * 100, 1) . "%\n";

if ($passCount === $totalTests) {
    echo "✅ Property 14: Inventory adjustment audit trail - ALL TESTS PASSED\n";
} else {
    echo "❌ Property 14: Some tests failed\n";
}