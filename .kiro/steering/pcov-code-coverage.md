# PCOV Code Coverage Integration

## Core Principles
- PCOV provides 10-30x faster code coverage than Xdebug with minimal memory overhead
- All coverage analysis uses PCOV extension for performance
- Coverage reports are generated in HTML, XML (Clover), and text formats
- Minimum coverage threshold is 80% for production code
- Type coverage threshold is 99.9% (enforced via Pest)

## Service Usage
- Use `CodeCoverageService` (singleton in container) for programmatic coverage access
- Inject via constructor: `public function __construct(private readonly CodeCoverageService $coverageService) {}`
- All methods return cached results (5-minute TTL by default)
- Call `$coverageService->clearCache()` after running new coverage analysis

## Filament v4.3+ Integration
- Coverage Dashboard Widget at `app/Filament/Widgets/System/CodeCoverageWidget.php`
- Coverage Management Page at `app/Filament/Pages/System/CodeCoverage.php`
- Access control via `view_code_coverage` permission
- Real-time coverage statistics with trend indicators
- Quick actions for running coverage and viewing reports

## Running Coverage

### Composer Scripts
```bash
# Run tests with coverage (80% minimum)
composer test:coverage

# Run type coverage check (99.9% minimum)
composer test:type-coverage

# Run full test suite (includes coverage)
composer test
```

### Direct Pest Commands
```bash
# Basic coverage
pest --coverage --min=80

# HTML report
pest --coverage-html=coverage-html

# XML report (for CI)
pest --coverage-clover=coverage.xml

# Text output
pest --coverage-text
```

## Configuration

### Environment Variables (.env)
```env
PCOV_ENABLED=true
PCOV_DIRECTORY=.
PCOV_EXCLUDE="~vendor~"
COVERAGE_MIN_PERCENTAGE=80
COVERAGE_MIN_TYPE_COVERAGE=99.9
```

### PHPUnit Configuration
Coverage is configured in both `phpunit.xml` (local) and `phpunit.ci.xml` (CI):
- HTML output: `coverage-html/`
- Clover XML: `coverage.xml`
- Text output to stdout

### Testing Config
Configuration in `config/testing.php`:
- `coverage.html_dir` - HTML report directory
- `coverage.clover_file` - Clover XML file path
- `coverage.cache_ttl` - Cache duration for stats
- `coverage.min_percentage` - Minimum coverage threshold
- `coverage.min_type_coverage` - Minimum type coverage

## Coverage Metrics

### Line Coverage
Percentage of executable lines executed during tests.
**Target**: 80%+ for production code

### Method Coverage
Percentage of methods called during tests.
**Target**: 90%+ for public APIs

### Class Coverage
Percentage of classes instantiated during tests.
**Target**: 80%+ for application classes

### Type Coverage
Percentage of code with proper type declarations (Pest-specific).
**Target**: 99.9% (strictly enforced)

## Excluding Code from Coverage

### Via PHPUnit Configuration
```xml
<source>
    <exclude>
        <directory>app/Console/Kernel.php</directory>
        <directory>app/Exceptions/Handler.php</directory>
        <directory>app/Http/Middleware</directory>
    </exclude>
</source>
```

### Via Annotations
```php
/**
 * @codeCoverageIgnore
 */
class DeprecatedClass {}

/**
 * @codeCoverageIgnoreStart
 */
function legacyCode() {}
/**
 * @codeCoverageIgnoreEnd
 */
```

## CI/CD Integration

### GitHub Actions
```yaml
- name: Setup PHP with PCOV
  uses: shivammathur/setup-php@v2
  with:
    php-version: '8.4'
    extensions: pcov
    coverage: pcov

- name: Run Tests with Coverage
  run: composer test:coverage
```

### Coverage Reports
- HTML reports stored in `coverage-html/`
- Clover XML for CI tools (Codecov, Coveralls)
- Text output for quick terminal review

## Performance Optimization

### Memory Management
```bash
# Increase memory for large projects
php -d memory_limit=1G vendor/bin/pest --coverage
```

### Parallel Testing
```bash
# Run tests in parallel (coverage merged automatically)
pest --parallel --coverage
```

### Selective Coverage
```bash
# Run coverage for specific suite
pest --testsuite=Feature --coverage

# Run coverage for specific directory
pest tests/Unit --coverage
```

## Filament Widget Features

### Stats Display
- Overall coverage percentage with color coding
- Method coverage metrics
- Class coverage metrics
- 7-day coverage trend chart

### Actions
- Run Coverage: Execute full coverage analysis
- View Report: Navigate to detailed coverage page
- Refresh: Clear cache and reload stats

### Color Coding
- Green (success): ≥80% coverage
- Orange (warning): 60-79% coverage
- Red (danger): <60% coverage

## Coverage Management Page

### Features
- PCOV status and configuration display
- Detailed coverage statistics
- Coverage by category (Models, Services, Controllers, Resources)
- Quick action commands
- Download HTML/XML reports
- Run full coverage analysis
- Clear coverage cache

### Access Control
Requires `view_code_coverage` permission (typically admin/developer role)

## Best Practices

### DO:
- ✅ Run coverage locally before pushing
- ✅ Aim for 80%+ overall coverage
- ✅ Focus on critical business logic
- ✅ Exclude generated/boilerplate code
- ✅ Use coverage to find untested code
- ✅ Monitor coverage trends over time
- ✅ Set realistic coverage thresholds
- ✅ Review coverage reports regularly

### DON'T:
- ❌ Aim for 100% coverage blindly
- ❌ Test getters/setters just for coverage
- ❌ Skip edge cases to maintain coverage
- ❌ Ignore coverage drops in PRs
- ❌ Include vendor code in coverage
- ❌ Run coverage on every test run (slow)
- ❌ Forget to exclude test files

## Troubleshooting

### PCOV Not Found
```bash
# Check if installed
php -m | grep pcov

# Install via PECL
pecl install pcov

# Enable in php.ini
echo "extension=pcov.so" >> /path/to/php.ini
```

### Coverage Report Empty
```bash
# Verify PCOV is enabled
php --ri pcov

# Check directory setting
php -i | grep pcov.directory

# Verify source paths in phpunit.xml
```

### Memory Limit Errors
```bash
# Increase memory limit
php -d memory_limit=1G vendor/bin/pest --coverage

# Or update php.ini
memory_limit = 1G
```

## Integration with Existing Tools

### Works With
- **Pest**: Primary test framework with coverage support
- **PHPStan**: Static analysis (separate from coverage)
- **Rector**: Code refactoring (run before coverage)
- **Pint**: Code formatting (run before coverage)
- **Filament**: UI for coverage visualization

### Workflow
1. Run `composer lint` (Rector + Pint)
2. Run `composer test:types` (PHPStan)
3. Run `composer test:coverage` (Pest with PCOV)
4. Review coverage in Filament UI

## Related Documentation
- `docs/pcov-code-coverage-integration.md` - Complete integration guide
- `docs/testing-infrastructure.md` - Testing setup and patterns
- `.kiro/steering/testing-standards.md` - Testing conventions
- `.kiro/steering/pest-route-testing.md` - Route testing patterns

## Package Information
- **Extension**: PCOV (PHP Extension)
- **Service**: `App\Services\Testing\CodeCoverageService`
- **Widget**: `App\Filament\Widgets\System\CodeCoverageWidget`
- **Page**: `App\Filament/Pages/System/CodeCoverage`
- **Config**: `config/testing.php`
- **Composer Scripts**: `test:coverage`, `test:type-coverage`

## Quick Reference

### Installation
```bash
pecl install pcov
php -m | grep pcov
```

### Run Coverage
```bash
composer test:coverage
pest --coverage --min=80
```

### View Reports
```bash
open coverage-html/index.html
```

### Filament Access
Navigate to: **System → Code Coverage**
