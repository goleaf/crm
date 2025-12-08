# Filament Shield Integration Guide

## Overview

Filament Shield provides role-based access control (RBAC) for Filament v4.3+ panels. It integrates with Spatie Laravel Permission to manage roles, permissions, and policies across your application.

## Installation

Shield has been installed and configured with multi-tenancy support:

```bash
composer require bezhansalleh/filament-shield
php artisan shield:install app --tenant
```

## Configuration

### Multi-Tenancy Setup

Shield is configured for team-based tenancy in `config/filament-shield.php`:

```php
'tenant_model' => \App\Models\Team::class,
```

### Shield Resource Location

The Shield role management resource is placed in the Settings cluster:

```php
'shield_resource' => [
    'slug' => 'shield/roles',
    'cluster' => \App\Filament\Clusters\Settings::class,
    'tabs' => [
        'pages' => true,
        'widgets' => true,
        'resources' => true,
        'custom_permissions' => true,
    ],
],
```

### Super Admin

Super admin role is enabled with gate-based authorization:

```php
'super_admin' => [
    'enabled' => true,
    'name' => 'super_admin',
    'define_via_gate' => false,
    'intercept_gate' => 'before',
],
```

## User Model Integration

The `User` model already includes the `HasRoles` trait from Spatie Permission:

```php
use Spatie\Permission\Traits\HasRoles;

final class User extends Authenticatable
{
    use HasRoles;
    // ...
}
```

## Generating Permissions

### Generate for All Resources

```bash
php artisan shield:generate --all
```

### Generate for Specific Resource

```bash
php artisan shield:generate --resource=CompanyResource
```

### Generate for Custom Permissions

```bash
php artisan shield:generate --option=custom
```

## Permission Structure

Shield generates permissions following this pattern:

- **Resources**: `view_any::Company`, `view::Company`, `create::Company`, `update::Company`, `delete::Company`
- **Pages**: `view::Dashboard`, `view::EditProfile`
- **Widgets**: `view::CrmStatsOverview`, `view::LeadTrendChart`

## Policy Integration

Shield automatically generates policies for resources. Existing policies in `app/Policies/` are preserved and enhanced with Shield's permission checks.

### Policy Methods

```php
public function viewAny(User $user): bool
{
    return $user->can('view_any::Company');
}

public function view(User $user, Company $company): bool
{
    return $user->can('view::Company');
}

public function create(User $user): bool
{
    return $user->can('create::Company');
}

public function update(User $user, Company $company): bool
{
    return $user->can('update::Company');
}

public function delete(User $user, Company $company): bool
{
    return $user->can('delete::Company');
}
```

## Resource Authorization

Resources automatically check permissions via policies:

```php
public static function canViewAny(): bool
{
    return auth()->user()->can('view_any::Company');
}

public static function canCreate(): bool
{
    return auth()->user()->can('create::Company');
}
```

## Custom Permissions

Define custom permissions in `config/filament-shield.php`:

```php
'custom_permissions' => [
    'export_reports',
    'import_data',
    'manage_integrations',
    'view_analytics',
],
```

## Role Management UI

Access role management at `/app/shield/roles` (within Settings cluster).

### Creating Roles

1. Navigate to Settings → Roles
2. Click "Create Role"
3. Enter role name and select permissions
4. Assign to team (multi-tenant)

### Assigning Roles

```php
// Assign role to user
$user->assignRole('admin');

// Assign role within team context
$user->assignRole('admin', $team);

// Check if user has role
if ($user->hasRole('admin')) {
    // ...
}

// Check if user has permission
if ($user->can('view_any::Company')) {
    // ...
}
```

## Multi-Tenancy Considerations

### Team-Scoped Roles

Roles and permissions are scoped to teams automatically:

```php
// Get roles for current team
$roles = auth()->user()->roles()->where('team_id', Filament::getTenant()->id)->get();

// Assign role to user in specific team
$user->assignRole('manager', $team);
```

### Cross-Tenant Access Prevention

Shield ensures users can only access resources within their current team:

```php
public static function getEloquentQuery(): Builder
{
    // Filament v4.3 auto-scopes to current tenant
    return parent::getEloquentQuery();
}
```

## Testing

### Feature Tests

```php
use function Pest\Laravel\actingAs;

it('allows admin to view companies', function () {
    $user = User::factory()->create();
    $user->assignRole('admin');
    
    actingAs($user)
        ->get(CompanyResource::getUrl('index'))
        ->assertSuccessful();
});

it('prevents non-admin from creating companies', function () {
    $user = User::factory()->create();
    $user->assignRole('viewer');
    
    actingAs($user)
        ->get(CompanyResource::getUrl('create'))
        ->assertForbidden();
});
```

### Policy Tests

```php
it('checks company view policy', function () {
    $user = User::factory()->create();
    $company = Company::factory()->create();
    
    $user->givePermissionTo('view::Company');
    
    expect($user->can('view', $company))->toBeTrue();
});
```

## Seeding Roles

Create a seeder for default roles:

```php
// database/seeders/RoleSeeder.php
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

public function run(): void
{
    // Create roles
    $admin = Role::create(['name' => 'admin']);
    $manager = Role::create(['name' => 'manager']);
    $user = Role::create(['name' => 'user']);
    
    // Generate permissions
    Artisan::call('shield:generate --all');
    
    // Assign all permissions to admin
    $admin->givePermissionTo(Permission::all());
    
    // Assign specific permissions to manager
    $manager->givePermissionTo([
        'view_any::Company',
        'view::Company',
        'create::Company',
        'update::Company',
    ]);
    
    // Assign read-only permissions to user
    $user->givePermissionTo([
        'view_any::Company',
        'view::Company',
    ]);
}
```

## Best Practices

### DO:
- ✅ Use Shield's role management UI for non-technical users
- ✅ Generate permissions after creating new resources
- ✅ Test authorization in feature tests
- ✅ Use policies for complex authorization logic
- ✅ Scope roles to teams in multi-tenant apps
- ✅ Create seeder for default roles and permissions
- ✅ Use descriptive role names (admin, manager, viewer)
- ✅ Document custom permissions in config

### DON'T:
- ❌ Hardcode permission checks in resources
- ❌ Skip policy generation for new resources
- ❌ Forget to assign roles to new users
- ❌ Mix Shield permissions with custom authorization
- ❌ Allow cross-tenant permission leakage
- ❌ Create too many granular permissions
- ❌ Forget to regenerate permissions after resource changes

## Troubleshooting

### Permissions Not Working

```bash
# Clear cache
php artisan optimize:clear

# Regenerate permissions
php artisan shield:generate --all

# Check role assignments
php artisan tinker
>>> User::find(1)->roles
>>> User::find(1)->permissions
```

### Policy Not Found

```bash
# Generate policy for resource
php artisan shield:generate --resource=CompanyResource
```

### Super Admin Not Working

Check `config/filament-shield.php`:

```php
'super_admin' => [
    'enabled' => true,
    'name' => 'super_admin',
],
```

Assign super admin role:

```php
$user->assignRole('super_admin');
```

## Integration with Existing Policies

Shield merges with existing policies. If you have custom authorization logic, keep it in your policies:

```php
public function update(User $user, Company $company): bool
{
    // Shield permission check
    if (! $user->can('update::Company')) {
        return false;
    }
    
    // Custom logic
    return $company->team_id === $user->currentTeam->id;
}
```

## Related Documentation

- [Spatie Laravel Permission](https://spatie.be/docs/laravel-permission)
- [Filament Shield](https://github.com/bezhanSalleh/filament-shield)
- `.kiro/steering/filament-auth-tenancy.md` - Filament authorization patterns
- `.kiro/steering/filament-conventions.md` - Filament v4.3+ conventions
- `docs/testing-infrastructure.md` - Testing guidelines

## Steering File Updates

When adding Shield integration, update `.kiro/steering/filament-auth-tenancy.md`:

```markdown
## Shield Integration
- Use Filament Shield for role-based access control
- Generate permissions with `php artisan shield:generate --all`
- Roles are team-scoped in multi-tenant applications
- Super admin role bypasses all permission checks
- Custom permissions defined in `config/filament-shield.php`
```
