# Pest Route Testing Plugin

## Core Principles
- Use `spatie/pest-plugin-route-testing` to ensure all routes remain accessible and properly configured.
- Test routes in isolation by type: public, authenticated, API, guest-only, and parametric routes.
- Centralize route testing configuration in `Tests\Feature\Routes\RouteTestingConfig` for maintainability.
- Exclude complex routes (forms, wizards, third-party packages) from automated testing; cover them with feature tests instead.

## Route Categories

### Public Routes
- Accessible without authentication: home, terms, privacy policy, security.txt
- Test with `routeTesting()->only(['home', 'terms.show'])->assertAllRoutesAreAccessible()`
- Verify external redirects (Discord, social links) with `->assertAllRoutesRedirect()`

### Authenticated Routes
- Require `auth` middleware: dashboard, calendar, notes, purchase orders
- Test with `routeTesting()->actingAs($user)->only(['dashboard'])->assertAllRoutesAreAccessible()`
- Verify unauthenticated access redirects to login

### API Routes
- Require Sanctum token authentication: contacts CRUD endpoints
- Test with `routeTesting()->withToken($token)->only(['contacts.index'])->assertAllRoutesAreAccessible()`
- Verify JSON responses and proper HTTP status codes
- Test Precognition support with `Precognition: true` header

### Guest Routes
- Accessible only when not authenticated: login, register, password reset
- Test with `routeTesting()->only(['login'])->assertAllRoutesRedirect()` (redirects to app URL)
- Verify authenticated users are redirected away

### Parametric Routes
- Require route parameters: notes.print, contacts.show, auth.socialite.redirect
- Test with `routeTesting()->bind('note', $note)->only(['notes.print'])->assertAllRoutesAreAccessible()`
- Use factories to create required models

## Configuration Patterns

### Excluded Routes
```php
// RouteTestingConfig::excludedRoutes()
[
    'telescope.*',      // Third-party packages
    'horizon.*',
    '*.store',          // Form submissions (test separately)
    '*.update',
    '*.destroy',
    'filament.*.create', // Complex Filament forms
    'filament.*.edit',
]
```

### Route Bindings
```php
// RouteTestingConfig::parametricRoutes()
[
    'notes.print' => ['note'],
    'contacts.show' => ['contact'],
    'auth.socialite.redirect' => ['provider'],
]
```

## Testing Patterns

### Basic Route Test
```php
it('can access home page', function (): void {
    routeTesting()
        ->only(['home'])
        ->assertAllRoutesAreAccessible();
});
```

### Authenticated Route Test
```php
it('can access calendar when authenticated', function (): void {
    $user = User::factory()->create();
    
    routeTesting()
        ->actingAs($user)
        ->only(['calendar'])
        ->assertAllRoutesAreAccessible();
});
```

### API Route Test
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

### Parametric Route Test
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

### Redirect Test
```php
it('redirects dashboard to app URL', function (): void {
    routeTesting()
        ->only(['dashboard'])
        ->assertAllRoutesRedirect();
});
```

## File Organization

### Test Structure
```
tests/Feature/Routes/
├── PublicRoutesTest.php           # Public routes (home, terms, policy)
├── AuthRoutesTest.php             # Authentication routes (login, register)
├── AuthenticatedRoutesTest.php    # Protected routes (dashboard, calendar)
├── ApiRoutesTest.php              # API routes (contacts, resources)
├── CalendarRoutesTest.php         # Calendar-specific routes
├── RouteCoverageTest.php          # Coverage validation
├── AllRoutesTest.php              # Comprehensive route tests
└── RouteTestingConfig.php         # Centralized configuration
```

## Integration with CI/CD

### Composer Script
```json
{
  "scripts": {
    "test:routes": "pest --filter=routes --parallel"
  }
}
```

### GitHub Actions
```yaml
- name: Run Route Tests
  run: composer test:routes
```

## Best Practices

### DO:
- ✅ Test all public routes without authentication
- ✅ Test authenticated routes with proper user context
- ✅ Use factories to create required models for route parameters
- ✅ Group tests by route type (public, auth, API)
- ✅ Centralize route configuration in `RouteTestingConfig`
- ✅ Exclude complex routes that require feature tests
- ✅ Run route tests in parallel for speed
- ✅ Validate route naming conventions and middleware
- ✅ Monitor route coverage with `RouteCoverageTest`

### DON'T:
- ❌ Test form submission routes (use feature tests)
- ❌ Test routes requiring complex state without setup
- ❌ Include third-party package routes (Telescope, Horizon)
- ❌ Test routes that intentionally return errors
- ❌ Skip route testing because "it's too slow"
- ❌ Hardcode route parameters instead of using factories
- ❌ Forget to update tests when routes change
- ❌ Test signed URL routes without proper signatures

## Performance

### Parallel Execution
```bash
pest --parallel tests/Feature/Routes
```

### Selective Testing
```php
// Test only changed routes in CI
routeTesting()
    ->only(['calendar.*', 'notes.print'])
    ->assertAllRoutesAreAccessible();
```

## Maintenance

### After Route Changes
1. Update `RouteTestingConfig` with new routes
2. Add route bindings if parameters changed
3. Adjust middleware expectations
4. Run `composer test:routes` to verify
5. Update documentation if behavior changed

### Monitoring Coverage
```bash
# List all routes
php artisan route:list

# Find untested routes
php artisan route:list --json | jq '.[] | select(.name != null) | .name'
```

## Related Documentation
- `docs/pest-route-testing-integration.md` - Comprehensive guide
- `docs/laravel-precognition.md` - API validation testing
- `.kiro/steering/testing-standards.md` - Testing conventions
- `.kiro/steering/filament-testing.md` - Filament route testing

## Integration Points
- Works with `defstudio/pest-plugin-laravel-expectations` for HTTP assertions
- Complements Filament resource testing
- Validates Precognition-enabled routes
- Integrates with Sanctum authentication testing
- Runs alongside feature and unit tests
