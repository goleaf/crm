<?php

declare(strict_types=1);

use App\Filament\Resources\RoleResource;
use App\Models\Role;
use App\Models\SecurityGroup;
use App\Models\Team;
use App\Models\User;
use App\Services\Role\RoleManagementService;
use App\Services\SecurityGroup\SecurityGroupService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;

uses(RefreshDatabase::class);

/**
 * Integration tests for role and security group enforcement across records
 *
 * Tests that roles and security groups properly control access to records
 * and that permissions are enforced consistently across the application.
 */
describe('Role and Security Group Enforcement Integration Tests', function (): void {
    beforeEach(function (): void {
        $this->team = Team::factory()->create();
        $this->roleService = resolve(RoleManagementService::class);
        $this->securityGroupService = resolve(SecurityGroupService::class);

        // Create test permissions
        $this->permissions = [
            'view' => Permission::create(['name' => 'view:Company', 'guard_name' => 'web']),
            'create' => Permission::create(['name' => 'create:Company', 'guard_name' => 'web']),
            'update' => Permission::create(['name' => 'update:Company', 'guard_name' => 'web']),
            'delete' => Permission::create(['name' => 'delete:Company', 'guard_name' => 'web']),
        ];
    });

    it('enforces role permissions across different record types', function (): void {
        // Create roles with different permission levels
        $viewerRole = $this->roleService->createRole([
            'name' => 'viewer',
            'display_name' => 'Viewer',
            'guard_name' => 'web',
        ], ['view:Company']);

        $editorRole = $this->roleService->createRole([
            'name' => 'editor',
            'display_name' => 'Editor',
            'guard_name' => 'web',
        ], ['view:Company', 'create:Company', 'update:Company']);

        $adminRole = $this->roleService->createRole([
            'name' => 'admin',
            'display_name' => 'Admin',
            'guard_name' => 'web',
        ], ['view:Company', 'create:Company', 'update:Company', 'delete:Company']);

        // Create users with different roles
        $viewer = User::factory()->create();
        $editor = User::factory()->create();
        $admin = User::factory()->create();

        $this->roleService->assignRoleToUser($viewer, $viewerRole, $this->team->id);
        $this->roleService->assignRoleToUser($editor, $editorRole, $this->team->id);
        $this->roleService->assignRoleToUser($admin, $adminRole, $this->team->id);

        // Test viewer permissions
        $this->actingAs($viewer);
        expect($viewer->can('view:Company'))->toBeTrue('Viewer should have view permission');
        expect($viewer->can('create:Company'))->toBeFalse('Viewer should not have create permission');
        expect($viewer->can('update:Company'))->toBeFalse('Viewer should not have update permission');
        expect($viewer->can('delete:Company'))->toBeFalse('Viewer should not have delete permission');

        // Test editor permissions
        $this->actingAs($editor);
        expect($editor->can('view:Company'))->toBeTrue('Editor should have view permission');
        expect($editor->can('create:Company'))->toBeTrue('Editor should have create permission');
        expect($editor->can('update:Company'))->toBeTrue('Editor should have update permission');
        expect($editor->can('delete:Company'))->toBeFalse('Editor should not have delete permission');

        // Test admin permissions
        $this->actingAs($admin);
        expect($admin->can('view:Company'))->toBeTrue('Admin should have view permission');
        expect($admin->can('create:Company'))->toBeTrue('Admin should have create permission');
        expect($admin->can('update:Company'))->toBeTrue('Admin should have update permission');
        expect($admin->can('delete:Company'))->toBeTrue('Admin should have delete permission');

        // Test role inheritance
        $managerRole = $this->roleService->createRole([
            'name' => 'manager',
            'display_name' => 'Manager',
            'guard_name' => 'web',
            'parent_role_id' => $editorRole->id,
        ], ['delete:Company']); // Adds delete to inherited permissions

        $manager = User::factory()->create();
        $this->roleService->assignRoleToUser($manager, $managerRole, $this->team->id);

        $this->actingAs($manager);
        expect($manager->can('view:Company'))->toBeTrue('Manager should inherit view permission');
        expect($manager->can('create:Company'))->toBeTrue('Manager should inherit create permission');
        expect($manager->can('update:Company'))->toBeTrue('Manager should inherit update permission');
        expect($manager->can('delete:Company'))->toBeTrue('Manager should have delete permission');
    });

    it('enforces security group record-level access', function (): void {
        // Create security groups with different access levels
        $salesGroup = SecurityGroup::factory()->create([
            'team_id' => $this->team->id,
            'name' => 'Sales Team',
            'access_level' => 'group',
        ]);

        $managementGroup = SecurityGroup::factory()->create([
            'team_id' => $this->team->id,
            'name' => 'Management',
            'access_level' => 'all',
            'parent_group_id' => null,
        ]);

        // Create users and assign to groups
        $salesUser = User::factory()->create();
        $managerUser = User::factory()->create();
        $outsideUser = User::factory()->create();

        $this->securityGroupService->addUserToGroup($salesUser, $salesGroup);
        $this->securityGroupService->addUserToGroup($managerUser, $managementGroup);

        // Create test records with different ownership
        $salesRecord = \App\Models\Company::factory()->create([
            'team_id' => $this->team->id,
            'creator_id' => $salesUser->id,
        ]);

        $managementRecord = \App\Models\Company::factory()->create([
            'team_id' => $this->team->id,
            'creator_id' => $managerUser->id,
        ]);

        // Grant record access to groups
        $this->securityGroupService->grantRecordAccess($salesGroup, $salesRecord, 'read_write');
        $this->securityGroupService->grantRecordAccess($managementGroup, $managementRecord, 'read_write');

        // Test group-level access
        $this->actingAs($salesUser);
        $salesUserAccess = $this->securityGroupService->checkRecordAccess($salesUser, $salesRecord);
        expect($salesUserAccess['has_access'])->toBeTrue('Sales user should have access to sales record');
        expect($salesUserAccess['access_level'])->toBe('read_write');

        $salesUserManagementAccess = $this->securityGroupService->checkRecordAccess($salesUser, $managementRecord);
        expect($salesUserManagementAccess['has_access'])->toBeFalse('Sales user should not have access to management record');

        // Test management access (should have access to all records)
        $this->actingAs($managerUser);
        $managerSalesAccess = $this->securityGroupService->checkRecordAccess($managerUser, $salesRecord);
        expect($managerSalesAccess['has_access'])->toBeTrue('Manager should have access to sales record');

        $managerManagementAccess = $this->securityGroupService->checkRecordAccess($managerUser, $managementRecord);
        expect($managerManagementAccess['has_access'])->toBeTrue('Manager should have access to management record');

        // Test outside user (no group membership)
        $this->actingAs($outsideUser);
        $outsideUserAccess = $this->securityGroupService->checkRecordAccess($outsideUser, $salesRecord);
        expect($outsideUserAccess['has_access'])->toBeFalse('Outside user should not have access to any record');
    });

    it('handles hierarchical security group inheritance', function (): void {
        // Create hierarchical security groups
        $companyGroup = SecurityGroup::factory()->create([
            'team_id' => $this->team->id,
            'name' => 'Company Wide',
            'access_level' => 'all',
        ]);

        $departmentGroup = SecurityGroup::factory()->create([
            'team_id' => $this->team->id,
            'name' => 'Sales Department',
            'access_level' => 'group',
            'parent_group_id' => $companyGroup->id,
        ]);

        $teamGroup = SecurityGroup::factory()->create([
            'team_id' => $this->team->id,
            'name' => 'Sales Team A',
            'access_level' => 'group',
            'parent_group_id' => $departmentGroup->id,
        ]);

        // Create users at different levels
        $companyUser = User::factory()->create();
        $departmentUser = User::factory()->create();
        $teamUser = User::factory()->create();

        $this->securityGroupService->addUserToGroup($companyUser, $companyGroup);
        $this->securityGroupService->addUserToGroup($departmentUser, $departmentGroup);
        $this->securityGroupService->addUserToGroup($teamUser, $teamGroup);

        // Create test record
        $record = \App\Models\Company::factory()->create([
            'team_id' => $this->team->id,
            'creator_id' => $teamUser->id,
        ]);

        // Grant access to team level
        $this->securityGroupService->grantRecordAccess($teamGroup, $record, 'read_write');

        // Test inheritance - all levels should have access
        $companyAccess = $this->securityGroupService->checkRecordAccess($companyUser, $record);
        expect($companyAccess['has_access'])->toBeTrue('Company user should inherit access through hierarchy');

        $departmentAccess = $this->securityGroupService->checkRecordAccess($departmentUser, $record);
        expect($departmentAccess['has_access'])->toBeTrue('Department user should inherit access through hierarchy');

        $teamAccess = $this->securityGroupService->checkRecordAccess($teamUser, $record);
        expect($teamAccess['has_access'])->toBeTrue('Team user should have direct access');

        // Test non-inheritable permissions
        $restrictedGroup = SecurityGroup::factory()->create([
            'team_id' => $this->team->id,
            'name' => 'Restricted Team',
            'access_level' => 'group',
            'parent_group_id' => $departmentGroup->id,
            'inherit_permissions' => false,
        ]);

        $restrictedUser = User::factory()->create();
        $this->securityGroupService->addUserToGroup($restrictedUser, $restrictedGroup);

        $restrictedAccess = $this->securityGroupService->checkRecordAccess($restrictedUser, $record);
        expect($restrictedAccess['has_access'])->toBeFalse('Restricted user should not inherit access when inheritance is disabled');
    });

    it('integrates role and security group permissions correctly', function (): void {
        // Create role with basic permissions
        $role = $this->roleService->createRole([
            'name' => 'sales_rep',
            'display_name' => 'Sales Representative',
            'guard_name' => 'web',
        ], ['view:Company', 'update:Company']);

        // Create security group
        $group = SecurityGroup::factory()->create([
            'team_id' => $this->team->id,
            'name' => 'Sales Group',
            'access_level' => 'group',
        ]);

        // Create user with both role and group membership
        $user = User::factory()->create();
        $this->roleService->assignRoleToUser($user, $role, $this->team->id);
        $this->securityGroupService->addUserToGroup($user, $group);

        // Create test record
        $record = \App\Models\Company::factory()->create([
            'team_id' => $this->team->id,
            'creator_id' => $user->id,
        ]);

        $this->securityGroupService->grantRecordAccess($group, $record, 'read_only');

        $this->actingAs($user);

        // Test combined permissions
        expect($user->can('view:Company'))->toBeTrue('User should have view permission from role');
        expect($user->can('update:Company'))->toBeTrue('User should have update permission from role');
        expect($user->can('delete:Company'))->toBeFalse('User should not have delete permission');

        // Test record-level access
        $recordAccess = $this->securityGroupService->checkRecordAccess($user, $record);
        expect($recordAccess['has_access'])->toBeTrue('User should have record access through group');
        expect($recordAccess['access_level'])->toBe('read_only', 'Access level should be limited by group settings');

        // Test that role permissions are limited by record access level
        $effectivePermissions = $this->securityGroupService->getEffectivePermissions($user, $record);
        expect($effectivePermissions['can_view'])->toBeTrue('Should be able to view (role + record access)');
        expect($effectivePermissions['can_update'])->toBeFalse('Should not be able to update (limited by read_only record access)');
    });

    it('handles role and group changes dynamically', function (): void {
        // Create initial setup
        $role = $this->roleService->createRole([
            'name' => 'basic_user',
            'display_name' => 'Basic User',
            'guard_name' => 'web',
        ], ['view:Company']);

        $group = SecurityGroup::factory()->create([
            'team_id' => $this->team->id,
            'name' => 'Basic Group',
            'access_level' => 'group',
        ]);

        $user = User::factory()->create();
        $this->roleService->assignRoleToUser($user, $role, $this->team->id);
        $this->securityGroupService->addUserToGroup($user, $group);

        $record = \App\Models\Company::factory()->create([
            'team_id' => $this->team->id,
        ]);

        $this->securityGroupService->grantRecordAccess($group, $record, 'read_only');

        $this->actingAs($user);

        // Initial state
        expect($user->can('view:Company'))->toBeTrue();
        expect($user->can('update:Company'))->toBeFalse();

        $initialAccess = $this->securityGroupService->checkRecordAccess($user, $record);
        expect($initialAccess['has_access'])->toBeTrue();

        // Update role permissions
        $this->roleService->updateRole($role, [], ['view:Company', 'update:Company']);

        // Clear permission cache
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Test updated permissions
        expect($user->can('update:Company'))->toBeTrue('User should have new update permission');

        // Update group access level
        $this->securityGroupService->updateRecordAccess($group, $record, 'read_write');

        $updatedAccess = $this->securityGroupService->checkRecordAccess($user, $record);
        expect($updatedAccess['access_level'])->toBe('read_write', 'Record access should be updated');

        // Remove user from group
        $this->securityGroupService->removeUserFromGroup($user, $group);

        $removedAccess = $this->securityGroupService->checkRecordAccess($user, $record);
        expect($removedAccess['has_access'])->toBeFalse('User should lose record access when removed from group');

        // Remove role from user
        $this->roleService->removeRoleFromUser($user, $role);

        expect($user->can('view:Company'))->toBeFalse('User should lose role permissions when role is removed');
        expect($user->can('update:Company'))->toBeFalse('User should lose all role permissions');
    });

    it('enforces permissions in Filament resources', function (): void {
        // Create role with limited permissions
        $limitedRole = $this->roleService->createRole([
            'name' => 'limited_user',
            'display_name' => 'Limited User',
            'guard_name' => 'web',
        ], ['view:Role']);

        $user = User::factory()->create();
        $this->roleService->assignRoleToUser($user, $limitedRole, $this->team->id);

        $this->actingAs($user);

        // Test that user can view roles but not create/edit/delete
        $indexResponse = $this->get(RoleResource::getUrl('index'));
        $indexResponse->assertSuccessful();

        // Test that create action is not available
        $createResponse = $this->get(RoleResource::getUrl('create'));
        $createResponse->assertForbidden();

        // Create a role to test edit/delete
        $testRole = Role::factory()->create([
            'name' => 'test_role',
            'display_name' => 'Test Role',
            'guard_name' => 'web',
        ]);

        // Test that edit action is not available
        $editResponse = $this->get(RoleResource::getUrl('edit', ['record' => $testRole]));
        $editResponse->assertForbidden();

        // Test that delete action is not available
        $deleteResponse = $this->delete(RoleResource::getUrl('delete', ['record' => $testRole]));
        $deleteResponse->assertForbidden();

        // Now grant full permissions and test again
        $fullRole = $this->roleService->createRole([
            'name' => 'full_user',
            'display_name' => 'Full User',
            'guard_name' => 'web',
        ], ['view:Role', 'create:Role', 'update:Role', 'delete:Role']);

        $this->roleService->removeRoleFromUser($user, $limitedRole);
        $this->roleService->assignRoleToUser($user, $fullRole, $this->team->id);

        // Clear permission cache
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Test that all actions are now available
        $createResponse2 = $this->get(RoleResource::getUrl('create'));
        $createResponse2->assertSuccessful();

        $editResponse2 = $this->get(RoleResource::getUrl('edit', ['record' => $testRole]));
        $editResponse2->assertSuccessful();

        // Test successful role creation
        $createData = [
            'name' => 'new_test_role',
            'display_name' => 'New Test Role',
            'description' => 'A new test role',
            'guard_name' => 'web',
        ];

        $createPostResponse = $this->post(RoleResource::getUrl('create'), $createData);
        $createPostResponse->assertRedirect();

        $newRole = Role::where('name', 'new_test_role')->first();
        expect($newRole)->not->toBeNull('New role should be created');
        expect($newRole->display_name)->toBe('New Test Role');
    });
});
