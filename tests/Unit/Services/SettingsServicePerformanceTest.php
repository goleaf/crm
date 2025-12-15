<?php

declare(strict_types=1);

use App\Models\Setting;
use App\Models\Team;
use App\Services\SettingsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->service = resolve(SettingsService::class);
});

it('caches settings to reduce database queries', function (): void {
    $this->service->set('perf.cache', 'value', 'string', 'general');

    DB::enableQueryLog();
    DB::flushQueryLog();

    // First call - should query database
    $this->service->get('perf.cache');
    $firstCallQueries = count(DB::getQueryLog());

    DB::flushQueryLog();

    // Second call - should use cache
    $this->service->get('perf.cache');
    $secondCallQueries = count(DB::getQueryLog());

    expect($firstCallQueries)->toBeGreaterThan(0)
        ->and($secondCallQueries)->toBe(0);
});

it('handles bulk operations efficiently', function (): void {
    $settings = [];
    for ($i = 0; $i < 100; $i++) {
        $settings["bulk.key{$i}"] = "value{$i}";
    }

    DB::enableQueryLog();
    DB::flushQueryLog();

    $this->service->setMany($settings, 'bulk');

    $queryCount = count(DB::getQueryLog());

    // Should not exceed reasonable query count (allowing for upserts)
    expect($queryCount)->toBeLessThan(250); // ~2 queries per setting (select + insert/update)
});

it('retrieves group settings efficiently', function (): void {
    // Create 50 settings in the same group
    for ($i = 0; $i < 50; $i++) {
        Setting::create([
            'key' => "group.key{$i}",
            'value' => "value{$i}",
            'type' => 'string',
            'group' => 'test_group',
        ]);
    }

    DB::enableQueryLog();
    DB::flushQueryLog();

    $this->service->getGroup('test_group');

    $queryCount = count(DB::getQueryLog());

    // Should use a single query to fetch all group settings
    expect($queryCount)->toBe(1);
});

it('handles concurrent reads efficiently', function (): void {
    $this->service->set('concurrent.read', 'value', 'string', 'general');

    DB::enableQueryLog();
    DB::flushQueryLog();

    // Simulate multiple concurrent reads
    for ($i = 0; $i < 10; $i++) {
        $this->service->get('concurrent.read');
    }

    $queryCount = count(DB::getQueryLog());

    // After first read, subsequent reads should use cache
    expect($queryCount)->toBeLessThanOrEqual(1);
});

it('minimizes queries for team-specific settings', function (): void {
    $team = Team::factory()->create();

    $this->service->set('team.setting', 'value', 'string', 'general', $team->id);

    DB::enableQueryLog();
    DB::flushQueryLog();

    $this->service->get('team.setting', null, $team->id);
    $this->service->get('team.setting', null, $team->id);

    $queryCount = count(DB::getQueryLog());

    // Second call should use cache
    expect($queryCount)->toBeLessThanOrEqual(1);
});

it('handles large value storage efficiently', function (): void {
    $largeValue = str_repeat('a', 50000);

    $startTime = microtime(true);
    $this->service->set('large.value', $largeValue, 'string', 'general');
    $setTime = microtime(true) - $startTime;

    $startTime = microtime(true);
    $retrieved = $this->service->get('large.value');
    $getTime = microtime(true) - $startTime;

    expect($retrieved)->toBe($largeValue)
        ->and($setTime)->toBeLessThan(1.0) // Should complete in under 1 second
        ->and($getTime)->toBeLessThan(0.5); // Retrieval should be faster
});

it('handles encryption overhead acceptably', function (): void {
    $value = 'secret value';

    // Non-encrypted
    $startTime = microtime(true);
    $this->service->set('plain.value', $value, 'string', 'general', null, false);
    $plainTime = microtime(true) - $startTime;

    // Encrypted
    $startTime = microtime(true);
    $this->service->set('encrypted.value', $value, 'string', 'general', null, true);
    $encryptedTime = microtime(true) - $startTime;

    // Encryption should not add excessive overhead (allow 10x slower)
    expect($encryptedTime)->toBeLessThan($plainTime * 10);
});

it('scales well with increasing number of settings', function (): void {
    $counts = [10, 50, 100];
    $times = [];

    foreach ($counts as $count) {
        Cache::flush();
        Setting::query()->delete();

        $startTime = microtime(true);

        for ($i = 0; $i < $count; $i++) {
            $this->service->set("scale.key{$i}", "value{$i}", 'string', 'general');
        }

        $times[$count] = microtime(true) - $startTime;
    }

    // Time should scale roughly linearly, not exponentially
    // Allow some variance but check it's not exponential growth
    $ratio = $times[100] / $times[10];
    expect($ratio)->toBeLessThan(15); // Should be ~10x, allow up to 15x
});

it('cache invalidation is fast', function (): void {
    $this->service->set('cache.invalidate', 'value', 'string', 'general');
    $this->service->get('cache.invalidate'); // Cache it

    $startTime = microtime(true);
    $this->service->clearCache('cache.invalidate');
    $clearTime = microtime(true) - $startTime;

    expect($clearTime)->toBeLessThan(0.1); // Should be nearly instant
});

it('handles mixed operations efficiently', function (): void {
    DB::enableQueryLog();
    DB::flushQueryLog();

    // Mix of operations
    $this->service->set('mixed.key1', 'value1', 'string', 'general');
    $this->service->get('mixed.key1');
    $this->service->set('mixed.key2', 'value2', 'string', 'general');
    $this->service->get('mixed.key1'); // Should use cache
    $this->service->get('mixed.key2');
    $this->service->delete('mixed.key1');

    $queryCount = count(DB::getQueryLog());

    // Should be efficient with caching
    expect($queryCount)->toBeLessThan(10);
});

it('memory usage stays reasonable with large datasets', function (): void {
    $memoryBefore = memory_get_usage();

    // Create 1000 settings
    for ($i = 0; $i < 1000; $i++) {
        Setting::create([
            'key' => "memory.key{$i}",
            'value' => "value{$i}",
            'type' => 'string',
            'group' => 'memory',
        ]);
    }

    $memoryAfter = memory_get_usage();
    $memoryUsed = ($memoryAfter - $memoryBefore) / 1024 / 1024; // Convert to MB

    // Should not use excessive memory (allow 50MB for 1000 settings)
    expect($memoryUsed)->toBeLessThan(50);
});
