<?php

declare(strict_types=1);

/**
 * MinimalTabs Performance Verification Script
 *
 * Verifies that the MinimalTabs component meets performance targets
 * after the Filament v4.3+ namespace update.
 */

require_once __DIR__ . '/../vendor/autoload.php';

use App\Filament\Components\MinimalTabs;

echo "=== MinimalTabs Performance Verification ===\n";
echo "Testing Filament v4.3+ compatibility and performance...\n\n";

// Test 1: Basic functionality
echo "1. Testing basic functionality...\n";
$startTime = microtime(true);
$tabs = MinimalTabs::make('Test')
    ->minimal()
    ->compact()
    ->minimal(false)
    ->minimal();
$endTime = microtime(true);
$basicTime = ($endTime - $startTime) * 1000;

echo "   ✅ Basic operations: {$basicTime}ms\n";
echo '   ✅ Target: < 10ms - ' . ($basicTime < 10 ? 'PASS' : 'FAIL') . "\n\n";

// Test 2: Large class list performance
echo "2. Testing large class list performance...\n";
$manyClasses = [];
for ($i = 0; $i < 1000; $i++) {
    $manyClasses[] = "class-{$i}";
}
$initialClasses = implode(' ', $manyClasses);

$startTime = microtime(true);
$tabs = MinimalTabs::make('Performance Test')
    ->extraAttributes(['class' => $initialClasses])
    ->minimal()
    ->compact();
$endTime = microtime(true);
$largeClassTime = ($endTime - $startTime) * 1000;

echo "   ✅ 1000 classes: {$largeClassTime}ms\n";
echo '   ✅ Target: < 100ms - ' . ($largeClassTime < 100 ? 'PASS' : 'FAIL') . "\n\n";

// Test 3: Memory usage
echo "3. Testing memory efficiency...\n";
$initialMemory = memory_get_usage();
$tabs = MinimalTabs::make('Memory Test');

for ($i = 0; $i < 1000; $i++) {
    $tabs->minimal($i % 2 === 0)->compact($i % 3 === 0);
}

$finalMemory = memory_get_usage();
$memoryIncrease = ($finalMemory - $initialMemory) / 1024; // KB

echo "   ✅ Memory usage: {$memoryIncrease}KB\n";
echo '   ✅ Target: < 1024KB - ' . ($memoryIncrease < 1024 ? 'PASS' : 'FAIL') . "\n\n";

// Test 4: Namespace verification
echo "4. Testing Filament v4.3+ namespace compatibility...\n";
$reflection = new ReflectionClass(MinimalTabs::class);
$parentClass = $reflection->getParentClass();
$expectedNamespace = 'Filament\Schemas\Components\Tabs';

echo "   ✅ Parent class: {$parentClass->getName()}\n";
echo "   ✅ Expected: {$expectedNamespace}\n";
echo '   ✅ Namespace: ' . ($parentClass->getName() === $expectedNamespace ? 'PASS' : 'FAIL') . "\n\n";

// Summary
echo "=== Performance Summary ===\n";
$allTestsPass = $basicTime < 10 && $largeClassTime < 100 && $memoryIncrease < 1024 && $parentClass->getName() === $expectedNamespace;

if ($allTestsPass) {
    echo "🎉 ALL TESTS PASSED - Component is optimized for Filament v4.3+\n";
    exit(0);
} else {
    echo "❌ SOME TESTS FAILED - Review performance optimizations\n";
    exit(1);
}
