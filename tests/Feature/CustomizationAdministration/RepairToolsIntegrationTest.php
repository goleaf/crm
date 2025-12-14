<?php

declare(strict_types=1);

use App\Models\Studio\FieldDependency;
use App\Models\Studio\LabelCustomization;
use App\Models\Studio\LayoutDefinition;
use App\Models\Team;
use App\Models\User;
use App\Services\Studio\StudioService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

/**
 * Integration tests for repair tools execution
 *
 * Tests that repair and diagnostic tools work correctly
 * and integrate properly with the application.
 */
describe('Repair Tools Integration Tests', function (): void {
    beforeEach(function (): void {
        $this->user = User::factory()->create();
        $this->team = Team::factory()->create();
        $this->user->teams()->attach($this->team);

        $this->studioService = resolve(StudioService::class);

        $this->actingAs($this->user);
    });

    it('runs comprehensive system diagnostics', function (): void {
        // Create test data with some intentional issues
        $validLayout = LayoutDefinition::factory()->create([
            'team_id' => $this->team->id,
            'module_name' => 'companies',
            'view_type' => 'list',
            'components' => ['fields' => ['name', 'email']],
        ]);

        $corruptedLayout = LayoutDefinition::factory()->create([
            'team_id' => $this->team->id,
            'module_name' => 'companies',
            'view_type' => 'detail',
            'components' => null, // Corrupted data
        ]);

        $validDependency = FieldDependency::factory()->create([
            'team_id' => $this->team->id,
            'module_name' => 'companies',
            'source_field_code' => 'type',
            'target_field_code' => 'category',
            'dependency_type' => 'visibility',
        ]);

        $invalidDependency = FieldDependency::factory()->create([
            'team_id' => $this->team->id,
            'module_name' => 'companies',
            'source_field_code' => 'nonexistent',
            'target_field_code' => 'also_nonexistent',
            'dependency_type' => 'invalid_type',
        ]);

        // Run diagnostics
        $diagnosticResult = $this->studioService->runDiagnostics($this->team);

        expect($diagnosticResult['success'])->toBeTrue('Diagnostics should run successfully');
        expect($diagnosticResult['issues_found'])->toBeGreaterThan(0, 'Should find issues in test data');
        expect($diagnosticResult['issues'])->toHaveKey('corrupted_layouts');
        expect($diagnosticResult['issues'])->toHaveKey('invalid_dependencies');

        // Verify specific issues are identified
        expect($diagnosticResult['issues']['corrupted_layouts'])->toContain($corruptedLayout->id);
        expect($diagnosticResult['issues']['invalid_dependencies'])->toContain($invalidDependency->id);

        // Valid items should not be flagged
        expect($diagnosticResult['issues']['corrupted_layouts'])->not->toContain($validLayout->id);
        expect($diagnosticResult['issues']['invalid_dependencies'])->not->toContain($validDependency->id);

        // Test diagnostic report generation
        expect($diagnosticResult)->toHaveKey('report');
        expect($diagnosticResult['report'])->toHaveKey('summary');
        expect($diagnosticResult['report'])->toHaveKey('details');
        expect($diagnosticResult['report']['summary']['total_issues'])->toBeGreaterThan(0);
    });

    it('performs safe dry-run repairs', function (): void {
        // Create corrupted data
        $corruptedLayout = LayoutDefinition::factory()->create([
            'team_id' => $this->team->id,
            'module_name' => 'companies',
            'components' => null,
        ]);

        $invalidLabel = LabelCustomization::factory()->create([
            'team_id' => $this->team->id,
            'module_name' => 'companies',
            'element_key' => '',
            'custom_label' => '',
        ]);

        // Record original state
        $originalLayoutComponents = $corruptedLayout->components;
        $originalLabelKey = $invalidLabel->element_key;

        // Run dry-run repair
        $dryRunResult = $this->studioService->repairCustomizations($this->team, [
            'dry_run' => true,
            'verbose' => true,
        ]);

        expect($dryRunResult['success'])->toBeTrue('Dry run should succeed');
        expect($dryRunResult['dry_run'])->toBeTrue('Should confirm dry run mode');
        expect($dryRunResult['would_fix'])->toBeGreaterThan(0, 'Should identify items to fix');
        expect($dryRunResult['changes'])->toHaveKey('layouts');
        expect($dryRunResult['changes'])->toHaveKey('labels');

        // Verify no actual changes were made
        $corruptedLayout->refresh();
        $invalidLabel->refresh();

        expect($corruptedLayout->components)->toBe($originalLayoutComponents, 'Layout should not be modified in dry run');
        expect($invalidLabel->element_key)->toBe($originalLabelKey, 'Label should not be modified in dry run');

        // Test that dry run provides detailed change preview
        expect($dryRunResult['changes']['layouts'])->toContain($corruptedLayout->id);
        expect($dryRunResult['changes']['labels'])->toContain($invalidLabel->id);
        expect($dryRunResult)->toHaveKey('preview', 'Should provide preview of changes');
    });

    it('executes actual repairs safely', function (): void {
        // Create various types of corrupted data
        $corruptedLayout = LayoutDefinition::factory()->create([
            'team_id' => $this->team->id,
            'module_name' => 'companies',
            'components' => null,
        ]);

        $invalidDependency = FieldDependency::factory()->create([
            'team_id' => $this->team->id,
            'module_name' => 'companies',
            'dependency_type' => 'invalid_type',
            'conditions' => null,
        ]);

        $emptyLabel = LabelCustomization::factory()->create([
            'team_id' => $this->team->id,
            'module_name' => 'companies',
            'element_key' => '',
            'custom_label' => '',
        ]);

        // Create valid data that should not be affected
        $validLayout = LayoutDefinition::factory()->create([
            'team_id' => $this->team->id,
            'module_name' => 'people',
            'components' => ['fields' => ['name', 'email']],
        ]);

        // Run actual repair
        $repairResult = $this->studioService->repairCustomizations($this->team, [
            'dry_run' => false,
            'backup' => true,
        ]);

        expect($repairResult['success'])->toBeTrue('Repair should succeed');
        expect($repairResult['fixed_count'])->toBeGreaterThan(0, 'Should fix issues');
        expect($repairResult['backup_created'])->toBeTrue('Should create backup');
        expect($repairResult['backup_path'])->not->toBeNull('Should provide backup path');

        // Verify corrupted data is fixed or removed
        $layoutExists = LayoutDefinition::find($corruptedLayout->id);
        if ($layoutExists) {
            expect($layoutExists->components)->not->toBeNull('Fixed layout should have valid components');
            expect($layoutExists->components)->toBeArray('Components should be array');
        }

        $dependencyExists = FieldDependency::find($invalidDependency->id);
        if ($dependencyExists) {
            expect($dependencyExists->dependency_type)->toBeIn(['visibility', 'required', 'options'], 'Fixed dependency should have valid type');
            expect($dependencyExists->conditions)->not->toBeNull('Fixed dependency should have conditions');
        }

        $labelExists = LabelCustomization::find($emptyLabel->id);
        if ($labelExists) {
            expect($labelExists->element_key)->not->toBeEmpty('Fixed label should have element key');
            expect($labelExists->custom_label)->not->toBeEmpty('Fixed label should have custom label');
        }

        // Verify valid data is unchanged
        $validLayout->refresh();
        expect($validLayout->components)->toBe(['fields' => ['name', 'email']], 'Valid layout should remain unchanged');
    });

    it('demonstrates repair tool idempotence', function (): void {
        // Create corrupted data
        $corruptedLayout = LayoutDefinition::factory()->create([
            'team_id' => $this->team->id,
            'module_name' => 'companies',
            'components' => null,
        ]);

        // First repair
        $firstRepair = $this->studioService->repairCustomizations($this->team, ['dry_run' => false]);
        expect($firstRepair['success'])->toBeTrue('First repair should succeed');
        expect($firstRepair['fixed_count'])->toBeGreaterThan(0, 'First repair should fix issues');

        // Second repair (should be idempotent)
        $secondRepair = $this->studioService->repairCustomizations($this->team, ['dry_run' => false]);
        expect($secondRepair['success'])->toBeTrue('Second repair should succeed');
        expect($secondRepair['fixed_count'])->toBe(0, 'Second repair should find nothing to fix');

        // Third repair (confirm idempotence)
        $thirdRepair = $this->studioService->repairCustomizations($this->team, ['dry_run' => false]);
        expect($thirdRepair['success'])->toBeTrue('Third repair should succeed');
        expect($thirdRepair['fixed_count'])->toBe(0, 'Third repair should find nothing to fix');

        // Verify system is in healthy state
        $postRepairDiagnostic = $this->studioService->runDiagnostics($this->team);
        expect($postRepairDiagnostic['issues_found'])->toBe(0, 'No issues should remain after repair');
        expect($postRepairDiagnostic['status'])->toBe('healthy');
    });

    it('repairs cache inconsistencies', function (): void {
        // Create customizations
        $layout = LayoutDefinition::factory()->create([
            'team_id' => $this->team->id,
            'module_name' => 'companies',
        ]);

        $dependency = FieldDependency::factory()->create([
            'team_id' => $this->team->id,
            'module_name' => 'companies',
        ]);

        // Populate cache
        $this->studioService->cacheCustomizations($this->team, 'companies');
        $cacheKey = "studio.customizations.{$this->team->id}.companies";

        // Verify cache exists
        expect(Cache::has($cacheKey))->toBeTrue('Cache should be populated');

        // Corrupt cache
        Cache::put($cacheKey, ['corrupted' => 'data'], 3600);

        // Run cache diagnostics
        $cacheDiagnostic = $this->studioService->diagnoseCacheHealth($this->team);
        expect($cacheDiagnostic['inconsistencies_found'])->toBeGreaterThan(0, 'Should detect cache inconsistencies');

        // Repair cache
        $cacheRepair = $this->studioService->repairCache($this->team, [
            'modules' => ['companies'],
        ]);

        expect($cacheRepair['success'])->toBeTrue('Cache repair should succeed');
        expect($cacheRepair['repaired_modules'])->toContain('companies');

        // Verify cache is restored
        $repairedCache = Cache::get($cacheKey);
        expect($repairedCache)->not->toBeNull('Cache should be restored');
        expect($repairedCache)->toHaveKey('layouts');
        expect($repairedCache)->toHaveKey('dependencies');
        expect($repairedCache['layouts'])->toHaveCount(1);
        expect($repairedCache['dependencies'])->toHaveCount(1);

        // Test cache repair idempotence
        $secondCacheRepair = $this->studioService->repairCache($this->team, [
            'modules' => ['companies'],
        ]);
        expect($secondCacheRepair['changes_made'])->toBe(0, 'Second cache repair should make no changes');
    });

    it('repairs database indexes safely', function (): void {
        // Create test data to ensure indexes are used
        LayoutDefinition::factory()->count(100)->create(['team_id' => $this->team->id]);
        FieldDependency::factory()->count(50)->create(['team_id' => $this->team->id]);

        // Run index diagnostics
        $indexDiagnostic = $this->studioService->diagnoseIndexHealth();
        expect($indexDiagnostic)->toHaveKey('missing_indexes');
        expect($indexDiagnostic)->toHaveKey('unused_indexes');
        expect($indexDiagnostic)->toHaveKey('performance_issues');

        // Test dry-run index repair
        $dryRunIndexRepair = $this->studioService->repairIndexes(['dry_run' => true]);
        expect($dryRunIndexRepair['dry_run'])->toBeTrue();
        expect($dryRunIndexRepair)->toHaveKey('would_create');
        expect($dryRunIndexRepair)->toHaveKey('would_drop');

        // Run actual index repair if needed
        if (! empty($dryRunIndexRepair['would_create'])) {
            $indexRepair = $this->studioService->repairIndexes(['dry_run' => false]);
            expect($indexRepair['success'])->toBeTrue('Index repair should succeed');
        }

        // Verify data integrity is maintained
        $layoutCount = LayoutDefinition::where('team_id', $this->team->id)->count();
        $dependencyCount = FieldDependency::where('team_id', $this->team->id)->count();

        expect($layoutCount)->toBe(100, 'Layout count should be preserved');
        expect($dependencyCount)->toBe(50, 'Dependency count should be preserved');

        // Test query performance is maintained
        $startTime = microtime(true);
        LayoutDefinition::where('team_id', $this->team->id)
            ->where('module_name', 'companies')
            ->get();
        $queryTime = microtime(true) - $startTime;

        expect($queryTime)->toBeLessThan(1.0, 'Query should complete in reasonable time');
    });

    it('integrates repair tools with Artisan commands', function (): void {
        // Create corrupted data
        LayoutDefinition::factory()->create([
            'team_id' => $this->team->id,
            'components' => null,
        ]);

        // Test diagnostic command
        $diagnosticExitCode = Artisan::call('studio:diagnose', [
            '--team' => $this->team->id,
            '--format' => 'json',
        ]);

        expect($diagnosticExitCode)->toBe(0, 'Diagnostic command should succeed');

        $diagnosticOutput = Artisan::output();
        expect($diagnosticOutput)->toContain('issues_found', 'Diagnostic output should contain issues');

        // Test repair command with dry-run
        $dryRunExitCode = Artisan::call('studio:repair', [
            '--team' => $this->team->id,
            '--dry-run' => true,
        ]);

        expect($dryRunExitCode)->toBe(0, 'Dry-run repair command should succeed');

        // Test actual repair command
        $repairExitCode = Artisan::call('studio:repair', [
            '--team' => $this->team->id,
            '--backup' => true,
        ]);

        expect($repairExitCode)->toBe(0, 'Repair command should succeed');

        // Test cache repair command
        $cacheRepairExitCode = Artisan::call('studio:repair-cache', [
            '--team' => $this->team->id,
        ]);

        expect($cacheRepairExitCode)->toBe(0, 'Cache repair command should succeed');

        // Verify system is healthy after command-line repairs
        $finalDiagnostic = $this->studioService->runDiagnostics($this->team);
        expect($finalDiagnostic['issues_found'])->toBe(0, 'No issues should remain after repair');
    });

    it('handles repair tool error conditions gracefully', function (): void {
        // Test repair with invalid team
        $invalidTeamRepair = $this->studioService->repairCustomizations(
            Team::factory()->make(['id' => 99999]), // Non-existent team
            ['dry_run' => false],
        );

        expect($invalidTeamRepair['success'])->toBeFalse('Repair should fail for invalid team');
        expect($invalidTeamRepair['error'])->toContain('Team not found');

        // Test repair with database connection issues (simulate)
        DB::shouldReceive('transaction')->andThrow(new \Exception('Database connection failed'));

        $dbErrorRepair = $this->studioService->repairCustomizations($this->team, ['dry_run' => false]);
        expect($dbErrorRepair['success'])->toBeFalse('Repair should handle database errors gracefully');
        expect($dbErrorRepair['error'])->toContain('Database connection failed');

        // Test repair with insufficient permissions (simulate)
        $this->user->revokePermissionTo('manage_studio');

        $permissionErrorRepair = $this->studioService->repairCustomizations($this->team, ['dry_run' => false]);
        expect($permissionErrorRepair['success'])->toBeFalse('Repair should check permissions');
        expect($permissionErrorRepair['error'])->toContain('Insufficient permissions');

        // Test recovery from partial repair failure
        LayoutDefinition::factory()->create([
            'team_id' => $this->team->id,
            'components' => null,
        ]);

        // Grant permissions back
        $this->user->givePermissionTo('manage_studio');

        // Simulate partial failure during repair
        $partialRepair = $this->studioService->repairCustomizations($this->team, [
            'dry_run' => false,
            'continue_on_error' => true,
        ]);

        expect($partialRepair)->toHaveKey('partial_success', 'Should handle partial failures');
        expect($partialRepair)->toHaveKey('failed_items', 'Should report failed items');
        expect($partialRepair)->toHaveKey('successful_items', 'Should report successful items');
    });

    it('maintains audit trail for repair operations', function (): void {
        // Create corrupted data
        $corruptedLayout = LayoutDefinition::factory()->create([
            'team_id' => $this->team->id,
            'components' => null,
        ]);

        // Run repair with audit logging
        $repairResult = $this->studioService->repairCustomizations($this->team, [
            'dry_run' => false,
            'audit_log' => true,
        ]);

        expect($repairResult['success'])->toBeTrue('Repair should succeed');
        expect($repairResult['audit_log_id'])->not->toBeNull('Should create audit log entry');

        // Verify audit log contains repair details
        $auditLog = \App\Models\Studio\StudioAuditLog::find($repairResult['audit_log_id']);
        expect($auditLog)->not->toBeNull('Audit log should exist');
        expect($auditLog->action)->toBe('repair_customizations');
        expect($auditLog->user_id)->toBe($this->user->id);
        expect($auditLog->team_id)->toBe($this->team->id);
        expect($auditLog->changes)->toHaveKey('fixed_items');
        expect($auditLog->changes)->toHaveKey('backup_path');

        // Test that audit log is preserved even if repair fails
        $failedRepair = $this->studioService->repairCustomizations(
            Team::factory()->make(['id' => 99999]),
            ['audit_log' => true],
        );

        if (isset($failedRepair['audit_log_id'])) {
            $failedAuditLog = \App\Models\Studio\StudioAuditLog::find($failedRepair['audit_log_id']);
            expect($failedAuditLog->success)->toBeFalse('Failed repair should be logged as unsuccessful');
            expect($failedAuditLog->error_message)->not->toBeNull('Error should be recorded in audit log');
        }
    });
});
