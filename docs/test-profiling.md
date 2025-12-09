# Test Profiling Guide

> **Quick Reference**: This is the comprehensive profiling guide. For concise rules, see `.kiro/steering/test-profiling.md` and `.kiro/steering/testing-standards.md`.

## Overview
Laravel's `--profile` option helps identify slow-running tests in your test suite, enabling you to optimize test performance and reduce CI/CD pipeline times.

## Quick Start

### Profile All Tests
```bash
composer test:pest:profile
```

Or directly with Pest:
```bash
php artisan test --profile
```

### Profile Specific Test Suite
```bash
php artisan test --profile --testsuite=Feature
php artisan test --profile --testsuite=Unit
```

### Profile Specific Directory
```bash
php artisan test --profile tests/Feature/Routes
php artisan test --profile tests/Unit/Services
```

## Understanding the Output

The `--profile` option displays the 10 slowest tests by default:

```
PASS  Tests\Feature\Calendar\CalendarSyncTest
✓ can sync calendar events                                    2.45s

PASS  Tests\Feature\Export\CompanyExporterTest
✓ can export companies to excel                               1.89s

PASS  Tests\Feature\Services\World\WorldDataServiceTest
✓ can calculate distance between cities                       0.95s

...

Top 10 Slowest Tests (2.45s)
  2.45s  Tests\Feature\Calendar\CalendarSyncTest > can sync calendar events
  1.89s  Tests\Feature\Export\CompanyExporterTest > can export companies to excel
  0.95s  Tests\Feature\Services\World\WorldDataServiceTest > can calculate distance between cities
```

## Common Slow Test Patterns

### Database-Heavy Tests
Tests that create many records or perform complex queries:

```php
// Slow - Creates 100 records individually
it('processes many companies', function () {
    $companies = Company::factory()->count(100)->create();
    // ...
});

// Faster - Use database transactions and minimal data
it('processes many companies', function () {
    $companies = Company::factory()->count(10)->create();
    // Test with representative sample
});
```

### External API Calls
Tests that make real HTTP requests:

```php
// Slow - Real API call
it('fetches github stars', function () {
    $service = app(GitHubService::class);
    $stars = $service->getStarsCount();
    // ...
});

// Faster - Mock HTTP responses
it('fetches github stars', function () {
    Http::fake([
        'api.github.com/*' => Http::response(['stargazers_count' => 100], 200),
    ]);
    
    $service = app(GitHubService::class);
    $stars = $service->getStarsCount();
    // ...
});
```

### File Operations
Tests that read/write large files:

```php
// Slow - Large file operations
it('processes large csv', function () {
    Storage::fake('local');
    $csv = Storage::put('test.csv', $largeContent);
    // ...
});

// Faster - Use smaller test fixtures
it('processes csv', function () {
    Storage::fake('local');
    $csv = Storage::put('test.csv', $smallSampleContent);
    // ...
});
```

## Optimization Strategies

### 1. Use Database Transactions
```php
uses(RefreshDatabase::class);

// Automatically rolls back after each test
it('creates company', function () {
    $company = Company::factory()->create();
    expect($company)->toExist();
});
```

### 2. Mock External Services
```php
it('sends notification', function () {
    Notification::fake();
    
    $user->notify(new WelcomeNotification());
    
    Notification::assertSentTo($user, WelcomeNotification::class);
});
```

### 3. Use Minimal Test Data
```php
// Only create what you need
$user = User::factory()->create(['email' => 'test@example.com']);

// Instead of creating full related data
$user = User::factory()
    ->has(Company::factory()->count(10))
    ->has(Task::factory()->count(50))
    ->create();
```

### 4. Cache Expensive Operations
```php
// In setUp or beforeEach
beforeEach(function () {
    $this->countries = Cache::remember('test.countries', 3600, function () {
        return Country::all();
    });
});
```

### 5. Use Parallel Testing
```bash
# Run tests in parallel (faster overall, but won't show individual timings)
composer test:pest

# Profile to find slow tests, then optimize them
composer test:pest:profile
```

## Integration with CI/CD

### GitHub Actions Example
```yaml
- name: Profile Tests
  run: composer test:pest:profile
  if: github.event_name == 'pull_request'

- name: Upload Profile Results
  uses: actions/upload-artifact@v3
  with:
    name: test-profile
    path: test-profile.txt
```

### Save Profile Output
```bash
php artisan test --profile > test-profile.txt
```

## Performance Targets

### Recommended Thresholds
- **Unit Tests**: < 100ms per test
- **Feature Tests**: < 500ms per test
- **Integration Tests**: < 2s per test
- **E2E Tests**: < 5s per test

### When to Optimize
- Tests taking > 1s should be reviewed
- Tests taking > 5s should be refactored
- Total suite time > 5 minutes needs optimization

## Best Practices

### DO:
- ✅ Profile tests regularly during development
- ✅ Mock external services (HTTP, mail, notifications)
- ✅ Use database transactions for cleanup
- ✅ Create minimal test data
- ✅ Use factories efficiently
- ✅ Cache expensive setup operations
- ✅ Run profiling before major refactors

### DON'T:
- ❌ Ignore slow tests
- ❌ Make real API calls in tests
- ❌ Create unnecessary database records
- ❌ Skip database transactions
- ❌ Use sleep() or wait() unnecessarily
- ❌ Load entire datasets when samples suffice

## Troubleshooting

### Test Appears Slow But Isn't
Sometimes setup/teardown time is included. Isolate the test:

```bash
php artisan test --profile --filter="specific test name"
```

### Inconsistent Timing
Run multiple times to get average:

```bash
for i in {1..5}; do php artisan test --profile; done
```

### Database Bottlenecks
Check for missing indexes or N+1 queries:

```php
use Illuminate\Support\Facades\DB;

beforeEach(function () {
    DB::enableQueryLog();
});

afterEach(function () {
    $queries = DB::getQueryLog();
    if (count($queries) > 10) {
        dump("Warning: {count($queries)} queries executed");
    }
});
```

## Related Commands

```bash
# Profile with coverage
php artisan test --profile --coverage

# Profile specific test
php artisan test --profile --filter=CompanyTest

# Profile with parallel execution (shows aggregate time)
php artisan test --profile --parallel

# Profile with type coverage
php artisan test --profile --type-coverage
```

## Integration with Existing Tools

### Works With
- ✅ Pest parallel execution
- ✅ Code coverage (PCOV)
- ✅ Type coverage
- ✅ Route testing
- ✅ Stress testing (Stressless)

### Workflow
1. Run full test suite: `composer test`
2. Profile to find slow tests: `composer test:pest:profile`
3. Optimize identified tests
4. Re-profile to verify improvements
5. Commit optimizations

## Example Optimization Session

```bash
# 1. Profile current state
composer test:pest:profile > before.txt

# 2. Identify slowest test (e.g., CalendarSyncTest)
# 3. Optimize the test (mock HTTP, reduce data)
# 4. Profile again
composer test:pest:profile > after.txt

# 5. Compare results
diff before.txt after.txt
```

## Related Documentation
- `docs/testing-infrastructure.md` - Testing setup and patterns
- `docs/pcov-code-coverage-integration.md` - Code coverage with PCOV
- `docs/pest-route-testing-complete-guide.md` - Route testing patterns
- `.kiro/steering/testing-standards.md` - Testing conventions
- `.kiro/steering/filament-testing.md` - Filament testing patterns

## Quick Reference

### Commands
```bash
# Profile all tests
composer test:pest:profile

# Profile specific suite
php artisan test --profile --testsuite=Feature

# Profile specific directory
php artisan test --profile tests/Feature/Routes

# Profile with filter
php artisan test --profile --filter=CompanyTest

# Save output
php artisan test --profile > profile.txt
```

### Common Optimizations
1. Mock HTTP calls with `Http::fake()`
2. Mock notifications with `Notification::fake()`
3. Mock mail with `Mail::fake()`
4. Use `RefreshDatabase` trait
5. Create minimal test data
6. Cache expensive setup
7. Use database transactions
8. Avoid `sleep()` calls
9. Mock external services
10. Use representative samples instead of full datasets
