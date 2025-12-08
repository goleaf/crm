# Pest Route Testing Plugin Integration

## Overview
The Pest Route Testing Plugin (`spatie/pest-plugin-route-testing`) provides automated testing for all routes in your Laravel application, ensuring every route is accessible, properly configured, and returns expected responses.

## Installation
```bash
composer require spatie/pest-plugin-route-testing --dev
```

## Core Concepts

### What It Tests
- **Route Accessibility**: Ensures all routes are reachable
- **HTTP Methods**: Validates GET, POST, PUT, PATCH, DELETE routes
- **Middleware**: Verifies authentication, authorization, and custom middleware
- **Response Codes**: Checks for 200, 302, 401, 403, 404, etc.
- **Named Routes**: Validates route names and parameters
- **Route Groups**: Tests grouped routes with shared middleware

### Why Use It
- **Catch Breaking Changes**: Detect route configuration errors before deployment
- **Prevent 404s**: Ensure all routes remain accessible after refactoring
- **Middleware Validation**: Verify auth/guest middleware is correctly applied
- **Documentation**: Auto-generate route documentation from tests
- **Regression Prevention**: Catch accidental route deletions or changes

## Basic Usage

### Test All Routes
```php
use function Spatie\PestPluginRouteTest\routeTesting;

it('can access all routes', function () {
    routeTesting()
        ->assertAllRoutesAreAccessible();
});
```

### Test Specific Routes
```php
it('can access public routes', function () {
    routeTesting()
        ->only(['home', 'terms.show', 'policy.show'])
        ->assertAllRoutesAreAccessible();
});
```

### Exclude Routes
```php
it('can access routes except admin', function () {
    routeTesting()
        ->except(['admin.*', 'telescope.*'])
        ->assertAllRoutesAreAccessible();
});
```

## Advanced Patterns

### Authenticated Routes
```php
it('can access authenticated routes', function () {
    $user = User::factory()->create();
    
    routeTesting()
        ->actingAs($user)
        ->only(['dashboard', 'profile.*'])
        ->assertAllRoutesAreAccessible();
});
```

### Guest Routes
```php
it('redirects authenticated users from guest routes', function () {
    $user = User::factory()->create();
    
    routeTesting()
        ->actingAs($user)
        ->only(['login', 'register'])
        ->assertAllRoutesRedirect();
});
```

### API Routes with Sanctum
```php
it('can access API routes with token', function () {
    $user = User::factory()->create();
    $token = $user->createToken('test')->plainTextToken;
    
    routeTesting()
        ->withToken($token)
        ->only(['api.*'])
        ->assertAllRoutesAreAccessible();
});
```

### Routes with Parameters
```php
it('can access routes with parameters', function () {
    $note = Note::factory()->create();
    
    routeTesting()
        ->bind('note', $note)
        ->only(['notes.print'])
        ->assertAllRoutesAreAccessible();
});
```

### Custom Assertions
```php
it('returns JSON for API routes', function () {
    routeTesting()
        ->only(['api.*'])
        ->assertAllRoutesReturn(function ($response) {
            expect($response)->toHaveHeader('Content-Type', 'application/json');
        });
});
```

## Integration with Existing Tests

### Filament Routes
```php
it('can access Filament admin routes', function () {
    $admin = User::factory()->admin()->create();
    
    routeTesting()
        ->actingAs($admin)
        ->only(['filament.*'])
        ->except(['filament.*.create', 'filament.*.edit']) // Skip form routes
        ->assertAllRoutesAreAccessible();
});
```

### Multi-Tenancy Routes
```php
it('can access tenant routes', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $user->teams()->attach($team);
    
    routeTesting()
        ->actingAs($user)
        ->bind('team', $team)
        ->only(['filament.app.*'])
        ->assertAllRoutesAreAccessible();
});
```

### Social Authentication
```php
it('can access social auth routes', function () {
    routeTesting()
        ->only(['auth.socialite.*'])
        ->assertAllRoutesAreAccessible();
});
```

## Best Practices

### DO:
- ✅ Test all public routes without authentication
- ✅ Test authenticated routes with proper user context
- ✅ Exclude routes that require specific state (forms, wizards)
- ✅ Use factories to create required models for route parameters
- ✅ Group tests by middleware (guest, auth, admin)
- ✅ Test API routes separately with proper authentication
- ✅ Use descriptive test names that explain what's being tested

### DON'T:
- ❌ Test form submission routes (use feature tests instead)
- ❌ Test routes that require complex setup without proper context
- ❌ Include third-party package routes (Telescope, Horizon) in main tests
- ❌ Test routes that intentionally return errors (404, 403)
- ❌ Skip route testing because "it's too slow" (it's fast!)

## Performance Optimization

### Parallel Testing
```php
// Pest.php
pest()->extend(Tests\TestCase::class)
    ->use(RefreshDatabase::class)
    ->in('Feature/Routes');
```

Run with:
```bash
pest --parallel tests/Feature/Routes
```

### Selective Testing
```php
// Test only changed routes in CI
it('can access recently changed routes', function () {
    routeTesting()
        ->only(['calendar.*', 'notes.print'])
        ->assertAllRoutesAreAccessible();
});
```

### Caching Route List
```php
// Cache route list for faster subsequent runs
beforeEach(function () {
    if (!Cache::has('route_list')) {
        Cache::put('route_list', Route::getRoutes(), 3600);
    }
});
```

## Common Patterns

### Test Public Routes
```php
it('can access all public routes', function () {
    routeTesting()
        ->only(['home', 'terms.show', 'policy.show', 'security.txt'])
        ->assertAllRoutesAreAccessible();
});
```

### Test Auth Routes
```php
it('can access authentication routes', function () {
    routeTesting()
        ->only(['login', 'register', 'password.*', 'verification.*'])
        ->assertAllRoutesAreAccessible();
});
```

### Test Redirects
```php
it('redirects dashboard to app URL', function () {
    routeTesting()
        ->only(['dashboard'])
        ->assertAllRoutesRedirect();
});
```

### Test External Redirects
```php
it('redirects to external services', function () {
    routeTesting()
        ->only(['discord'])
        ->assertAllRoutesRedirect();
});
```

## Troubleshooting

### Route Not Found
```php
// Check if route exists
Route::has('route.name'); // true/false

// List all routes
php artisan route:list
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
    ->bind('id', $note->id)
    ->only(['notes.print'])
    ->assertAllRoutesAreAccessible();
```

### Middleware Conflicts
```php
// Skip middleware for testing
routeTesting()
    ->withoutMiddleware()
    ->only(['admin.*'])
    ->assertAllRoutesAreAccessible();
```

## Integration with CI/CD

### GitHub Actions
```yaml
- name: Run Route Tests
  run: composer test:routes
```

### Composer Script
```json
{
  "scripts": {
    "test:routes": "pest --filter=routes --parallel"
  }
}
```

## Related Documentation
- `docs/laravel-precognition.md` - Form validation testing
- `docs/pest-laravel-expectations.md` - HTTP assertion helpers
- `.kiro/steering/testing-standards.md` - Testing conventions
- `.kiro/steering/filament-testing.md` - Filament-specific testing

## Examples in This Project
- `tests/Feature/Routes/PublicRoutesTest.php` - Public route tests
- `tests/Feature/Routes/AuthRoutesTest.php` - Authentication route tests
- `tests/Feature/Routes/ApiRoutesTest.php` - API route tests
- `tests/Feature/Routes/FilamentRoutesTest.php` - Filament admin route tests

## Quick Reference

### Common Test Patterns
```php
// Public routes
routeTesting()->only(['home', 'terms.show'])->assertAllRoutesAreAccessible();

// Auth routes
routeTesting()->actingAs($user)->only(['dashboard'])->assertAllRoutesAreAccessible();

// API routes
routeTesting()->withToken($token)->only(['api.*'])->assertAllRoutesAreAccessible();

// Redirects
routeTesting()->only(['login'])->assertAllRoutesRedirect();

// Exclude routes
routeTesting()->except(['admin.*', 'telescope.*'])->assertAllRoutesAreAccessible();

// With parameters
routeTesting()->bind('note', $note)->only(['notes.print'])->assertAllRoutesAreAccessible();
```

### Response Assertions
```php
// 200 OK
->assertAllRoutesAreAccessible()

// 302 Redirect
->assertAllRoutesRedirect()

// Custom assertion
->assertAllRoutesReturn(fn($response) => expect($response)->toBeOk())
```

## Maintenance

### Updating Tests After Route Changes
1. Add new routes to appropriate test file
2. Update route bindings if parameters changed
3. Adjust middleware expectations
4. Run `composer test:routes` to verify
5. Update documentation if route behavior changed

### Monitoring Route Coverage
```bash
# List all routes
php artisan route:list

# Count routes
php artisan route:list --json | jq 'length'

# Find untested routes
php artisan route:list --json | jq '.[] | select(.name != null) | .name'
```
