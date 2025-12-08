# Pest Route Testing Plugin - Complete Integration Guide

## Overview

The Pest Route Testing Plugin (`spatie/pest-plugin-route-testing`) is fully integrated into this Laravel/Filament v4 application to ensure all routes remain accessible, properly configured, and return expected responses.

## Installation Status

✅ **Package Installed**: `spatie/pest-plugin-route-testing` v1.1.4  
✅ **Test Suite Created**: `tests/Feature/Routes/`  
✅ **Composer Script**: `composer test:routes`  
✅ **CI Integration**: Included in `composer test` and `composer test:ci`

## Directory Structure

```
tests/Feature/Routes/
├── RouteTestingConfig.php         # Centralized configuration
├── PublicRoutesTest.php           # Public routes (home, terms, policy)
├── AuthRoutesTest.php             # Authentication routes (login, register)
├── AuthenticatedRoutesTest.php    # Protected routes (dashboard, calendar)
├── ApiRoutesTest.php              # API routes (contacts, resources)
├── CalendarRoutesTest.php         # Calendar-specific routes
├── FilamentRoutesTest.php         # Filament admin routes
├── RouteCoverageTest.php          # Coverage validation
└── AllRoutesTest.php              # Comprehensive route tests
```

## Configuration

### RouteTestingConfig.php

This class provides centralized configuration for all route tests:

#### Excluded Routes
Routes that should NOT be tested automatically:
- Third-party packages (Telescope, Horizon, Clockwork)
- Form submission routes (tested separately in feature tests)
- Complex Filament forms (create, edit pages)
- Livewire internal routes
- Signed URL routes (require special handling)
- Social auth callbacks (require external providers)

#### Route Categories
- **Public Routes**: Accessible without authentication
- **Authenticated Routes**: Require `auth` middleware
- **API Routes**: Require Sanctum token authentication
- **Guest Routes**: Accessible only when NOT authenticated
- **Parametric Routes**: Require route parameters (models)
- **Redirect Routes**: Expected to redirect
- **Signed Routes**: Require signed URLs
- **Precognition Routes**: Support Laravel Precognition validation

## Test Patterns

### 1. Public Routes

```php
it('can access home page', function (): void {
    routeTesting()
        ->only(['home'])
        ->assertAllRoutesAreAccessible();
});
```

**What it tests:**
- Route is accessible without authentication
- Returns 200 OK status
- No redirects or errors

### 2. Authenticated Routes

```php
it('can access calendar when authenticated', function (): void {
    $user = User::factory()->create();
    
    routeTesting()
        ->actingAs($user)
        ->only(['calendar'])
        ->assertAllRoutesAreAccessible();
});
```

**What it tests:**
- Route requires authentication
- Authenticated users can access
- Returns 200 OK status

### 3. API Routes with Sanctum

```php
it('can access contact index with authentication', function (): void {
    $user = User::factory()->create();
    $token = $user->createToken('test')->plainTextToken;
    
    routeTesting()
        ->withToken($token)
        ->only(['contacts.index'])
        ->assertAllRoutesAreAccessible();
});
```

**What it tests:**
- API route requires Sanctum token
- Valid token grants access
- Returns 200 OK status
- JSON response format

### 4. Parametric Routes

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

**What it tests:**
- Route with parameters works correctly
- Model binding functions properly
- Tenant scoping is respected

### 5. Redirect Routes

```php
it('redirects dashboard to app URL', function (): void {
    routeTesting()
        ->only(['dashboard'])
        ->assertAllRoutesRedirect();
});
```

**What it tests:**
- Route redirects as expected
- Returns 302 status
- Redirect location is correct

### 6. Guest-Only Routes

```php
it('redirects authenticated users from login', function (): void {
    $user = User::factory()->create();
    
    routeTesting()
        ->actingAs($user)
        ->only(['login'])
        ->assertAllRoutesRedirect();
});
```

**What it tests:**
- Authenticated users cannot access guest routes
- Proper redirect to dashboard/home

### 7. Signed URL Routes

```php
it('can access email verification route', function (): void {
    $user = User::factory()->unverified()->create();
    
    $url = URL::signedRoute('verification.verify', [
        'id' => $user->id,
        'hash' => sha1($user->email),
    ]);
    
    $this->actingAs($user)
        ->get($url)
        ->assertRedirect(route('dashboard'));
});
```

**What it tests:**
- Signed URLs work correctly
- Invalid signatures are rejected
- Proper redirect after verification

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

### In CI Pipeline
```bash
composer test:ci  # Includes route tests
```

## Integration with CI/CD

### GitHub Actions

```yaml
name: Tests

on: [push, pull_request]

jobs:
  test:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v3
      
      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: 8.4
          
      - name: Install Dependencies
        run: composer install
        
      - name: Run Route Tests
        run: composer test:routes
```

### GitLab CI

```yaml
test:routes:
  stage: test
  script:
    - composer install
    - composer test:routes
  only:
    - merge_requests
    - main
```

## Best Practices

### DO:
✅ Test all public routes without authentication  
✅ Test authenticated routes with proper user context  
✅ Use factories to create required models for route parameters  
✅ Group tests by route type (public, auth, API)  
✅ Centralize route configuration in `RouteTestingConfig`  
✅ Exclude complex routes that require feature tests  
✅ Run route tests in parallel for speed  
✅ Validate route naming conventions and middleware  
✅ Monitor route coverage with `RouteCoverageTest`  
✅ Update tests when routes change  

### DON'T:
❌ Test form submission routes (use feature tests)  
❌ Test routes requiring complex state without setup  
❌ Include third-party package routes (Telescope, Horizon)  
❌ Test routes that intentionally return errors  
❌ Skip route testing because "it's too slow"  
❌ Hardcode route parameters instead of using factories  
❌ Forget to update tests when routes change  
❌ Test signed URL routes without proper signatures  

## Performance Optimization

### 1. Parallel Execution
```bash
pest --parallel tests/Feature/Routes
```

**Benefits:**
- 2-3x faster test execution
- Better CI pipeline performance
- Efficient use of CPU cores

### 2. Selective Testing
```php
// Test only changed routes in CI
routeTesting()
    ->only(['calendar.*', 'notes.print'])
    ->assertAllRoutesAreAccessible();
```

**Benefits:**
- Faster feedback loop
- Reduced CI time
- Focus on changed code

### 3. Database Optimization
```php
// Use RefreshDatabase trait
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);
```

**Benefits:**
- Clean database state
- No test pollution
- Consistent results

## Troubleshooting

### Route Not Found

**Problem:** Test fails with "Route not found" error

**Solution:**
```bash
# Check if route exists
php artisan route:list | grep route.name

# Clear route cache
php artisan route:clear
php artisan optimize:clear
```

### Authentication Issues

**Problem:** Authenticated route test fails

**Solution:**
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

**Problem:** Parametric route test fails

**Solution:**
```php
// Use explicit binding with proper tenant scoping
$user = User::factory()->create();
$note = Note::factory()->create([
    'team_id' => $user->currentTeam->id,
]);

routeTesting()
    ->actingAs($user)
    ->bind('note', $note)
    ->only(['notes.print'])
    ->assertAllRoutesAreAccessible();
```

### Middleware Conflicts

**Problem:** Route test fails due to middleware

**Solution:**
```php
// Skip middleware for testing (use sparingly)
routeTesting()
    ->withoutMiddleware()
    ->only(['admin.*'])
    ->assertAllRoutesAreAccessible();
```

## Maintenance

### After Route Changes

1. **Update RouteTestingConfig**
   - Add new routes to appropriate category
   - Add route bindings if parameters changed
   - Adjust middleware expectations

2. **Run Tests**
   ```bash
   composer test:routes
   ```

3. **Update Documentation**
   - Document new route patterns
   - Update examples if behavior changed

4. **Commit Changes**
   ```bash
   git add tests/Feature/Routes/
   git commit -m "test: update route tests for new routes"
   ```

### Monitoring Route Coverage

```bash
# List all routes
php artisan route:list

# Count routes
php artisan route:list --json | jq 'length'

# Find untested routes
php artisan route:list --json | jq '.[] | select(.name != null) | .name'
```

### Adding New Route Tests

1. **Identify Route Type**
   - Public, authenticated, API, guest, parametric?

2. **Choose Test File**
   - Add to existing file or create new one

3. **Write Test**
   ```php
   it('can access new route', function (): void {
       // Setup (user, models, etc.)
       
       routeTesting()
           ->actingAs($user)
           ->bind('model', $model)
           ->only(['new.route'])
           ->assertAllRoutesAreAccessible();
   });
   ```

4. **Run Test**
   ```bash
   pest tests/Feature/Routes/YourTestFile.php
   ```

5. **Update Config**
   - Add to `RouteTestingConfig` if needed

## Integration with Existing Patterns

### Works With:
✅ **Laravel Expectations Plugin** - HTTP assertions  
✅ **Filament v4 Resources** - Admin route testing  
✅ **Laravel Precognition** - API validation testing  
✅ **Sanctum Authentication** - API token testing  
✅ **Multi-Tenancy** - Tenant-scoped route testing  
✅ **Pest Parallel** - Fast test execution  

### Complements:
✅ **Feature Tests** - Complex user flows  
✅ **Unit Tests** - Business logic  
✅ **Browser Tests** - E2E testing  
✅ **API Tests** - Detailed API testing  

## Examples from This Project

### Public Routes
- `tests/Feature/Routes/PublicRoutesTest.php`
- Tests: home, terms, policy, security.txt

### Authenticated Routes
- `tests/Feature/Routes/AuthenticatedRoutesTest.php`
- Tests: dashboard, calendar, notes.print

### API Routes
- `tests/Feature/Routes/ApiRoutesTest.php`
- Tests: contacts.index, contacts.show

### Calendar Routes
- `tests/Feature/Routes/CalendarRoutesTest.php`
- Tests: calendar, calendar.export.ical

### Filament Routes
- `tests/Feature/Routes/FilamentRoutesTest.php`
- Tests: Filament admin panel routes

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

## Related Documentation

- `docs/pest-route-testing-integration.md` - Original integration guide
- `.kiro/steering/pest-route-testing.md` - Steering rules
- `.kiro/steering/testing-standards.md` - Testing conventions
- `.kiro/steering/filament-testing.md` - Filament route testing
- `docs/laravel-precognition.md` - API validation testing

## Package Information

- **Package**: `spatie/pest-plugin-route-testing`
- **Version**: 1.1.4
- **Repository**: https://github.com/spatie/pest-plugin-route-testing
- **Documentation**: https://github.com/spatie/pest-plugin-route-testing#readme
- **License**: MIT

## Support

For issues or questions:
1. Check this documentation
2. Review test examples in `tests/Feature/Routes/`
3. Check package documentation
4. Open an issue on GitHub

## Changelog

### 2025-12-08
- ✅ Package installed and configured
- ✅ Test suite created with 8 test files
- ✅ RouteTestingConfig centralized configuration
- ✅ Composer scripts added
- ✅ CI integration completed
- ✅ Documentation created
- ✅ Steering rules updated

### Future Enhancements
- [ ] Add more API route tests
- [ ] Add Filament resource route tests
- [ ] Add webhook route tests
- [ ] Add rate limiting tests
- [ ] Add CORS tests
