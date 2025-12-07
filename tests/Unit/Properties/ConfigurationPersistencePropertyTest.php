<?php

declare(strict_types=1);

use App\Models\Setting;
use App\Services\SettingsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;

uses(RefreshDatabase::class);

/**
 * Feature: system-technical, Property 1: Configuration persistence
 * Validates: Requirements 1.1, 1.2
 *
 * Property: For any system setting with a valid key, value, type, and group,
 * when the setting is stored and the cache is cleared, then retrieving the
 * setting should return the same value that was stored, demonstrating that
 * settings persist across cache clears and apply consistently.
 */
it('persists configuration settings across cache clears', function (): void {
    $service = app(SettingsService::class);

    // Generate random settings with different types
    $testCases = [
        ['key' => 'test.string.'.uniqid(), 'value' => fake()->sentence(), 'type' => 'string', 'group' => 'general'],
        ['key' => 'test.integer.'.uniqid(), 'value' => fake()->numberBetween(1, 1000), 'type' => 'integer', 'group' => 'general'],
        ['key' => 'test.boolean.'.uniqid(), 'value' => fake()->boolean(), 'type' => 'boolean', 'group' => 'general'],
        ['key' => 'test.array.'.uniqid(), 'value' => ['key1' => fake()->word(), 'key2' => fake()->word()], 'type' => 'array', 'group' => 'general'],
        ['key' => 'company.name.'.uniqid(), 'value' => fake()->company(), 'type' => 'string', 'group' => 'company'],
        ['key' => 'locale.timezone.'.uniqid(), 'value' => fake()->timezone(), 'type' => 'string', 'group' => 'locale'],
        ['key' => 'currency.rate.'.uniqid(), 'value' => fake()->randomFloat(4, 0.5, 2.0), 'type' => 'float', 'group' => 'currency'],
    ];

    foreach ($testCases as $testCase) {
        // Store the setting
        $service->set(
            $testCase['key'],
            $testCase['value'],
            $testCase['type'],
            $testCase['group']
        );

        // Verify it's cached
        $cachedValue = $service->get($testCase['key']);
        expect($cachedValue)->toBe($testCase['value']);

        // Clear all caches to simulate restart
        Cache::flush();
        $service->clearCache();

        // Retrieve from database (not cache)
        $persistedValue = $service->get($testCase['key']);

        // Assert the value persisted correctly
        expect($persistedValue)->toBe($testCase['value']);
    }
})->repeat(10);

/**
 * Property: For any setting stored with team context, the setting should
 * persist independently from global settings with the same key.
 */
it('persists team-specific settings independently from global settings', function (): void {
    $service = app(SettingsService::class);
    $team = \App\Models\Team::factory()->create();

    $key = 'test.team.setting.'.uniqid();
    $globalValue = fake()->sentence();
    $teamValue = fake()->sentence();

    // Store both global and team-specific settings
    $service->set($key, $globalValue, 'string', 'general', null);
    $service->set($key, $teamValue, 'string', 'general', $team->id);

    // Clear cache
    Cache::flush();

    // Verify both persist independently
    expect($service->get($key, null, null))->toBe($globalValue)
        ->and($service->get($key, null, $team->id))->toBe($teamValue);
})->repeat(10);

/**
 * Property: For any encrypted setting, the value should persist in encrypted
 * form in the database but decrypt correctly when retrieved.
 */
it('persists encrypted settings securely', function (): void {
    $service = app(SettingsService::class);

    $key = 'secret.key.'.uniqid();
    $secretValue = fake()->password(20);

    // Store encrypted setting
    $setting = $service->set($key, $secretValue, 'string', 'general', null, true);

    // Verify it's encrypted in database
    $dbValue = Setting::where('key', $key)->first()->value;
    expect($dbValue)->not->toBe($secretValue);

    // Clear cache
    Cache::flush();

    // Verify it decrypts correctly when retrieved
    $retrievedValue = $service->get($key);
    expect($retrievedValue)->toBe($secretValue);
})->repeat(10);

/**
 * Property: Settings in different groups should persist independently
 * and be retrievable by group.
 */
it('persists settings by group independently', function (): void {
    $service = app(SettingsService::class);

    $groups = ['company', 'locale', 'currency', 'fiscal', 'notification'];
    $settingsByGroup = [];

    foreach ($groups as $group) {
        $key = "{$group}.test.".uniqid();
        $value = fake()->word();
        $service->set($key, $value, 'string', $group);
        $settingsByGroup[$group] = ['key' => $key, 'value' => $value];
    }

    // Clear cache
    Cache::flush();

    // Verify each group's settings persist correctly
    foreach ($groups as $group) {
        $groupSettings = $service->getGroup($group);
        $expectedKey = $settingsByGroup[$group]['key'];
        $expectedValue = $settingsByGroup[$group]['value'];

        expect($groupSettings->has($expectedKey))->toBeTrue()
            ->and($groupSettings->get($expectedKey))->toBe($expectedValue);
    }
})->repeat(10);

/**
 * Property: Updating a setting should persist the new value and not create duplicates.
 */
it('persists setting updates without creating duplicates', function (): void {
    $service = app(SettingsService::class);

    $key = 'test.update.'.uniqid();
    $initialValue = fake()->word();
    $updatedValue = fake()->word();

    // Store initial value
    $service->set($key, $initialValue, 'string', 'general');

    // Update the value
    $service->set($key, $updatedValue, 'string', 'general');

    // Clear cache
    Cache::flush();

    // Verify only one setting exists with updated value
    expect(Setting::where('key', $key)->count())->toBe(1)
        ->and($service->get($key))->toBe($updatedValue);
})->repeat(10);

/**
 * Property: Complex nested array settings should persist with full structure intact.
 */
it('persists complex nested array settings correctly', function (): void {
    $service = app(SettingsService::class);

    $key = 'test.complex.'.uniqid();
    $complexValue = [
        'level1' => [
            'level2' => [
                'level3' => fake()->word(),
                'array' => [fake()->word(), fake()->word(), fake()->word()],
            ],
            'number' => fake()->numberBetween(1, 100),
        ],
        'boolean' => fake()->boolean(),
        'string' => fake()->sentence(),
    ];

    // Store complex setting
    $service->set($key, $complexValue, 'array', 'general');

    // Clear cache
    Cache::flush();

    // Verify structure persists correctly
    $retrieved = $service->get($key);
    expect($retrieved)->toBe($complexValue);
})->repeat(10);
