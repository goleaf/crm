<?php

declare(strict_types=1);

use App\Models\Studio\FieldDependency;
use App\Models\Studio\LabelCustomization;
use App\Models\Studio\LayoutDefinition;
use App\Models\Team;
use App\Services\Studio\StudioService;
use Tests\Support\PropertyTestCase;

uses(PropertyTestCase::class);

/**
 * Property: Layout customizations are isolated per module and team
 * 
 * **Feature: customization-administration, Property 1: Customization isolation**
 * 
 * Validates: Requirements 1.3
 */
test('property: layout customizations are scoped to selected modules and teams', function (): void {
    runPropertyTest(function (): void {
        // Create two different teams
        $team1 = Team::factory()->create();
        $team2 = Team::factory()->create();

        // Create two different modules
        $modules = ['companies', 'people'];
        $module1 = $modules[array_rand($modules)];
        $module2 = $modules[array_rand($modules)];

        // Create layout definitions for different teams and modules
        $layout1 = LayoutDefinition::factory()->create([
            'team_id' => $team1->id,
            'module_name' => $module1,
            'view_type' => 'list',
            'name' => 'Custom List Layout',
            'components' => ['field1' => 'config1'],
        ]);

        $layout2 = LayoutDefinition::factory()->create([
            'team_id' => $team2->id,
            'module_name' => $module1,
            'view_type' => 'list',
            'name' => 'Different Layout',
            'components' => ['field2' => 'config2'],
        ]);

        $layout3 = LayoutDefinition::factory()->create([
            'team_id' => $team1->id,
            'module_name' => $module2,
            'view_type' => 'list',
            'name' => 'Another Module Layout',
            'components' => ['field3' => 'config3'],
        ]);

        // Verify team isolation: team1 should only see its own layouts
        $team1Layouts = LayoutDefinition::where('team_id', $team1->id)->get();
        expect($team1Layouts)->toHaveCount(2);
        expect($team1Layouts->pluck('id'))->toContain($layout1->id, $layout3->id);
        expect($team1Layouts->pluck('id'))->not->toContain($layout2->id);

        // Verify team isolation: team2 should only see its own layouts
        $team2Layouts = LayoutDefinition::where('team_id', $team2->id)->get();
        expect($team2Layouts)->toHaveCount(1);
        expect($team2Layouts->first()->id)->toBe($layout2->id);

        // Verify module isolation: layouts for module1 should not affect module2
        $module1Layouts = LayoutDefinition::where('team_id', $team1->id)
            ->where('module_name', $module1)
            ->get();
        expect($module1Layouts)->toHaveCount(1);
        expect($module1Layouts->first()->id)->toBe($layout1->id);

        $module2Layouts = LayoutDefinition::where('team_id', $team1->id)
            ->where('module_name', $module2)
            ->get();
        expect($module2Layouts)->toHaveCount(1);
        expect($module2Layouts->first()->id)->toBe($layout3->id);
    });
});

/**
 * Property: Field dependencies are isolated per module and team
 * 
 * **Feature: customization-administration, Property 1: Customization isolation**
 * 
 * Validates: Requirements 1.3
 */
test('property: field dependencies are scoped to selected modules and teams', function (): void {
    runPropertyTest(function (): void {
        // Create two different teams
        $team1 = Team::factory()->create();
        $team2 = Team::factory()->create();

        // Create field dependencies for different teams and modules
        $dependency1 = FieldDependency::factory()->create([
            'team_id' => $team1->id,
            'module_name' => 'companies',
            'source_field_code' => 'status',
            'target_field_code' => 'reason',
            'dependency_type' => 'visibility',
        ]);

        $dependency2 = FieldDependency::factory()->create([
            'team_id' => $team2->id,
            'module_name' => 'companies',
            'source_field_code' => 'type',
            'target_field_code' => 'category',
            'dependency_type' => 'required',
        ]);

        $dependency3 = FieldDependency::factory()->create([
            'team_id' => $team1->id,
            'module_name' => 'people',
            'source_field_code' => 'role',
            'target_field_code' => 'department',
            'dependency_type' => 'options',
        ]);

        // Verify team isolation
        $team1Dependencies = FieldDependency::where('team_id', $team1->id)->get();
        expect($team1Dependencies)->toHaveCount(2);
        expect($team1Dependencies->pluck('id'))->toContain($dependency1->id, $dependency3->id);
        expect($team1Dependencies->pluck('id'))->not->toContain($dependency2->id);

        // Verify module isolation within the same team
        $companiesDependencies = FieldDependency::where('team_id', $team1->id)
            ->where('module_name', 'companies')
            ->get();
        expect($companiesDependencies)->toHaveCount(1);
        expect($companiesDependencies->first()->id)->toBe($dependency1->id);

        $peopleDependencies = FieldDependency::where('team_id', $team1->id)
            ->where('module_name', 'people')
            ->get();
        expect($peopleDependencies)->toHaveCount(1);
        expect($peopleDependencies->first()->id)->toBe($dependency3->id);
    });
});

/**
 * Property: Label customizations are isolated per module and team
 * 
 * **Feature: customization-administration, Property 1: Customization isolation**
 * 
 * Validates: Requirements 1.3
 */
test('property: label customizations are scoped to selected modules and teams', function (): void {
    runPropertyTest(function (): void {
        // Create two different teams
        $team1 = Team::factory()->create();
        $team2 = Team::factory()->create();

        // Create label customizations for different teams and modules
        $label1 = LabelCustomization::factory()->create([
            'team_id' => $team1->id,
            'module_name' => 'companies',
            'element_type' => 'field',
            'element_key' => 'name',
            'original_label' => 'Name',
            'custom_label' => 'Company Name',
        ]);

        $label2 = LabelCustomization::factory()->create([
            'team_id' => $team2->id,
            'module_name' => 'companies',
            'element_type' => 'field',
            'element_key' => 'name',
            'original_label' => 'Name',
            'custom_label' => 'Organization Name',
        ]);

        $label3 = LabelCustomization::factory()->create([
            'team_id' => $team1->id,
            'module_name' => 'people',
            'element_type' => 'field',
            'element_key' => 'name',
            'original_label' => 'Name',
            'custom_label' => 'Full Name',
        ]);

        // Verify team isolation
        $team1Labels = LabelCustomization::where('team_id', $team1->id)->get();
        expect($team1Labels)->toHaveCount(2);
        expect($team1Labels->pluck('id'))->toContain($label1->id, $label3->id);
        expect($team1Labels->pluck('id'))->not->toContain($label2->id);

        // Verify module isolation within the same team
        $companiesLabels = LabelCustomization::where('team_id', $team1->id)
            ->where('module_name', 'companies')
            ->get();
        expect($companiesLabels)->toHaveCount(1);
        expect($companiesLabels->first()->custom_label)->toBe('Company Name');

        $peopleLabels = LabelCustomization::where('team_id', $team1->id)
            ->where('module_name', 'people')
            ->get();
        expect($peopleLabels)->toHaveCount(1);
        expect($peopleLabels->first()->custom_label)->toBe('Full Name');

        // Verify that different teams can have different customizations for the same element
        $team2CompaniesLabels = LabelCustomization::where('team_id', $team2->id)
            ->where('module_name', 'companies')
            ->where('element_key', 'name')
            ->get();
        expect($team2CompaniesLabels)->toHaveCount(1);
        expect($team2CompaniesLabels->first()->custom_label)->toBe('Organization Name');
    });
});

/**
 * Property: Studio service validates customization isolation
 * 
 * **Feature: customization-administration, Property 1: Customization isolation**
 * 
 * Validates: Requirements 1.3
 */
test('property: studio service enforces customization isolation', function (): void {
    runPropertyTest(function (): void {
        $team = Team::factory()->create();
        $moduleName = 'companies';
        $studioService = app(StudioService::class);

        // Test that validation passes for consistent module names
        $validData = [
            'module_name' => $moduleName,
            'source_field_code' => 'status',
            'target_field_code' => 'reason',
        ];

        expect($studioService->validateCustomizationIsolation($team, $moduleName, $validData))
            ->toBeTrue();

        // Test that validation fails for inconsistent module names
        $invalidData = [
            'module_name' => 'people', // Different from $moduleName
            'source_field_code' => 'status',
            'target_field_code' => 'reason',
        ];

        expect($studioService->validateCustomizationIsolation($team, $moduleName, $invalidData))
            ->toBeFalse();
    });
});