# Test Profiling & Performance Optimization

> **📚 Comprehensive Guide**: See `docs/test-profiling.md` for detailed optimization strategies, troubleshooting, and CI/CD integration.

## Core Principles
- Use `composer test:pest:profile` or `php artisan test --profile` to identify slow-running tests.
- Profile tests regularly during development to catch performance regressions early.
- Target < 100ms for unit tests, < 500ms for feature tests, < 2s for integration tests.
- Mock external services (HTTP, mail, notifications) to avoid network latency.
- Use database transactions and minimal test data for faster execution.

## When to Profile

### During Development
- After adding new feature tests
- When test suite execution time increases noticeably
- Before submitting pull requests with new tests
- When refactoring existing tests

### In CI/CD
- On pull requests to catch performance regressions
- Weekly scheduled profiling to track trends
- Before major releases to ensure test suite health

## Common Performance Issues

### Database Operations
```php
// ❌ SLOW - Creates too many records
it('processes companies', function () {
    $companies = Company::factory()->count(100)->create();
    // Test logic
});

// ✅ FAST - Uses minimal representative data
it('processes companies', function () {
    $companies = Company::factory()->count(3)->create();
    // Test logic with sample
});
```

### External API Calls
```php
// ❌ SLOW - Real HTTP request
it('fetches data from api', function () {
    $response = Http::get('https://api.example.com/data');
    // ...
});

// ✅ FAST - Mocked response
it('fetches data from api', function () {
    Http::fake([
        'api.example.com/*' => Http::response(['data' => 'test'], 200),
    ]);
    // ...
});
```

### File Operations
```php
// ❌ SLOW - Large file processing
it('imports csv', function () {
    Storage::fake('local');
    $file = UploadedFile::fake()->create('large.csv', 10000); // 10MB
    // ...
});

// ✅ FAST - Small test fixture
it('imports csv', function () {
    Storage::fake('local');
    $file = UploadedFile::fake()->create('test.csv', 10); // 10KB
    // ...
});
```

### N+1 Query Problems
```php
// ❌ SLOW - N+1 queries
it('displays companies with users', function () {
    $companies = Company::factory()->count(10)->create();
    $companies->each(fn ($c) => $c->users); // N+1!
});

// ✅ FAST - Eager loading
it('displays companies with users', function () {
    $companies = Company::factory()->count(10)->create();
    $companies->load('users'); // Single query
});
```

## Optimization Strategies

### 1. Mock External Services
Always mock HTTP, mail, notifications, and queues:

```php
beforeEach(function () {
    Http::fake();
    Mail::fake();
    Notification::fake();
    Queue::fake();
});
```

### 2. Use Database Transactions
Leverage `RefreshDatabase` trait for automatic rollback:

```php
uses(RefreshDatabase::class);

it('creates record', function () {
    $company = Company::factory()->create();
    expect($company)->toExist();
    // Automatically rolled back
});
```

### 3. Cache Expensive Setup
Cache data that's reused across tests:

```php
beforeEach(function () {
    $this->countries = Cache::remember('test.countries', 3600, function () {
        return Country::all();
    });
});
```

### 4. Use Minimal Factories
Create only what's needed for the test:

```php
// ✅ Minimal factory
$user = User::factory()->create(['email' => 'test@example.com']);

// ❌ Over-specified factory
$user = User::factory()
    ->has(Company::factory()->count(10))
    ->has(Task::factory()->count(50))
    ->has(Note::factory()->count(100))
    ->create();
```

### 5. Avoid Sleep/Wait
Never use `sleep()` or `wait()` in tests:

```php
// ❌ SLOW - Artificial delay
it('processes async task', function () {
    dispatch(new ProcessTask());
    sleep(2); // Don't do this!
    // ...
});

// ✅ FAST - Synchronous testing
it('processes async task', function () {
    Queue::fake();
    dispatch(new ProcessTask());
    Queue::assertPushed(ProcessTask::class);
});
```

## Profiling Workflow

### 1. Baseline Profile
```bash
composer test:pest:profile > baseline.txt
```

### 2. Identify Slow Tests
Review the output and identify tests > 500ms:
```
Top 10 Slowest Tests (5.23s)
  2.45s  Tests\Feature\Calendar\CalendarSyncTest > can sync calendar events
  1.89s  Tests\Feature\Export\CompanyExporterTest > can export companies
  0.95s  Tests\Feature\Services\World\WorldDataServiceTest > calculates distance
```

### 3. Optimize Identified Tests
Apply optimization strategies to slow tests.

### 4. Re-Profile
```bash
composer test:pest:profile > optimized.txt
diff baseline.txt optimized.txt
```

### 5. Verify Improvements
Ensure tests still pass and are faster:
```bash
composer test:pest
```

## Integration with Existing Tools

### Works With
- ✅ Pest parallel execution (`composer test:pest`)
- ✅ Code coverage (`composer test:coverage`)
- ✅ Type coverage (`composer test:type-coverage`)
- ✅ Route testing (`composer test:routes`)
- ✅ Stress testing (Stressless with `RUN_STRESS_TESTS=1`)

### Recommended Workflow
1. Run full test suite: `composer test`
2. Profile to find slow tests: `composer test:pest:profile`
3. Optimize identified tests
4. Run coverage to ensure no regressions: `composer test:coverage`
5. Commit optimizations

## Performance Targets

### Test Type Thresholds
- **Unit Tests**: < 100ms per test
- **Feature Tests**: < 500ms per test
- **Integration Tests**: < 2s per test
- **E2E Tests (Playwright)**: < 5s per test

### Suite Targets
- **Total Suite Time**: < 5 minutes
- **CI Pipeline Time**: < 10 minutes
- **Coverage Generation**: < 2 minutes

### When to Optimize
- Any test > 1s should be reviewed
- Any test > 5s should be refactored immediately
- Suite time increasing > 20% needs investigation

## Best Practices

### DO:
- ✅ Profile tests regularly during development
- ✅ Mock all external services (HTTP, mail, notifications)
- ✅ Use database transactions for cleanup
- ✅ Create minimal test data
- ✅ Use factories efficiently
- ✅ Cache expensive setup operations
- ✅ Run profiling before major refactors
- ✅ Track test performance trends over time
- ✅ Document optimization decisions

### DON'T:
- ❌ Ignore slow tests
- ❌ Make real API calls in tests
- ❌ Create unnecessary database records
- ❌ Skip database transactions
- ❌ Use `sleep()` or `wait()` unnecessarily
- ❌ Load entire datasets when samples suffice
- ❌ Forget to re-profile after optimizations
- ❌ Optimize without measuring first

## Troubleshooting

### Test Appears Slow But Isn't
Isolate the test to exclude setup/teardown time:
```bash
php artisan test --profile --filter="specific test name"
```

### Inconsistent Timing
Run multiple times to get average:
```bash
for i in {1..5}; do php artisan test --profile; done
```

### Database Bottlenecks
Enable query logging to find N+1 issues:
```php
beforeEach(function () {
    DB::enableQueryLog();
});

afterEach(function () {
    $queries = DB::getQueryLog();
    if (count($queries) > 10) {
        dump("Warning: " . count($queries) . " queries executed");
    }
});
```

### Memory Issues
Increase memory limit for profiling:
```bash
php -d memory_limit=2G artisan test --profile
```

## CI/CD Integration

### GitHub Actions Example
```yaml
- name: Profile Tests
  run: composer test:pest:profile
  if: github.event_name == 'pull_request'

- name: Check for Slow Tests
  run: |
    php artisan test --profile | grep -E '[0-9]+\.[0-9]+s' | awk '$1 > 1.0 {exit 1}'
```

### Save Profile Results
```bash
php artisan test --profile > test-profile-$(date +%Y%m%d).txt
```

## Related Documentation
- `docs/test-profiling.md` - Complete profiling guide with examples
- `docs/testing-infrastructure.md` - Testing setup and patterns
- `docs/pcov-code-coverage-integration.md` - Code coverage with PCOV
- `docs/pest-route-testing-complete-guide.md` - Route testing patterns
- `.kiro/steering/testing-standards.md` - Testing conventions
- `.kiro/steering/filament-testing.md` - Filament testing patterns

## Quick Commands

```bash
# Profile all tests
composer test:pest:profile

# Profile specific suite
php artisan test --profile --testsuite=Feature

# Profile specific directory
php artisan test --profile tests/Feature/Routes

# Profile with filter
php artisan test --profile --filter=CompanyTest

# Profile with coverage
php artisan test --profile --coverage

# Save output
php artisan test --profile > profile.txt
```
