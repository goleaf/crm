<?php

declare(strict_types=1);

use App\Models\Role;
use App\Models\RoleAuditLog;
use App\Models\User;
use App\Services\Role\RoleManagementService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;

uses(RefreshDatabase::class);

/**
 * **Feature: customization-administration, Property 6: Auditability**
 *
 * Property: All changes to customizations, roles, and security groups are logged with who/when and can be rolled back.
 *
 * This property tests that:
 * 1. Role creation is logged with proper details
 * 2. Role updates are logged with change details
 * 3. Permission changes are logged
 * 4. User assignments/removals are logged
 * 5. All audit logs include user context and timestamps
 * 6. Audit logs can be used to track change history
 */
it('logs all role changes for auditability', function (): void {
    $service = resolve(RoleManagementService::class);
    $user = User::factory()->create();

    // Act as the user for audit logging
    $this->actingAs($user);

    // Property 1: Role creation should be logged
    $role = $service->createRole([
        'name' => 'test_role',
        'display_name' => 'Test Role',
        'description' => 'A test role for auditing',
        'guard_name' => 'web',
    ]);

    $creationLog = $role->auditLogs()->where('action', 'created')->first();
    expect($creationLog)->not->toBeNull('Role creation should be logged');
    expect($creationLog->user_id)->toBe($user->id, 'Audit log should record the user who created the role');
    expect($creationLog->ip_address)->not->toBeNull('Audit log should record IP address');
    expect($creationLog->changes)->toBeArray('Audit log should contain change details');

    // Property 2: Role updates should be logged with change details
    $originalName = $role->display_name;
    $service->updateRole($role, [
        'display_name' => 'Updated Test Role',
        'description' => 'Updated description',
    ]);

    $updateLog = $role->auditLogs()->where('action', 'updated')->first();
    expect($updateLog)->not->toBeNull('Role update should be logged');
    expect($updateLog->user_id)->toBe($user->id, 'Update log should record the user who made changes');
    expect($updateLog->changes)->toHaveKey('changes', 'Update log should contain change details');

    // Property 3: Permission changes should be logged
    $permission = Permission::create(['name' => 'test:permission', 'guard_name' => 'web']);
    $service->updateRole($role, [], [$permission->name]);

    $permissionLog = $role->auditLogs()->where('action', 'updated')->latest()->first();
    expect($permissionLog->changes)->toHaveKey('permissions_updated', 'Permission changes should be logged');
    expect($permissionLog->changes['permissions_updated'])->toBeTrue('Permission update flag should be set');

    // Property 4: User assignments should be logged
    $assignedUser = User::factory()->create();
    $service->assignRoleToUser($assignedUser, $role);

    $assignmentLog = $role->auditLogs()->where('action', 'user_assigned')->first();
    expect($assignmentLog)->not->toBeNull('User assignment should be logged');
    expect($assignmentLog->changes)->toHaveKey('user_id', 'Assignment log should contain user ID');
    expect($assignmentLog->changes)->toHaveKey('user_name', 'Assignment log should contain user name');
    expect($assignmentLog->changes['user_id'])->toBe($assignedUser->id);
    expect($assignmentLog->changes['user_name'])->toBe($assignedUser->name);

    // Property 5: User removal should be logged
    $service->removeRoleFromUser($assignedUser, $role);

    $removalLog = $role->auditLogs()->where('action', 'user_removed')->first();
    expect($removalLog)->not->toBeNull('User removal should be logged');
    expect($removalLog->changes)->toHaveKey('user_id', 'Removal log should contain user ID');
    expect($removalLog->changes['user_id'])->toBe($assignedUser->id);

    // Property 6: Template creation should be logged
    $newRole = $service->createFromTemplate($role, [
        'name' => 'from_template_role',
        'display_name' => 'Role from Template',
    ]);

    $templateLog = $newRole->auditLogs()->where('action', 'created_from_template')->first();
    expect($templateLog)->not->toBeNull('Template creation should be logged');
    expect($templateLog->changes)->toHaveKey('template_id', 'Template log should contain template ID');
    expect($templateLog->changes['template_id'])->toBe($role->id);

    // Property 7: All audit logs should have proper timestamps and user context
    $allLogs = RoleAuditLog::all();
    foreach ($allLogs as $log) {
        expect($log->created_at)->not->toBeNull('All logs should have timestamps');
        expect($log->user_id)->not->toBeNull('All logs should have user context');
        expect($log->ip_address)->not->toBeNull('All logs should have IP address');
        expect($log->action)->not->toBeEmpty('All logs should have action type');
    }

    // Property 8: Audit trail should be chronological and complete
    $roleLogs = $role->auditLogs()->oldest()->get();
    $actions = $roleLogs->pluck('action')->toArray();

    expect($actions)->toContain('created', 'Audit trail should contain creation');
    expect($actions)->toContain('updated', 'Audit trail should contain updates');
    expect($actions)->toContain('user_assigned', 'Audit trail should contain user assignment');
    expect($actions)->toContain('user_removed', 'Audit trail should contain user removal');

    // Property 9: Audit logs should survive role updates
    $initialLogCount = $role->auditLogs()->count();
    $service->updateRole($role, ['description' => 'Another update']);

    expect($role->auditLogs()->count())->toBe($initialLogCount + 1, 'New audit log should be added for each change');
});

/**
 * Property: Bulk operations are properly audited
 */
it('logs bulk permission operations for auditability', function (): void {
    $service = resolve(RoleManagementService::class);
    $user = User::factory()->create();

    $this->actingAs($user);

    // Create role and permissions
    $role = $service->createRole([
        'name' => 'bulk_test_role',
        'display_name' => 'Bulk Test Role',
        'guard_name' => 'web',
    ]);

    $permissions = [
        Permission::create(['name' => 'view:Resource1', 'guard_name' => 'web']),
        Permission::create(['name' => 'create:Resource1', 'guard_name' => 'web']),
        Permission::create(['name' => 'update:Resource1', 'guard_name' => 'web']),
    ];

    // Perform bulk permission assignment
    $permissionMatrix = [
        'Resource1' => [
            'view' => true,
            'create' => true,
            'update' => false,
        ],
    ];

    $service->bulkAssignPermissions($role, $permissionMatrix);

    // Property: Bulk operations should be logged with details
    $bulkLog = $role->auditLogs()->where('action', 'permissions_bulk_updated')->first();
    expect($bulkLog)->not->toBeNull('Bulk permission update should be logged');
    expect($bulkLog->changes)->toHaveKey('permissions_count', 'Bulk log should contain permission count');
    expect($bulkLog->changes)->toHaveKey('matrix', 'Bulk log should contain permission matrix');
    expect($bulkLog->changes['matrix'])->toBe($permissionMatrix, 'Bulk log should preserve permission matrix details');
    expect($bulkLog->user_id)->toBe($user->id, 'Bulk log should record the user');
});

/**
 * Property: Audit logs provide complete change history
 */
it('provides complete change history through audit logs', function (): void {
    $service = resolve(RoleManagementService::class);
    $user1 = User::factory()->create(['name' => 'User One']);
    $user2 = User::factory()->create(['name' => 'User Two']);

    // Create role as user1
    $this->actingAs($user1);
    $role = $service->createRole([
        'name' => 'history_test_role',
        'display_name' => 'History Test Role',
        'guard_name' => 'web',
    ]);

    // Update role as user2
    $this->actingAs($user2);
    $service->updateRole($role, [
        'display_name' => 'Updated History Test Role',
        'description' => 'Added description',
    ]);

    // Property: Change history should show who made what changes when
    $logs = $role->auditLogs()->with('user')->oldest()->get();

    expect($logs)->toHaveCount(2, 'Should have logs for creation and update');

    $creationLog = $logs->first();
    expect($creationLog->action)->toBe('created');
    expect($creationLog->user->name)->toBe('User One', 'Creation should be attributed to User One');

    $updateLog = $logs->last();
    expect($updateLog->action)->toBe('updated');
    expect($updateLog->user->name)->toBe('User Two', 'Update should be attributed to User Two');
    expect($updateLog->changes['changes'])->toHaveKey('display_name', 'Update log should show what changed');

    // Property: Audit logs should be immutable (cannot be modified after creation)
    $originalLogId = $creationLog->id;
    $originalChanges = $creationLog->changes;

    // Attempt to modify the log (this should not affect the original)
    $creationLog->update(['changes' => ['modified' => 'data']]);

    // Reload from database
    $reloadedLog = RoleAuditLog::find($originalLogId);
    expect($reloadedLog->changes)->toBe($originalChanges, 'Audit logs should maintain data integrity');
});
