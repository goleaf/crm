<?php

declare(strict_types=1);

use App\Models\Role;
use App\Models\User;
use App\Services\Role\RoleManagementService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;

uses(RefreshDatabase::class);

/**
 * **Feature: customization-administration, Property 3: Permission enforcement**
 *
 * Property: Roles and security groups enforce module/field/action permissions and record-level access consistently.
 *
 * This property tests that:
 * 1. Users with roles have the permissions assigned to those roles
 * 2. Users without roles do not have permissions
 * 3. Role inheritance works correctly (child roles inherit parent permissions)
 * 4. Permission changes are immediately reflected in user access
 */
it('enforces permissions consistently across roles and users', function (): void {
    // Create permissions
    $viewPermission = Permission::create(['name' => 'view:TestResource', 'guard_name' => 'web']);
    $createPermission = Permission::create(['name' => 'create:TestResource', 'guard_name' => 'web']);
    $editPermission = Permission::create(['name' => 'update:TestResource', 'guard_name' => 'web']);
    $deletePermission = Permission::create(['name' => 'delete:TestResource', 'guard_name' => 'web']);

    // Create parent role with view and create permissions
    $parentRole = Role::factory()->create([
        'name' => 'parent_role',
        'display_name' => 'Parent Role',
    ]);
    $parentRole->givePermissionTo([$viewPermission, $createPermission]);

    // Create child role that inherits from parent and adds edit permission
    $childRole = Role::factory()->withParent($parentRole)->create([
        'name' => 'child_role',
        'display_name' => 'Child Role',
    ]);
    $childRole->givePermissionTo($editPermission);

    // Create user and assign child role
    $user = User::factory()->create();
    $team = \App\Models\Team::factory()->create();
    $user->assignRole($childRole->name, $team);

    // Property 1: User should have permissions from both parent and child roles
    expect($user->can('view:TestResource'))->toBeTrue('User should inherit view permission from parent role');
    expect($user->can('create:TestResource'))->toBeTrue('User should inherit create permission from parent role');
    expect($user->can('update:TestResource'))->toBeTrue('User should have edit permission from child role');
    expect($user->can('delete:TestResource'))->toBeFalse('User should not have delete permission');

    // Property 2: Role inheritance should work through getAllPermissions method
    $allPermissions = $childRole->getAllPermissions();
    $permissionNames = $allPermissions->pluck('name')->toArray();

    expect($permissionNames)->toContain('view:TestResource', 'Child role should inherit view permission');
    expect($permissionNames)->toContain('create:TestResource', 'Child role should inherit create permission');
    expect($permissionNames)->toContain('update:TestResource', 'Child role should have its own edit permission');
    expect($permissionNames)->not->toContain('delete:TestResource', 'Child role should not have delete permission');

    // Property 3: Permission changes should be immediately reflected
    $parentRole->givePermissionTo($deletePermission);

    // Clear permission cache
    app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

    // User should now have delete permission through inheritance
    expect($user->can('delete:TestResource'))->toBeTrue('User should inherit new delete permission from parent role');

    // Property 4: Removing role should remove all permissions
    $user->removeRole($childRole);

    expect($user->can('view:TestResource'))->toBeFalse('User should lose view permission after role removal');
    expect($user->can('create:TestResource'))->toBeFalse('User should lose create permission after role removal');
    expect($user->can('update:TestResource'))->toBeFalse('User should lose edit permission after role removal');
    expect($user->can('delete:TestResource'))->toBeFalse('User should lose delete permission after role removal');
});

/**
 * Property: Role management service enforces permission consistency
 */
it('maintains permission consistency through role management service', function (): void {
    $service = resolve(RoleManagementService::class);

    // Create permissions
    $permissions = [
        Permission::create(['name' => 'view:Company', 'guard_name' => 'web']),
        Permission::create(['name' => 'create:Company', 'guard_name' => 'web']),
        Permission::create(['name' => 'update:Company', 'guard_name' => 'web']),
    ];

    // Create role through service
    $role = $service->createRole([
        'name' => 'company_manager',
        'display_name' => 'Company Manager',
        'guard_name' => 'web',
    ], collect($permissions)->pluck('name')->toArray());

    // Create user and assign role through service
    $user = User::factory()->create();
    $team = \App\Models\Team::factory()->create();
    $service->assignRoleToUser($user, $role, $team->id);

    // Property: User should have all role permissions
    foreach ($permissions as $permission) {
        expect($user->can($permission->name))->toBeTrue("User should have {$permission->name} permission");
    }

    // Update role permissions through service
    $newPermissions = ['view:Company', 'create:Company']; // Remove update permission
    $service->updateRole($role, [], $newPermissions);

    // Clear permission cache
    app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

    // Property: Permission changes should be reflected immediately
    expect($user->can('view:Company'))->toBeTrue('User should still have view permission');
    expect($user->can('create:Company'))->toBeTrue('User should still have create permission');
    expect($user->can('update:Company'))->toBeFalse('User should lose update permission');

    // Remove role through service
    $service->removeRoleFromUser($user, $role);

    // Property: User should lose all permissions
    expect($user->can('view:Company'))->toBeFalse('User should lose view permission');
    expect($user->can('create:Company'))->toBeFalse('User should lose create permission');
});

/**
 * Property: Circular inheritance prevention
 */
it('prevents circular role inheritance', function (): void {
    $service = resolve(RoleManagementService::class);

    // Create roles
    $roleA = $service->createRole([
        'name' => 'role_a',
        'display_name' => 'Role A',
        'guard_name' => 'web',
    ]);

    $roleB = $service->createRole([
        'name' => 'role_b',
        'display_name' => 'Role B',
        'guard_name' => 'web',
        'parent_role_id' => $roleA->id,
    ]);

    // Property: Attempting to create circular inheritance should fail
    expect(fn () => $service->updateRole($roleA, ['parent_role_id' => $roleB->id]))
        ->toThrow(InvalidArgumentException::class, 'Circular role inheritance detected.');
});

/**
 * Property: Template roles work correctly
 */
it('creates roles from templates with correct permissions', function (): void {
    $service = resolve(RoleManagementService::class);

    // Create permissions
    $permissions = [
        Permission::create(['name' => 'view:Task', 'guard_name' => 'web']),
        Permission::create(['name' => 'create:Task', 'guard_name' => 'web']),
    ];

    // Create template role
    $template = $service->createRole([
        'name' => 'task_manager_template',
        'display_name' => 'Task Manager Template',
        'guard_name' => 'web',
        'is_template' => true,
    ], collect($permissions)->pluck('name')->toArray());

    // Create role from template
    $newRole = $service->createFromTemplate($template, [
        'name' => 'task_manager_team_a',
        'display_name' => 'Task Manager - Team A',
    ]);

    // Property: New role should have same permissions as template
    $templatePermissions = $template->permissions->pluck('name')->sort()->values();
    $newRolePermissions = $newRole->permissions->pluck('name')->sort()->values();

    expect($newRolePermissions->toArray())->toBe($templatePermissions->toArray(), 'Role created from template should have same permissions');

    // Property: New role should not be a template
    expect($newRole->is_template)->toBeFalse('Role created from template should not be a template itself');

    // Property: Template properties should be copied
    expect($newRole->is_admin_role)->toBe($template->is_admin_role);
    expect($newRole->is_studio_role)->toBe($template->is_studio_role);
});
