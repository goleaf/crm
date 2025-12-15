# Route Testing Quick Reference

> Covers public, API, and Filament v4.3+ admin routes using the route-testing plugin patterns.

## 🚀 Quick Start

```bash
# Run all route tests
composer test:routes

# Run specific test file
pest tests/Feature/Routes/PublicRoutesTest.php

# Get help
kiro run route-test-help
```

## 📝 Common Patterns

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

### API Route
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

## 🔧 Adding New Routes

### 1. Update Config
```php
// tests/Feature/Routes/RouteTestingConfig.php

public static function authenticatedRoutes(): array
{
    return [
        'dashboard',
        'your.new.route', // Add here
    ];
}
```

### 2. Create Test
```php
// tests/Feature/Routes/YourTestFile.php

it('can access your new route', function (): void {
    $user = User::factory()->create();
    
    routeTesting()
        ->actingAs($user)
        ->only(['your.new.route'])
        ->assertAllRoutesAreAccessible();
});
```

### 3. Run Tests
```bash
composer test:routes
```

## 🐛 Troubleshooting

### Route Not Found
```bash
php artisan route:list | grep route.name
php artisan route:clear
php artisan optimize:clear
```

### Authentication Issues
```php
$user = User::factory()->create();
$user->givePermissionTo('view_dashboard');

routeTesting()
    ->actingAs($user)
    ->only(['dashboard'])
    ->assertAllRoutesAreAccessible();
```

### Tenant Scoping
```php
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

## 📚 Documentation

- **Complete Guide**: `docs/pest-route-testing-complete-guide.md`
- **Test Suite**: `tests/Feature/Routes/README.md`
- **Steering Rules**: `.kiro/steering/pest-route-testing.md`
- **Ecosystem**: `docs/testing-ecosystem-overview.md`

## 🎯 Route Categories

| Category | Config Method | Example |
|----------|--------------|---------|
| Public | `publicRoutes()` | home, terms |
| Authenticated | `authenticatedRoutes()` | dashboard, calendar |
| API | `apiRoutes()` | contacts.index |
| Guest | `guestRoutes()` | login, register |
| Parametric | `parametricRoutes()` | notes.print |
| Redirect | `redirectRoutes()` | dashboard |
| Signed | `signedRoutes()` | verification.verify |

## ⚡ Commands

```bash
# Run all route tests
composer test:routes

# Run with parallel execution
pest tests/Feature/Routes --parallel

# Run with coverage
pest tests/Feature/Routes --coverage

# Run specific test
pest tests/Feature/Routes/PublicRoutesTest.php

# Run single test
pest tests/Feature/Routes/PublicRoutesTest.php --filter="can access home page"

# Get troubleshooting help
kiro run route-test-help

# List all routes
php artisan route:list

# Clear caches
php artisan optimize:clear
```

## ✅ Checklist for New Routes

- [ ] Identify route type (public, auth, API, etc.)
- [ ] Add to `RouteTestingConfig.php`
- [ ] Create test in appropriate file
- [ ] Use factories for test data
- [ ] Handle authentication if needed
- [ ] Bind parameters if needed
- [ ] Run `composer test:routes`
- [ ] Verify all tests pass
- [ ] Update documentation if unique pattern

## 🎨 Best Practices

### DO:
✅ Test all public routes  
✅ Use factories for models  
✅ Group tests by type  
✅ Update config when adding routes  
✅ Run tests before committing  

### DON'T:
❌ Test form submissions  
❌ Test complex flows  
❌ Include third-party routes  
❌ Hardcode parameters  
❌ Skip authentication  

## 🔗 Quick Links

- **Package**: https://github.com/spatie/pest-plugin-route-testing
- **Pest Docs**: https://pestphp.com
- **Laravel Testing**: https://laravel.com/docs/testing

## 💡 Tips

1. **Automatic Testing**: Tests run automatically when route files change
2. **Parallel Execution**: Use `--parallel` for faster tests
3. **Coverage**: Route tests included in PCOV coverage
4. **CI/CD**: Integrated into `composer test` and `composer test:ci`
5. **Troubleshooting**: Run `kiro run route-test-help` for guidance

---

**Quick Reference Version**: 1.0  
**Last Updated**: December 8, 2025  
**Package Version**: 1.1.4
