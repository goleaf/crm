<?php

declare(strict_types=1);

use function Pest\Stressless\stress;

test('stressless can probe a configured endpoint', function (): void {
    $runStressTests = filter_var((string) env('RUN_STRESS_TESTS', false), FILTER_VALIDATE_BOOLEAN);
    $target = env('STRESSLESS_TARGET');

    if (! $runStressTests || blank($target)) {
        $this->markTestSkipped('Stressless is opt-in. Set RUN_STRESS_TESTS=1 and STRESSLESS_TARGET to run.');
    }

    $concurrency = max(1, (int) env('STRESSLESS_CONCURRENCY', 3));
    $duration = max(1, (int) env('STRESSLESS_DURATION', 5));
    $p95Threshold = max(1, (float) env('STRESSLESS_P95_THRESHOLD_MS', 1000));

    $result = stress($target)
        ->concurrently(requests: $concurrency)
        ->for($duration)
        ->seconds();

    expect($result->requests()->failed()->count())->toBe(0);
    expect($result->requests()->duration()->p95())->toBeLessThan($p95Threshold);
})->group('stressless', 'performance');
