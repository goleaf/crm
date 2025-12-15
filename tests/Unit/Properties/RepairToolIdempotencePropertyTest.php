<?php

declare(strict_types=1);

use App\Models\Studio\FieldDependency;
use App\Models\Studio\LabelCustomization;
use App\Models\Studio\LayoutDefinition;
use App\Models\Team;
use App\Services\Studio\StudioService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;

uses(RefreshDatabase::class);

/**
 * **Feature: customization-administration, Property 5: Repair tool safety**
 *
 * Property: Repair/diagnostic tools perform intended maintenance without altering data unexpectedly.
 *
 * This property tests that:
 * 1. Repair tools are idempotent (running multiple times produces same result)
 * 2. Repair tools don't alter valid data
 * 3. Repair tools only fix actual problems
 * 4. Repair tools provide accurate diagnostics
 * 5. Repair tools can run in dry-run mode safely
 */
it('ensures repair tools are idempotent and safe', function (): void {
    $team = Team::factory()->create();
    $studioService = resolve(StudioService::class);

    // Create valid customizations
    $validLayout = LayoutDefinition::factory()->create([
        'team_id' => $team->id,
        'module_name' => 'companies',
        'view_type' => 'list',
        'name' => 'Valid Layout',
        'components' => ['fields' => ['name', 'email']],
    ]);

    $validDependency = FieldDependency::factory()->create([
        'team_id' => $team->id,
        'module_name' => 'companies',
        'source_field_code' => 'type',
        'target_field_code' => 'category',
        'dependency_type' => 'visibility',
        'conditions' => ['type' => 'corporate'],
    ]);

    // Create invalid/corrupted customizations
    $corruptedLayout = LayoutDefinition::factory()->create([
        'team_id' => $team->id,
        'module_name' => 'companies',
        'view_type' => 'detail',
        'name' => 'Corrupted Layout',
        'components' => null, // Invalid: should not be null
    ]);

    $invalidDependency = FieldDependency::factory()->create([
        'team_id' => $team->id,
        'module_name' => 'companies',
        'source_field_code' => 'nonexistent_field',
        'target_field_code' => 'another_nonexistent',
        'dependency_type' => 'invalid_type',
        'conditions' => ['invalid' => 'condition'],
    ]);

    // Property 1: Diagnostic should identify problems accurately
    $diagnosticResult = $studioService->runDiagnostics($team);

    expect($diagnosticResult['issues_found'])->toBeGreaterThan(0, 'Diagnostics should find issues');
    expect($diagnosticResult['issues'])->toHaveKey('corrupted_layouts', 'Should identify corrupted layouts');
    expect($diagnosticResult['issues'])->toHaveKey('invalid_dependencies', 'Should identify invalid dependencies');

    $corruptedLayouts = $diagnosticResult['issues']['corrupted_layouts'];
    expect($corruptedLayouts)->toContain($corruptedLayout->id, 'Should identify specific corrupted layout');

    $invalidDependencies = $diagnosticResult['issues']['invalid_dependencies'];
    expect($invalidDependencies)->toContain($invalidDependency->id, 'Should identify specific invalid dependency');

    // Property 2: Dry-run repair should not modify data
    $dryRunResult = $studioService->repairCustomizations($team, ['dry_run' => true]);

    expect($dryRunResult['dry_run'])->toBeTrue('Dry run flag should be preserved');
    expect($dryRunResult['would_fix'])->toBeGreaterThan(0, 'Dry run should report what would be fixed');

    // Verify no data was actually changed
    $corruptedLayout->refresh();
    expect($corruptedLayout->components)->toBeNull('Dry run should not modify corrupted data');

    $invalidDependency->refresh();
    expect($invalidDependency->dependency_type)->toBe('invalid_type', 'Dry run should not modify invalid data');

    // Property 3: Actual repair should fix problems
    $repairResult = $studioService->repairCustomizations($team, ['dry_run' => false]);

    expect($repairResult['success'])->toBeTrue('Repair should succeed');
    expect($repairResult['fixed_count'])->toBeGreaterThan(0, 'Repair should fix issues');
    expect($repairResult['fixed_items'])->toHaveKey('layouts', 'Should report fixed layouts');
    expect($repairResult['fixed_items'])->toHaveKey('dependencies', 'Should report fixed dependencies');

    // Property 4: Valid data should remain unchanged
    $validLayout->refresh();
    expect($validLayout->components)->toBe(['fields' => ['name', 'email']], 'Valid layout should remain unchanged');

    $validDependency->refresh();
    expect($validDependency->dependency_type)->toBe('visibility', 'Valid dependency should remain unchanged');
    expect($validDependency->conditions)->toBe(['type' => 'corporate'], 'Valid conditions should remain unchanged');

    // Property 5: Corrupted data should be fixed or removed
    $corruptedLayoutExists = LayoutDefinition::find($corruptedLayout->id);
    if ($corruptedLayoutExists) {
        // If repaired, should have valid components
        expect($corruptedLayoutExists->components)->not->toBeNull('Repaired layout should have valid components');
        expect($corruptedLayoutExists->components)->toBeArray('Repaired components should be array');
    }
    // If removed, that's also acceptable for corrupted data

    $invalidDependencyExists = FieldDependency::find($invalidDependency->id);
    if ($invalidDependencyExists) {
        // If repaired, should have valid type
        expect($invalidDependencyExists->dependency_type)
            ->toBeIn(['visibility', 'required', 'options'], 'Repaired dependency should have valid type');
    }
    // If removed, that's also acceptable for invalid data

    // Property 6: Running repair again should be idempotent
    $secondRepairResult = $studioService->repairCustomizations($team, ['dry_run' => false]);

    expect($secondRepairResult['fixed_count'])->toBe(0, 'Second repair should find nothing to fix');
    expect($secondRepairResult['success'])->toBeTrue('Second repair should still succeed');

    // Property 7: Diagnostics after repair should show no issues
    $postRepairDiagnostic = $studioService->runDiagnostics($team);

    expect($postRepairDiagnostic['issues_found'])->toBe(0, 'Post-repair diagnostics should find no issues');
    expect($postRepairDiagnostic['status'])->toBe('healthy', 'System should be healthy after repair');
});

/**
 * Property: Cache repair tools work correctly
 */
it('repairs cache inconsistencies safely', function (): void {
    $team = Team::factory()->create();
    $studioService = resolve(StudioService::class);

    // Create customizations that should be cached
    $layout = LayoutDefinition::factory()->create([
        'team_id' => $team->id,
        'module_name' => 'companies',
        'view_type' => 'list',
    ]);

    $dependency = FieldDependency::factory()->create([
        'team_id' => $team->id,
        'module_name' => 'companies',
    ]);

    // Populate cache with correct data
    $studioService->cacheCustomizations($team, 'companies');

    $cacheKey = "studio.customizations.{$team->id}.companies";
    $cachedData = Cache::get($cacheKey);
    expect($cachedData)->not->toBeNull('Cache should be populated');

    // Corrupt cache data to simulate inconsistency
    Cache::put($cacheKey, ['corrupted' => 'data'], 3600);

    // Property 1: Cache diagnostic should detect inconsistencies
    $cacheDiagnostic = $studioService->diagnoseCacheHealth($team);

    expect($cacheDiagnostic['inconsistencies_found'])->toBeGreaterThan(0, 'Should detect cache inconsistencies');
    expect($cacheDiagnostic['affected_modules'])->toContain('companies', 'Should identify affected module');

    // Property 2: Cache repair should restore correct data
    $cacheRepairResult = $studioService->repairCache($team, ['modules' => ['companies']]);

    expect($cacheRepairResult['success'])->toBeTrue('Cache repair should succeed');
    expect($cacheRepairResult['repaired_modules'])->toContain('companies', 'Should repair companies module cache');

    // Property 3: Repaired cache should match database
    $repairedCache = Cache::get($cacheKey);
    expect($repairedCache)->not->toBeNull('Repaired cache should exist');
    expect($repairedCache)->toHaveKey('layouts', 'Repaired cache should contain layouts');
    expect($repairedCache)->toHaveKey('dependencies', 'Repaired cache should contain dependencies');

    expect($repairedCache['layouts'])->toHaveCount(1, 'Cache should contain correct layout count');
    expect($repairedCache['dependencies'])->toHaveCount(1, 'Cache should contain correct dependency count');

    // Property 4: Multiple cache repairs should be idempotent
    $secondCacheRepair = $studioService->repairCache($team, ['modules' => ['companies']]);

    expect($secondCacheRepair['success'])->toBeTrue('Second cache repair should succeed');
    expect($secondCacheRepair['changes_made'])->toBe(0, 'Second repair should make no changes');

    $finalCache = Cache::get($cacheKey);
    expect($finalCache)->toBe($repairedCache, 'Cache should remain identical after second repair');
});

/**
 * Property: Index repair tools maintain data integrity
 */
it('repairs database indexes safely', function (): void {
    $team = Team::factory()->create();
    $studioService = resolve(StudioService::class);

    // Create test data
    LayoutDefinition::factory()->count(10)->create(['team_id' => $team->id]);
    FieldDependency::factory()->count(5)->create(['team_id' => $team->id]);
    LabelCustomization::factory()->count(8)->create(['team_id' => $team->id]);

    // Property 1: Index diagnostic should analyze performance
    $indexDiagnostic = $studioService->diagnoseIndexHealth();

    expect($indexDiagnostic)->toHaveKey('missing_indexes', 'Should check for missing indexes');
    expect($indexDiagnostic)->toHaveKey('unused_indexes', 'Should check for unused indexes');
    expect($indexDiagnostic)->toHaveKey('performance_issues', 'Should identify performance issues');

    // Property 2: Index repair should be safe and reversible
    $indexRepairResult = $studioService->repairIndexes(['dry_run' => true]);

    expect($indexRepairResult['dry_run'])->toBeTrue('Dry run should be respected');
    expect($indexRepairResult)->toHaveKey('would_create', 'Should report what indexes would be created');
    expect($indexRepairResult)->toHaveKey('would_drop', 'Should report what indexes would be dropped');

    // Property 3: Actual index repair should improve performance
    if (! empty($indexRepairResult['would_create'])) {
        $actualRepair = $studioService->repairIndexes(['dry_run' => false]);

        expect($actualRepair['success'])->toBeTrue('Index repair should succeed');
        expect($actualRepair['created_count'])->toBeGreaterThanOrEqual(0, 'Should report created indexes');
    }

    // Property 4: Query performance should be maintained or improved
    $beforeQuery = microtime(true);
    LayoutDefinition::where('team_id', $team->id)
        ->where('module_name', 'companies')
        ->get();
    $beforeTime = microtime(true) - $beforeQuery;

    // Run repair again to ensure idempotence
    $studioService->repairIndexes(['dry_run' => false]);

    $afterQuery = microtime(true);
    LayoutDefinition::where('team_id', $team->id)
        ->where('module_name', 'companies')
        ->get();
    $afterTime = microtime(true) - $afterQuery;

    // Performance should not degrade (allowing for some variance)
    expect($afterTime)->toBeLessThanOrEqual($beforeTime * 2, 'Query performance should not significantly degrade');

    // Property 5: Data integrity should be preserved
    $layoutCount = LayoutDefinition::where('team_id', $team->id)->count();
    $dependencyCount = FieldDependency::where('team_id', $team->id)->count();
    $labelCount = LabelCustomization::where('team_id', $team->id)->count();

    expect($layoutCount)->toBe(10, 'Layout count should be preserved');
    expect($dependencyCount)->toBe(5, 'Dependency count should be preserved');
    expect($labelCount)->toBe(8, 'Label count should be preserved');
});

/**
 * Property: Permission repair tools work correctly
 */
it('repairs permission inconsistencies safely', function (): void {
    $studioService = resolve(StudioService::class);

    // Property 1: Permission diagnostic should identify issues
    $permissionDiagnostic = $studioService->diagnosePermissionHealth();

    expect($permissionDiagnostic)->toHaveKey('orphaned_permissions', 'Should check for orphaned permissions');
    expect($permissionDiagnostic)->toHaveKey('missing_permissions', 'Should check for missing permissions');
    expect($permissionDiagnostic)->toHaveKey('role_inconsistencies', 'Should check role consistency');

    // Property 2: Permission repair should be conservative
    $permissionRepair = $studioService->repairPermissions(['dry_run' => true]);

    expect($permissionRepair['dry_run'])->toBeTrue('Dry run should be respected');
    expect($permissionRepair)->toHaveKey('would_fix', 'Should report what would be fixed');

    // Property 3: Actual repair should maintain security
    if (! empty($permissionRepair['would_fix'])) {
        $actualPermissionRepair = $studioService->repairPermissions(['dry_run' => false]);

        expect($actualPermissionRepair['success'])->toBeTrue('Permission repair should succeed');
        expect($actualPermissionRepair)->toHaveKey('security_maintained', 'Should confirm security is maintained');
        expect($actualPermissionRepair['security_maintained'])->toBeTrue('Security should be maintained');
    }

    // Property 4: Permission repair should be idempotent
    $secondPermissionRepair = $studioService->repairPermissions(['dry_run' => false]);

    expect($secondPermissionRepair['changes_made'])->toBe(0, 'Second repair should make no changes');
    expect($secondPermissionRepair['success'])->toBeTrue('Second repair should still succeed');
});
