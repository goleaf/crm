# Pest Route Testing Plugin - Quick Start Guide

## Installation ✅

The plugin is already installed:
```bash
composer require spatie/pest-plugin-route-testing --dev
```

## Quick Start

### 1. Run All Route Tests
```bash
composer test:routes
```

### 2. Run Specific Route Test
```bash
pest tests/Feature/Routes/PublicRoutesTest.php
```

### 3. Run with Coverage
```bash
pest tests/Feature/Routes --coverage
```

## Common Use Cases

### Test Public Routes
```php
use function Spatie\PestPluginRouteTest\routeTesting;

it('can access home page', function (): void {
    routeTesting()
        ->only(['home'])
        ->assertAllRoutesAreAccessible();
});
```

### Test Authenticated Routes
```php
it('can access dashboard when authenticated', function (): void {
    $user = User::factory()->create();
    
    routeTesting()
        ->actingAs($user)
        ->only(['dashboard'])
        ->assertAllRoutesAreAccessible();
});
```

### Test API Routes
```php
it('can access API with token', function (): void {
    $user = User::factory()->create();
    $token = $user->createToken('test')->plainTextToken;
    
    routeTesting()
        ->withToken($token)
        ->only(['contacts.index'])
        ->assertAllRoutesAreAccessible();
});
```

### Test Routes with Parameters
```php
it('can access note print route', function (): void {
    $user = User::factory()->create();
    $note = Note::factory()->create(['team_id' => $user->currentTeam->id]);
    
    routeTesting()
        ->actingAs($user)
        ->bind('note', $note)
        ->only(['notes.print'])
        ->assertAllRoutesAreAccessible();
});
```

## File Structure

```
tests/Feature/Routes/
├── PublicRoutesTest.php           # ✅ Public routes (home, terms, policy)
├── AuthRoutesTest.php             # ✅ Authentication routes
├── AuthenticatedRoutesTest.php    # ✅ Protected routes (dashboard, calendar)
├── ApiRoutesTest.php              # ✅ API routes (contacts)
├── CalendarRoutesTest.php         # ✅ Calendar-specific routes
├── RouteCoverageTest.php          # ✅ Coverage validation
├── AllRoutesTest.php              # ✅ Comprehensive tests
└── RouteTestingConfig.php         # ✅ Centralized configuration
```

## Configuration

All route testing configuration is centralized in `RouteTestingConfig`:

```php
// Get public routes
RouteTestingConfig::publicRoutes();

// Get authenticated routes
RouteTestingConfig::authenticatedRoutes();

// Get API routes
RouteTestingConfig::apiRoutes();

// Check if route should be excluded
RouteTestingConfig::shouldExclude('telescope.index'); // true

// Get required parameters for a route
RouteTestingConfig::getRequiredParameters('notes.print'); // ['note']
```

## Common Commands

```bash
# Run all route tests
composer test:routes

# Run specific test file
pest tests/Feature/Routes/PublicRoutesTest.php

# Run with parallel execution
pest tests/Feature/Routes --parallel

# Run with coverage
pest tests/Feature/Routes --coverage

# Run specific test
pest tests/Feature/Routes --filter="can access home page"

# List all routes
php artisan route:list

# List all named routes
php artisan route:list --json | jq '.[] | select(.name != null) | .name'
```

## Integration with CI/CD

### GitHub Actions
```yaml
- name: Run Route Tests
  run: composer test:routes
```

### GitLab CI
```yaml
test:routes:
  script:
    - composer test:routes
```

## Troubleshooting

### Route Not Found
```bash
# Check if route exists
php artisan route:list | grep "route.name"
```

### Authentication Issues
```php
// Ensure user has proper permissions
$user = User::factory()->create();
$user->givePermissionTo('view_dashboard');

routeTesting()
    ->actingAs($user)
    ->only(['dashboard'])
    ->assertAllRoutesAreAccessible();
```

### Parameter Binding Issues
```php
// Use explicit binding
$note = Note::factory()->create();

routeTesting()
    ->bind('note', $note)
    ->only(['notes.print'])
    ->assertAllRoutesAreAccessible();
```

## Best Practices

### ✅ DO:
- Test all public routes without authentication
- Test authenticated routes with proper user context
- Use factories to create required models
- Group tests by route type
- Run tests in parallel for speed
- Monitor route coverage

### ❌ DON'T:
- Test form submission routes (use feature tests)
- Include third-party package routes
- Skip route testing
- Hardcode route parameters
- Forget to update tests when routes change

## Next Steps

1. ✅ Review existing route tests in `tests/Feature/Routes/`
2. ✅ Run `composer test:routes` to verify all tests pass
3. ✅ Add new route tests when adding new routes
4. ✅ Update `RouteTestingConfig` when route structure changes
5. ✅ Monitor route coverage with `RouteCoverageTest`

## Documentation

- **Comprehensive Guide**: `docs/pest-route-testing-integration.md`
- **Steering File**: `.kiro/steering/pest-route-testing.md`
- **Testing Standards**: `.kiro/steering/testing-standards.md`
- **AGENTS.md**: Repository guidelines

## Support

For issues or questions:
1. Check `docs/pest-route-testing-integration.md` for detailed examples
2. Review `.kiro/steering/pest-route-testing.md` for patterns
3. Examine existing tests in `tests/Feature/Routes/`
4. Run `php artisan route:list` to debug route issues

---

**Status**: ✅ Fully Integrated
**Version**: 1.1.4
**Last Updated**: 2025-01-12
