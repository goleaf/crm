<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

final class RoleSeeder extends Seeder
{
    public function run(): void
    {
        // Create basic role templates
        $this->createRoleTemplates();

        // Create default roles
        $this->createDefaultRoles();
    }

    private function createRoleTemplates(): void
    {
        // Admin Template
        $adminTemplate = Role::create([
            'name' => 'admin_template',
            'display_name' => 'Administrator Template',
            'description' => 'Template for administrator roles with full system access',
            'guard_name' => 'web',
            'is_template' => true,
            'is_admin_role' => true,
            'is_studio_role' => false,
        ]);

        // Manager Template
        $managerTemplate = Role::create([
            'name' => 'manager_template',
            'display_name' => 'Manager Template',
            'description' => 'Template for manager roles with team management capabilities',
            'guard_name' => 'web',
            'is_template' => true,
            'is_admin_role' => false,
            'is_studio_role' => false,
        ]);

        // Studio Template
        $studioTemplate = Role::create([
            'name' => 'studio_template',
            'display_name' => 'Studio Developer Template',
            'description' => 'Template for studio developers with customization access',
            'guard_name' => 'web',
            'is_template' => true,
            'is_admin_role' => false,
            'is_studio_role' => true,
        ]);

        // User Template
        $userTemplate = Role::create([
            'name' => 'user_template',
            'display_name' => 'Standard User Template',
            'description' => 'Template for standard users with basic access',
            'guard_name' => 'web',
            'is_template' => true,
            'is_admin_role' => false,
            'is_studio_role' => false,
        ]);

        // Assign permissions to templates
        $this->assignTemplatePermissions($adminTemplate, $managerTemplate, $studioTemplate, $userTemplate);
    }

    private function createDefaultRoles(): void
    {
        // System Administrator (non-template)
        $systemAdmin = Role::create([
            'name' => 'system_administrator',
            'display_name' => 'System Administrator',
            'description' => 'Full system access including user management and system configuration',
            'guard_name' => 'web',
            'is_template' => false,
            'is_admin_role' => true,
            'is_studio_role' => true,
        ]);

        // Team Manager (non-template)
        $teamManager = Role::create([
            'name' => 'team_manager',
            'display_name' => 'Team Manager',
            'description' => 'Manage team members and team-specific resources',
            'guard_name' => 'web',
            'is_template' => false,
            'is_admin_role' => false,
            'is_studio_role' => false,
        ]);

        // Standard User (non-template)
        $standardUser = Role::create([
            'name' => 'standard_user',
            'display_name' => 'Standard User',
            'description' => 'Basic access to CRM features',
            'guard_name' => 'web',
            'is_template' => false,
            'is_admin_role' => false,
            'is_studio_role' => false,
        ]);

        // Assign permissions to default roles
        $this->assignDefaultRolePermissions($systemAdmin, $teamManager, $standardUser);
    }

    private function assignTemplatePermissions(Role $adminTemplate, Role $managerTemplate, Role $studioTemplate, Role $userTemplate): void
    {
        // Get all permissions
        $allPermissions = Permission::all();
        $rolePermissions = $allPermissions->filter(fn ($p): bool => str_contains((string) $p->name, ':Role'));
        $companyPermissions = $allPermissions->filter(fn ($p): bool => str_contains((string) $p->name, ':Company'));
        $peoplePermissions = $allPermissions->filter(fn ($p): bool => str_contains((string) $p->name, ':People'));
        $taskPermissions = $allPermissions->filter(fn ($p): bool => str_contains((string) $p->name, ':Task'));
        $notePermissions = $allPermissions->filter(fn ($p): bool => str_contains((string) $p->name, ':Note'));

        // Admin Template - All permissions
        $adminTemplate->syncPermissions($allPermissions);

        // Manager Template - Management permissions
        $managerTemplate->syncPermissions([
            ...$companyPermissions->all(),
            ...$peoplePermissions->all(),
            ...$taskPermissions->all(),
            ...$notePermissions->all(),
            // Add view permissions for roles but not create/edit/delete
            ...$rolePermissions->filter(fn ($p): bool => str_contains((string) $p->name, 'view'))->all(),
        ]);

        // Studio Template - Studio and development permissions
        $studioTemplate->syncPermissions([
            ...$rolePermissions->all(),
            // Add other studio-related permissions as they become available
        ]);

        // User Template - Basic read permissions
        $userTemplate->syncPermissions([
            ...$companyPermissions->filter(fn ($p): bool => str_contains((string) $p->name, 'view'))->all(),
            ...$peoplePermissions->filter(fn ($p): bool => str_contains((string) $p->name, 'view'))->all(),
            ...$taskPermissions->filter(fn ($p): bool => str_contains((string) $p->name, 'view'))->all(),
            ...$notePermissions->filter(fn ($p): bool => str_contains((string) $p->name, 'view'))->all(),
        ]);
    }

    private function assignDefaultRolePermissions(Role $systemAdmin, Role $teamManager, Role $standardUser): void
    {
        // Get all permissions
        $allPermissions = Permission::all();
        $companyPermissions = $allPermissions->filter(fn ($p): bool => str_contains((string) $p->name, ':Company'));
        $peoplePermissions = $allPermissions->filter(fn ($p): bool => str_contains((string) $p->name, ':People'));
        $taskPermissions = $allPermissions->filter(fn ($p): bool => str_contains((string) $p->name, ':Task'));
        $notePermissions = $allPermissions->filter(fn ($p): bool => str_contains((string) $p->name, ':Note'));

        // System Administrator - All permissions
        $systemAdmin->syncPermissions($allPermissions);

        // Team Manager - Management permissions
        $teamManager->syncPermissions([
            ...$companyPermissions->all(),
            ...$peoplePermissions->all(),
            ...$taskPermissions->all(),
            ...$notePermissions->all(),
        ]);

        // Standard User - Basic permissions
        $standardUser->syncPermissions([
            ...$companyPermissions->filter(fn ($p): bool => in_array(explode(':', (string) $p->name)[0], ['view', 'viewAny']))->all(),
            ...$peoplePermissions->filter(fn ($p): bool => in_array(explode(':', (string) $p->name)[0], ['view', 'viewAny']))->all(),
            ...$taskPermissions->filter(fn ($p): bool => in_array(explode(':', (string) $p->name)[0], ['view', 'viewAny', 'create', 'update']))->all(),
            ...$notePermissions->filter(fn ($p): bool => in_array(explode(':', (string) $p->name)[0], ['view', 'viewAny', 'create', 'update']))->all(),
        ]);
    }
}
