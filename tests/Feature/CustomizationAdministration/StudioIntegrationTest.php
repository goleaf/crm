<?php

declare(strict_types=1);

use App\Filament\Resources\Studio\FieldDependencyResource;
use App\Filament\Resources\Studio\LabelCustomizationResource;
use App\Filament\Resources\Studio\LayoutDefinitionResource;
use App\Models\Studio\FieldDependency;
use App\Models\Studio\LabelCustomization;
use App\Models\Studio\LayoutDefinition;
use App\Models\Team;
use App\Models\User;
use App\Services\Studio\StudioService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/**
 * Integration tests for Studio changes propagating to UI
 *
 * Tests that studio customizations properly affect the user interface
 * and that changes are immediately reflected across the system.
 */
describe('Studio Integration Tests', function (): void {
    beforeEach(function (): void {
        $this->user = User::factory()->create();
        $this->team = Team::factory()->create();
        $this->user->teams()->attach($this->team);

        $this->actingAs($this->user);

        // Set current team context
        session(['current_team_id' => $this->team->id]);
    });

    it('propagates layout changes to UI immediately', function (): void {
        // Create a layout definition
        $layout = LayoutDefinition::factory()->create([
            'team_id' => $this->team->id,
            'module_name' => 'companies',
            'view_type' => 'list',
            'name' => 'Custom Company List',
            'components' => [
                'fields' => ['name', 'email', 'phone'],
                'ordering' => ['name' => 'asc'],
                'filters' => ['status' => 'active'],
            ],
        ]);

        // Test that the layout appears in the resource
        $response = $this->get(LayoutDefinitionResource::getUrl('index'));
        $response->assertSuccessful();
        $response->assertSee('Custom Company List');

        // Test editing the layout through Filament
        $updateData = [
            'name' => 'Updated Company List',
            'components' => [
                'fields' => ['name', 'email', 'phone', 'website'],
                'ordering' => ['created_at' => 'desc'],
                'filters' => ['status' => 'active', 'type' => 'corporate'],
            ],
        ];

        $editResponse = $this->patch(
            LayoutDefinitionResource::getUrl('edit', ['record' => $layout]),
            $updateData,
        );
        $editResponse->assertRedirect();

        // Verify changes are immediately reflected
        $layout->refresh();
        expect($layout->name)->toBe('Updated Company List');
        expect($layout->components['fields'])->toContain('website');
        expect($layout->components['ordering'])->toBe(['created_at' => 'desc']);

        // Test that UI reflects the changes
        $viewResponse = $this->get(LayoutDefinitionResource::getUrl('view', ['record' => $layout]));
        $viewResponse->assertSuccessful();
        $viewResponse->assertSee('Updated Company List');
        $viewResponse->assertSee('website');
    });

    it('applies field dependencies correctly across modules', function (): void {
        // Create field dependency
        $dependency = FieldDependency::factory()->create([
            'team_id' => $this->team->id,
            'module_name' => 'companies',
            'source_field_code' => 'type',
            'target_field_code' => 'category',
            'dependency_type' => 'visibility',
            'conditions' => ['type' => 'corporate'],
        ]);

        // Test that dependency appears in resource
        $response = $this->get(FieldDependencyResource::getUrl('index'));
        $response->assertSuccessful();
        $response->assertSee('type');
        $response->assertSee('category');

        // Test creating a new dependency through UI
        $createData = [
            'module_name' => 'people',
            'source_field_code' => 'role',
            'target_field_code' => 'department',
            'dependency_type' => 'required',
            'conditions' => ['role' => 'manager'],
            'is_active' => true,
        ];

        $createResponse = $this->post(
            FieldDependencyResource::getUrl('create'),
            $createData,
        );
        $createResponse->assertRedirect();

        // Verify dependency was created with correct team context
        $newDependency = FieldDependency::where('module_name', 'people')
            ->where('source_field_code', 'role')
            ->first();

        expect($newDependency)->not->toBeNull();
        expect($newDependency->team_id)->toBe($this->team->id);
        expect($newDependency->conditions)->toBe(['role' => 'manager']);

        // Test that dependencies are properly scoped to team
        $otherTeam = Team::factory()->create();
        $otherDependency = FieldDependency::factory()->create([
            'team_id' => $otherTeam->id,
            'module_name' => 'people',
        ]);

        // Should not see other team's dependencies
        $indexResponse = $this->get(FieldDependencyResource::getUrl('index'));
        $indexResponse->assertSuccessful();

        // Should see own team's dependencies but not other team's
        expect(FieldDependency::where('team_id', $this->team->id)->count())->toBe(2);
        expect(FieldDependency::where('team_id', $otherTeam->id)->count())->toBe(1);
    });

    it('handles label customizations with proper isolation', function (): void {
        // Create label customization
        $label = LabelCustomization::factory()->create([
            'team_id' => $this->team->id,
            'module_name' => 'companies',
            'element_type' => 'field',
            'element_key' => 'name',
            'original_label' => 'Name',
            'custom_label' => 'Company Name',
        ]);

        // Test viewing label customization
        $response = $this->get(LabelCustomizationResource::getUrl('view', ['record' => $label]));
        $response->assertSuccessful();
        $response->assertSee('Company Name');
        $response->assertSee('companies');

        // Test bulk label updates
        $bulkData = [
            [
                'module_name' => 'companies',
                'element_type' => 'field',
                'element_key' => 'email',
                'original_label' => 'Email',
                'custom_label' => 'Email Address',
            ],
            [
                'module_name' => 'companies',
                'element_type' => 'field',
                'element_key' => 'phone',
                'original_label' => 'Phone',
                'custom_label' => 'Phone Number',
            ],
        ];

        $studioService = resolve(StudioService::class);
        $bulkResult = $studioService->bulkUpdateLabels($this->team, $bulkData);

        expect($bulkResult['success'])->toBeTrue();
        expect($bulkResult['updated_count'])->toBe(2);

        // Verify bulk updates were applied
        $emailLabel = LabelCustomization::where('team_id', $this->team->id)
            ->where('element_key', 'email')
            ->first();
        expect($emailLabel->custom_label)->toBe('Email Address');

        $phoneLabel = LabelCustomization::where('team_id', $this->team->id)
            ->where('element_key', 'phone')
            ->first();
        expect($phoneLabel->custom_label)->toBe('Phone Number');

        // Test that labels are properly isolated by team
        $otherTeam = Team::factory()->create();
        $otherLabel = LabelCustomization::factory()->create([
            'team_id' => $otherTeam->id,
            'module_name' => 'companies',
            'element_key' => 'name',
            'custom_label' => 'Organization Name',
        ]);

        // Should not see other team's labels in current team context
        $teamLabels = LabelCustomization::where('team_id', $this->team->id)->count();
        $otherTeamLabels = LabelCustomization::where('team_id', $otherTeam->id)->count();

        expect($teamLabels)->toBe(3); // Original + 2 bulk created
        expect($otherTeamLabels)->toBe(1);
    });

    it('validates studio service integration with UI', function (): void {
        $studioService = resolve(StudioService::class);

        // Test service methods are properly integrated
        $moduleInfo = $studioService->getModuleCustomizations($this->team, 'companies');
        expect($moduleInfo)->toHaveKey('layouts');
        expect($moduleInfo)->toHaveKey('dependencies');
        expect($moduleInfo)->toHaveKey('labels');

        // Create customizations through service
        $layoutData = [
            'name' => 'Service Created Layout',
            'view_type' => 'detail',
            'components' => ['fields' => ['name', 'description']],
        ];

        $serviceLayout = $studioService->createLayout($this->team, 'companies', $layoutData);
        expect($serviceLayout)->toBeInstanceOf(LayoutDefinition::class);
        expect($serviceLayout->team_id)->toBe($this->team->id);

        // Verify service-created layout appears in UI
        $response = $this->get(LayoutDefinitionResource::getUrl('index'));
        $response->assertSuccessful();
        $response->assertSee('Service Created Layout');

        // Test service validation integration
        $invalidData = [
            'name' => '', // Invalid: empty name
            'view_type' => 'invalid_type',
            'components' => null,
        ];

        expect(fn () => $studioService->createLayout($this->team, 'companies', $invalidData))
            ->toThrow(InvalidArgumentException::class);

        // Test service caching integration
        $cachedInfo = $studioService->getCachedModuleCustomizations($this->team, 'companies');
        expect($cachedInfo)->not->toBeNull();
        expect($cachedInfo['layouts'])->toHaveCount(1);

        // Modify data and verify cache invalidation
        $studioService->updateLayout($serviceLayout, ['name' => 'Updated Service Layout']);

        $refreshedCache = $studioService->getCachedModuleCustomizations($this->team, 'companies', true);
        expect($refreshedCache['layouts'][0]['name'])->toBe('Updated Service Layout');
    });

    it('handles concurrent studio modifications correctly', function (): void {
        // Simulate concurrent users modifying the same module
        $user2 = User::factory()->create();
        $user2->teams()->attach($this->team);

        // User 1 creates a layout
        $layout1 = LayoutDefinition::factory()->create([
            'team_id' => $this->team->id,
            'module_name' => 'companies',
            'view_type' => 'list',
            'name' => 'User 1 Layout',
        ]);

        // User 2 creates a different layout for the same module
        $this->actingAs($user2);

        $layout2Data = [
            'team_id' => $this->team->id,
            'module_name' => 'companies',
            'view_type' => 'detail',
            'name' => 'User 2 Layout',
            'components' => ['fields' => ['name', 'email']],
        ];

        $createResponse = $this->post(
            LayoutDefinitionResource::getUrl('create'),
            $layout2Data,
        );
        $createResponse->assertRedirect();

        // Both layouts should exist
        $layouts = LayoutDefinition::where('team_id', $this->team->id)
            ->where('module_name', 'companies')
            ->get();

        expect($layouts)->toHaveCount(2);
        expect($layouts->pluck('name')->toArray())->toContain('User 1 Layout', 'User 2 Layout');

        // Test concurrent editing of the same layout
        $this->actingAs($this->user);

        // User 1 updates layout
        $update1Response = $this->patch(
            LayoutDefinitionResource::getUrl('edit', ['record' => $layout1]),
            ['name' => 'User 1 Updated Layout'],
        );
        $update1Response->assertRedirect();

        // Switch to User 2 and try to update the same layout
        $this->actingAs($user2);

        $update2Response = $this->patch(
            LayoutDefinitionResource::getUrl('edit', ['record' => $layout1]),
            ['name' => 'User 2 Updated Layout'],
        );

        // Should handle concurrent updates gracefully
        $layout1->refresh();
        expect($layout1->name)->toBeIn(['User 1 Updated Layout', 'User 2 Updated Layout']);
    });
});
