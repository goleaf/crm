<?php

declare(strict_types=1);

use App\Models\Studio\FieldDependency;
use App\Models\Studio\LabelCustomization;
use App\Models\Studio\LayoutDefinition;
use App\Models\Team;
use App\Services\Studio\StudioService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/**
 * **Feature: customization-administration, Property 2: Module packaging integrity**
 *
 * Property: Exported packages include all definitions (fields/relationships/layouts) with versioning and install cleanly.
 *
 * This property tests that:
 * 1. Package exports include all related customizations
 * 2. Package versioning is consistent
 * 3. Package imports restore all customizations correctly
 * 4. Package integrity is maintained across export/import cycles
 */
it('ensures package completeness across export/import cycles', function (): void {
    $team = Team::factory()->create();
    $moduleName = 'companies';
    $studioService = resolve(StudioService::class);

    // Create comprehensive customizations for a module
    $layoutDefinition = LayoutDefinition::factory()->create([
        'team_id' => $team->id,
        'module_name' => $moduleName,
        'view_type' => 'list',
        'name' => 'Custom List Layout',
        'components' => [
            'fields' => ['name', 'email', 'phone'],
            'ordering' => ['name' => 'asc'],
            'filters' => ['status' => 'active'],
        ],
    ]);

    $fieldDependency = FieldDependency::factory()->create([
        'team_id' => $team->id,
        'module_name' => $moduleName,
        'source_field_code' => 'type',
        'target_field_code' => 'category',
        'dependency_type' => 'visibility',
        'conditions' => ['type' => 'corporate'],
    ]);

    $labelCustomization = LabelCustomization::factory()->create([
        'team_id' => $team->id,
        'module_name' => $moduleName,
        'element_type' => 'field',
        'element_key' => 'name',
        'original_label' => 'Name',
        'custom_label' => 'Company Name',
    ]);

    // Property 1: Export should include all customizations
    $packageData = $studioService->exportModulePackage($team, $moduleName);

    expect($packageData)->toHaveKey('layouts', 'Package should include layout definitions');
    expect($packageData)->toHaveKey('dependencies', 'Package should include field dependencies');
    expect($packageData)->toHaveKey('labels', 'Package should include label customizations');
    expect($packageData)->toHaveKey('version', 'Package should include version information');
    expect($packageData)->toHaveKey('module_name', 'Package should include module name');

    // Property 2: All customizations should be included in export
    expect($packageData['layouts'])->toHaveCount(1, 'All layouts should be exported');
    expect($packageData['dependencies'])->toHaveCount(1, 'All dependencies should be exported');
    expect($packageData['labels'])->toHaveCount(1, 'All labels should be exported');

    // Property 3: Exported data should match original customizations
    $exportedLayout = $packageData['layouts'][0];
    expect($exportedLayout['name'])->toBe($layoutDefinition->name);
    expect($exportedLayout['view_type'])->toBe($layoutDefinition->view_type);
    expect($exportedLayout['components'])->toBe($layoutDefinition->components);

    $exportedDependency = $packageData['dependencies'][0];
    expect($exportedDependency['source_field_code'])->toBe($fieldDependency->source_field_code);
    expect($exportedDependency['target_field_code'])->toBe($fieldDependency->target_field_code);
    expect($exportedDependency['conditions'])->toBe($fieldDependency->conditions);

    $exportedLabel = $packageData['labels'][0];
    expect($exportedLabel['element_key'])->toBe($labelCustomization->element_key);
    expect($exportedLabel['custom_label'])->toBe($labelCustomization->custom_label);

    // Property 4: Package should have valid version information
    expect($packageData['version'])->toMatch('/^\d+\.\d+\.\d+$/', 'Version should follow semantic versioning');
    expect($packageData['created_at'])->not->toBeNull('Package should have creation timestamp');
    expect($packageData['team_id'])->toBe($team->id, 'Package should reference source team');

    // Property 5: Import should restore all customizations correctly
    $newTeam = Team::factory()->create();

    // Clear existing data to ensure clean import
    LayoutDefinition::where('team_id', $newTeam->id)->delete();
    FieldDependency::where('team_id', $newTeam->id)->delete();
    LabelCustomization::where('team_id', $newTeam->id)->delete();

    $importResult = $studioService->importModulePackage($newTeam, $packageData);

    expect($importResult['success'])->toBeTrue('Import should succeed');
    expect($importResult)->toHaveKey('imported_count', 'Import should report imported items count');

    // Property 6: Imported customizations should match original data
    $importedLayouts = LayoutDefinition::where('team_id', $newTeam->id)
        ->where('module_name', $moduleName)
        ->get();
    expect($importedLayouts)->toHaveCount(1, 'All layouts should be imported');

    $importedLayout = $importedLayouts->first();
    expect($importedLayout->name)->toBe($layoutDefinition->name);
    expect($importedLayout->view_type)->toBe($layoutDefinition->view_type);
    expect($importedLayout->components)->toBe($layoutDefinition->components);

    $importedDependencies = FieldDependency::where('team_id', $newTeam->id)
        ->where('module_name', $moduleName)
        ->get();
    expect($importedDependencies)->toHaveCount(1, 'All dependencies should be imported');

    $importedDependency = $importedDependencies->first();
    expect($importedDependency->source_field_code)->toBe($fieldDependency->source_field_code);
    expect($importedDependency->target_field_code)->toBe($fieldDependency->target_field_code);
    expect($importedDependency->conditions)->toBe($fieldDependency->conditions);

    $importedLabels = LabelCustomization::where('team_id', $newTeam->id)
        ->where('module_name', $moduleName)
        ->get();
    expect($importedLabels)->toHaveCount(1, 'All labels should be imported');

    $importedLabel = $importedLabels->first();
    expect($importedLabel->element_key)->toBe($labelCustomization->element_key);
    expect($importedLabel->custom_label)->toBe($labelCustomization->custom_label);

    // Property 7: Round-trip integrity (export -> import -> export should be identical)
    $secondExport = $studioService->exportModulePackage($newTeam, $moduleName);

    // Compare key data structures (excluding timestamps and IDs)
    expect($secondExport['layouts'][0]['name'])->toBe($packageData['layouts'][0]['name']);
    expect($secondExport['layouts'][0]['components'])->toBe($packageData['layouts'][0]['components']);
    expect($secondExport['dependencies'][0]['source_field_code'])->toBe($packageData['dependencies'][0]['source_field_code']);
    expect($secondExport['labels'][0]['custom_label'])->toBe($packageData['labels'][0]['custom_label']);
});

/**
 * Property: Package versioning maintains consistency
 */
it('maintains package versioning consistency', function (): void {
    $team = Team::factory()->create();
    $moduleName = 'people';
    $studioService = resolve(StudioService::class);

    // Create initial customization
    LayoutDefinition::factory()->create([
        'team_id' => $team->id,
        'module_name' => $moduleName,
        'view_type' => 'detail',
        'name' => 'Initial Layout',
    ]);

    // Export initial version
    $package1 = $studioService->exportModulePackage($team, $moduleName);
    $version1 = $package1['version'];

    // Add more customizations
    FieldDependency::factory()->create([
        'team_id' => $team->id,
        'module_name' => $moduleName,
        'source_field_code' => 'status',
        'target_field_code' => 'notes',
    ]);

    // Export updated version
    $package2 = $studioService->exportModulePackage($team, $moduleName);
    $version2 = $package2['version'];

    // Property: Version should increment when content changes
    expect($version2)->not->toBe($version1, 'Version should change when customizations are added');

    // Property: Version format should be consistent
    expect($version1)->toMatch('/^\d+\.\d+\.\d+$/', 'Version 1 should follow semantic versioning');
    expect($version2)->toMatch('/^\d+\.\d+\.\d+$/', 'Version 2 should follow semantic versioning');

    // Property: Package metadata should be consistent
    expect($package2['module_name'])->toBe($package1['module_name'], 'Module name should remain consistent');
    expect($package2['team_id'])->toBe($package1['team_id'], 'Team ID should remain consistent');

    // Property: Content count should reflect changes
    expect(count($package2['layouts']))->toBe(count($package1['layouts']), 'Layout count should be same');
    expect(count($package2['dependencies']))->toBe(count($package1['dependencies']) + 1, 'Dependency count should increase');
});

/**
 * Property: Package import handles conflicts gracefully
 */
it('handles package import conflicts gracefully', function (): void {
    $team = Team::factory()->create();
    $moduleName = 'opportunities';
    $studioService = resolve(StudioService::class);

    // Create existing customization
    $existingLayout = LayoutDefinition::factory()->create([
        'team_id' => $team->id,
        'module_name' => $moduleName,
        'view_type' => 'list',
        'name' => 'Existing Layout',
        'components' => ['field1' => 'config1'],
    ]);

    // Create package with conflicting layout (same view_type)
    $packageData = [
        'module_name' => $moduleName,
        'version' => '1.0.0',
        'created_at' => now()->toISOString(),
        'team_id' => $team->id,
        'layouts' => [
            [
                'view_type' => 'list',
                'name' => 'Imported Layout',
                'components' => ['field2' => 'config2'],
                'ordering' => 1,
                'visibility_rules' => [],
            ],
        ],
        'dependencies' => [],
        'labels' => [],
    ];

    // Property: Import should handle conflicts according to strategy
    $importResult = $studioService->importModulePackage($team, $packageData, [
        'conflict_strategy' => 'merge',
    ]);

    expect($importResult['success'])->toBeTrue('Import should succeed with merge strategy');
    expect($importResult)->toHaveKey('conflicts_resolved', 'Import should report conflict resolution');

    // Property: Both layouts should exist after merge
    $layouts = LayoutDefinition::where('team_id', $team->id)
        ->where('module_name', $moduleName)
        ->where('view_type', 'list')
        ->get();

    expect($layouts)->toHaveCount(2, 'Both layouts should exist after merge');

    // Property: Import with replace strategy should replace existing
    $replaceResult = $studioService->importModulePackage($team, $packageData, [
        'conflict_strategy' => 'replace',
    ]);

    expect($replaceResult['success'])->toBeTrue('Import should succeed with replace strategy');

    $layoutsAfterReplace = LayoutDefinition::where('team_id', $team->id)
        ->where('module_name', $moduleName)
        ->where('view_type', 'list')
        ->get();

    expect($layoutsAfterReplace)->toHaveCount(1, 'Only imported layout should exist after replace');
    expect($layoutsAfterReplace->first()->name)->toBe('Imported Layout', 'Imported layout should replace existing');
});
