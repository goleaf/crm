<?php

declare(strict_types=1);

namespace Tests\Unit\Filament\Components;

use App\Filament\Components\MinimalTabs;

/**
 * Performance tests for MinimalTabs component.
 */
it('performs class operations efficiently with large class lists', function (): void {
    // Create a tabs instance with many existing classes
    $manyClasses = [];
    for ($i = 0; $i < 1000; $i++) {
        $manyClasses[] = "class-{$i}";
    }
    $initialClasses = implode(' ', $manyClasses);

    $startTime = microtime(true);

    $tabs = MinimalTabs::make('Performance Test')
        ->extraAttributes(['class' => $initialClasses])
        ->minimal()
        ->compact()
        ->minimal(false)
        ->compact(false)
        ->minimal()
        ->compact();

    $endTime = microtime(true);
    $executionTime = $endTime - $startTime;

    // Should complete in reasonable time (less than 100ms)
    expect($executionTime)->toBeLessThan(0.1);

    // Verify correctness
    $finalClasses = $tabs->getExtraAttributes()['class'];
    expect($finalClasses)->toContain('minimal-tabs');
    expect($finalClasses)->toContain('minimal-tabs-compact');

    // Should still contain all original classes
    for ($i = 0; $i < 1000; $i++) {
        expect($finalClasses)->toContain("class-{$i}");
    }
});

it('handles memory efficiently with repeated operations', function (): void {
    $initialMemory = memory_get_usage();

    $tabs = MinimalTabs::make('Memory Test');

    // Perform many operations
    for ($i = 0; $i < 10000; $i++) {
        $tabs->minimal($i % 2 === 0)
            ->compact($i % 3 === 0);
    }

    $finalMemory = memory_get_usage();
    $memoryIncrease = $finalMemory - $initialMemory;

    // Memory increase should be reasonable (less than 1MB)
    expect($memoryIncrease)->toBeLessThan(1024 * 1024);

    // Verify final state is correct
    $classes = $tabs->getExtraAttributes()['class'];
    expect($classes)->toContain('minimal-tabs'); // 10000 % 2 === 0
    expect($classes)->not->toContain('minimal-tabs-compact'); // 10000 % 3 !== 0
});

it('scales linearly with class count', function (): void {
    $classCounts = [10, 100, 1000];
    $times = [];

    foreach ($classCounts as $count) {
        $classes = [];
        for ($i = 0; $i < $count; $i++) {
            $classes[] = "test-class-{$i}";
        }
        $classString = implode(' ', $classes);

        $startTime = microtime(true);

        $tabs = MinimalTabs::make('Scale Test')
            ->extraAttributes(['class' => $classString])
            ->minimal()
            ->compact();

        $endTime = microtime(true);
        $times[$count] = $endTime - $startTime;
    }

    // Time should scale reasonably (not exponentially)
    // 1000 classes should not take more than 10x the time of 100 classes
    expect($times[1000])->toBeLessThan($times[100] * 10);
});

it('maintains consistent performance across different operations', function (): void {
    $tabs = MinimalTabs::make('Consistency Test')
        ->extraAttributes(['class' => 'existing-class-1 existing-class-2']);

    $operations = [
        $tabs->minimal(...),
        $tabs->compact(...),
        fn (): \App\Filament\Components\MinimalTabs => $tabs->minimal(false),
        fn (): \App\Filament\Components\MinimalTabs => $tabs->compact(false),
    ];

    $times = [];

    foreach ($operations as $i => $operation) {
        $startTime = microtime(true);

        // Run operation multiple times to get average
        for ($j = 0; $j < 1000; $j++) {
            $operation();
        }

        $endTime = microtime(true);
        $times[$i] = $endTime - $startTime;
    }

    // All operations should have similar performance characteristics
    $maxTime = max($times);
    $minTime = min($times);

    // Max time should not be more than 3x min time
    expect($maxTime)->toBeLessThan($minTime * 3);
});
