# Route Testing Suite

This directory contains automated tests for all application routes using the Pest Route Testing Plugin (`spatie/pest-plugin-route-testing`).

## Purpose

Ensure all routes in the application:
- Are accessible and return expected responses
- Have proper authentication/authorization
- Handle parameters correctly
- Redirect as expected
- Maintain proper middleware configuration

## Test Files

### RouteTestingConfig.php
Centralized configuration for all route tests. Defines:
- Excluded routes (third-party packages, complex forms)
- Public routes (accessible without auth)
- Authenticated routes (require auth middleware)
- API routes (require Sanctum tokens)
- Guest routes (accessible only when not authenticated)
- Parametric routes (require model binding)
- Redirect routes (expected to redirect)
- Signed routes (require signed URLs)
- Precognition routes (support Laravel Precognition)

### PublicRoutesTest.php
Tests for routes accessible without authentication:
- Home page
- Terms of service
- Privacy policy
- Security.txt
- External redirects (Discord)

### AuthRoutesTest.php
Tests for authentication-related routes:
- Login
- Register
- Password reset
- Email verification
- Social authentication

### AuthenticatedRoutesTest.php
Tests for routes requiring authentication:
- Dashboard
- Calendar
- Notes (with model binding)
- Purchase orders
- Team invitations
- Email verification

### ApiRoutesTest.php
Tests for API routes with Sanctum authentication:
- Contacts index
- Contacts show
- Precognition validation
- JSON response format

### CalendarRoutesTest.php
Tests for calendar-specific routes:
- Calendar view
- iCal export
- Event management

### FilamentRoutesTest.php
Tests for Filament admin panel routes:
- Dashboard
- Resources
- Pages
- Widgets

### RouteCoverageTest.php
Validates that all routes are either:
- Tested explicitly
- Excluded intentionally
- Documented in RouteTestingConfig

### AllRoutesTest.php
Comprehensive tests for all route categories combined.

## Running Tests

### All Route Tests
```bash
composer test:routes
```

### Specific Test File
```bash
pest tests/Feature/Routes/PublicRoutesTest.php
```

### With Parallel Execution
```bash
pest tests/Feature/Routes --parallel
```

### With Coverage
```bash
pest tests/Feature/Routes --coverage
```

### Single Test
```bash
pest tests/Feature/Routes/PublicRoutesTest.php --filter="can access home page"
```

## Adding New Route Tests

### 1. Identify Route Type
Determine if the route is:
- Public (no auth required)
- Authenticated (requires auth)
- API (requires Sanctum token)
- Guest-only (redirects when authenticated)
- Parametric (requires model binding)

### 2. Update RouteTestingConfig
Add the route to the appropriate array:

```php
// For public routes
public static function publicRoutes(): array
{
    return [
        'home',
        'your.new.route', // Add here
    ];
}

// For authenticated routes
public static function authenticatedRoutes(): array
{
    return [
        'dashboard',
        'your.new.route', // Add here
    ];
}

// For parametric routes
public static function parametricRoutes(): array
{
    return [
        'notes.print' => ['note'],
        'your.new.route' => ['model'], // Add here
    ];
}
```

### 3. Create Test
Add test to appropriate file:

```php
it('can access your new route', function (): void {
    // Setup (if needed)
    $user = User::factory()->create();
    $model = Model::factory()->create();
    
    routeTesting()
        ->actingAs($user) // If authenticated
        ->bind('model', $model) // If parametric
        ->only(['your.new.route'])
        ->assertAllRoutesAreAccessible();
});
```

### 4. Run Test
```bash
pest tests/Feature/Routes/YourTestFile.php
```

## Common Patterns

### Public Route
```php
it('can access public route', function (): void {
    routeTesting()
        ->only(['public.route'])
        ->assertAllRoutesAreAccessible();
});
```

### Authenticated Route
```php
it('can access authenticated route', function (): void {
    $user = User::factory()->create();
    
    routeTesting()
        ->actingAs($user)
        ->only(['auth.route'])
        ->assertAllRoutesAreAccessible();
});
```

### API Route with Token
```php
it('can access API route', function (): void {
    $user = User::factory()->create();
    $token = $user->createToken('test')->plainTextToken;
    
    routeTesting()
        ->withToken($token)
        ->only(['api.route'])
        ->assertAllRoutesAreAccessible();
});
```

### Parametric Route
```php
it('can access parametric route', function (): void {
    $user = User::factory()->create();
    $model = Model::factory()->create([
        'team_id' => $user->currentTeam->id,
    ]);
    
    routeTesting()
        ->actingAs($user)
        ->bind('model', $model)
        ->only(['model.show'])
        ->assertAllRoutesAreAccessible();
});
```

### Redirect Route
```php
it('redirects to expected location', function (): void {
    routeTesting()
        ->only(['redirect.route'])
        ->assertAllRoutesRedirect();
});
```

### Guest-Only Route
```php
it('redirects authenticated users', function (): void {
    $user = User::factory()->create();
    
    routeTesting()
        ->actingAs($user)
        ->only(['guest.route'])
        ->assertAllRoutesRedirect();
});
```

## Troubleshooting

### Route Not Found
```bash
# Check if route exists
php artisan route:list | grep route.name

# Clear route cache
php artisan route:clear
php artisan optimize:clear
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

### Tenant Scoping Issues
```php
// Ensure model belongs to user's team
$user = User::factory()->create();
$model = Model::factory()->create([
    'team_id' => $user->currentTeam->id,
]);

routeTesting()
    ->actingAs($user)
    ->bind('model', $model)
    ->only(['model.show'])
    ->assertAllRoutesAreAccessible();
```

### Get Help
```bash
# Run troubleshooting guide
kiro run route-test-help
```

## Best Practices

### DO:
✅ Test all public routes without authentication  
✅ Test authenticated routes with proper user context  
✅ Use factories to create required models  
✅ Group tests by route type  
✅ Update RouteTestingConfig when adding routes  
✅ Run tests before committing  
✅ Keep tests focused and simple  

### DON'T:
❌ Test form submission routes (use feature tests)  
❌ Test complex flows (use feature tests)  
❌ Include third-party package routes  
❌ Hardcode route parameters  
❌ Skip authentication setup  
❌ Forget to update config  

## Automation

### Automatic Testing
Route tests run automatically when route files change via:
- `.kiro/hooks/route-testing-automation.kiro.hook`

### CI Integration
Route tests are included in:
- `composer test` - Full test suite
- `composer test:ci` - CI pipeline
- GitHub Actions workflow
- GitLab CI pipeline

## Documentation

- **Complete Guide**: `docs/pest-route-testing-complete-guide.md`
- **Integration Guide**: `docs/pest-route-testing-integration.md`
- **Steering Rules**: `.kiro/steering/pest-route-testing.md`
- **Testing Standards**: `.kiro/steering/testing-standards.md`

## Package Information

- **Package**: `spatie/pest-plugin-route-testing`
- **Version**: 1.1.4
- **Repository**: https://github.com/spatie/pest-plugin-route-testing
- **License**: MIT

## Support

For issues or questions:
1. Check documentation in `docs/`
2. Review examples in this directory
3. Run troubleshooting guide: `kiro run route-test-help`
4. Check package documentation
