# PCOV Code Coverage Integration

## Overview
PCOV is a self-contained PHP extension for code coverage that provides significantly better performance compared to Xdebug. This document covers the complete integration of PCOV into the Relaticle CRM application.

## Why PCOV?

### Performance Benefits
- **10-30x faster** than Xdebug for code coverage
- Minimal memory overhead
- No impact on non-coverage test runs
- Designed specifically for code coverage (not debugging)

### Comparison with Xdebug
| Feature | PCOV | Xdebug |
|---------|------|--------|
| Coverage Speed | ⚡ Very Fast | 🐌 Slow |
| Memory Usage | Low | High |
| Setup Complexity | Simple | Complex |
| Debugging Support | ❌ No | ✅ Yes |
| Production Safe | ✅ Yes | ⚠️ Conditional |

## Installation

### macOS (Homebrew/Herd)
```bash
# Install PCOV via PECL
pecl install pcov

# Or via Homebrew (if using Homebrew PHP)
brew install pcov
```

### Linux (Ubuntu/Debian)
```bash
# Install PCOV
sudo apt-get install php8.4-pcov

# Or via PECL
sudo pecl install pcov
```

### Docker
```dockerfile
# In your Dockerfile
RUN pecl install pcov && docker-php-ext-enable pcov
```

### Verify Installation
```bash
php -m | grep pcov
# Should output: pcov

php --ri pcov
# Should show PCOV extension info
```

## Configuration

### PHP Configuration (php.ini)
```ini
; Enable PCOV extension
extension=pcov.so

; PCOV Configuration
pcov.enabled = 1
pcov.directory = /path/to/your/project
pcov.exclude = "~vendor~"

; Memory limit for coverage (increase if needed)
memory_limit = 512M
```

### Environment Variables (.env)
```env
# PCOV Configuration
PCOV_ENABLED=true
PCOV_DIRECTORY=.
PCOV_EXCLUDE="~vendor~"

# Coverage Thresholds
COVERAGE_MIN_PERCENTAGE=80
COVERAGE_MIN_TYPE_COVERAGE=99.9
```

### PHPUnit Configuration
The project uses two PHPUnit configurations:

#### phpunit.xml (Local Development)
```xml
<coverage>
    <report>
        <html outputDirectory="coverage-html" lowUpperBound="50" highLowerBound="80"/>
        <clover outputFile="coverage.xml"/>
        <text outputFile="php://stdout" showUncoveredFiles="true"/>
    </report>
</coverage>
```

#### phpunit.ci.xml (CI/CD)
Already configured with coverage reporting for PostgreSQL-based CI environments.

## Usage

### Running Coverage with PCOV

#### Basic Coverage Report
```bash
# Run tests with coverage
composer test:coverage

# Or directly with Pest
pest --coverage --min=80
```

#### HTML Coverage Report
```bash
# Generate HTML report
pest --coverage-html=coverage-html

# Open in browser
open coverage-html/index.html
```

#### Clover XML Report (for CI)
```bash
# Generate Clover XML
pest --coverage-clover=coverage.xml
```

#### Text Coverage Report
```bash
# Show coverage in terminal
pest --coverage-text
```

#### Coverage with Minimum Threshold
```bash
# Fail if coverage below 80%
pest --coverage --min=80

# Custom threshold
pest --coverage --min=90
```

### Composer Scripts

The following scripts are available:

```bash
# Run full test suite with coverage
composer test:coverage

# Run coverage with type coverage check
composer test:type-coverage

# Run all tests (includes coverage check)
composer test

# CI-specific coverage
composer test:ci
```

## Filament Integration

### Coverage Dashboard Widget

A Filament widget displays real-time coverage metrics:

**Location**: `app/Filament/Widgets/System/CodeCoverageWidget.php`

**Features**:
- Overall coverage percentage
- Line coverage
- Branch coverage
- Method coverage
- Trend indicators
- Quick actions to run coverage

### Coverage Management Page

**Location**: `app/Filament/Pages/System/CodeCoverage.php`

**Features**:
- View detailed coverage reports
- Run coverage analysis
- Download coverage reports (HTML, XML, JSON)
- View coverage history
- Set coverage thresholds
- View uncovered files

### Coverage Service

**Location**: `app/Services/Testing/CodeCoverageService.php`

**Methods**:
```php
// Get current coverage statistics
$service->getCoverageStats(): array

// Run coverage analysis
$service->runCoverage(string $suite = null): array

// Get coverage history
$service->getCoverageHistory(int $days = 30): Collection

// Parse coverage report
$service->parseCoverageReport(string $path): array

// Check if coverage meets threshold
$service->meetsThreshold(float $coverage, float $threshold = 80.0): bool
```

## CI/CD Integration

### GitHub Actions
```yaml
name: Tests with Coverage

on: [push, pull_request]

jobs:
  test:
    runs-on: ubuntu-latest
    
    steps:
      - uses: actions/checkout@v4
      
      - name: Setup PHP with PCOV
        uses: shivammathur/setup-php@v2
        with:
          php-version: '8.4'
          extensions: pcov
          coverage: pcov
      
      - name: Install Dependencies
        run: composer install --prefer-dist --no-progress
      
      - name: Run Tests with Coverage
        run: composer test:coverage
      
      - name: Upload Coverage to Codecov
        uses: codecov/codecov-action@v4
        with:
          files: ./coverage.xml
          fail_ci_if_error: true
```

### GitLab CI
```yaml
test:coverage:
  image: php:8.4
  before_script:
    - pecl install pcov
    - docker-php-ext-enable pcov
    - composer install
  script:
    - composer test:coverage
  coverage: '/^\s*Lines:\s*\d+.\d+\%/'
  artifacts:
    reports:
      coverage_report:
        coverage_format: cobertura
        path: coverage.xml
```

## Performance Optimization

### Excluding Files from Coverage

#### Via PHPUnit Configuration
```xml
<source>
    <include>
        <directory>app</directory>
        <directory>app-modules</directory>
    </include>
    <exclude>
        <directory>app/Console/Kernel.php</directory>
        <directory>app/Exceptions/Handler.php</directory>
        <directory>app/Http/Middleware</directory>
        <file>app/Providers/AppServiceProvider.php</file>
    </exclude>
</source>
```

#### Via PCOV Configuration
```ini
pcov.exclude = "~vendor~|~tests~|~node_modules~"
```

### Memory Optimization
```bash
# Increase memory limit for large projects
php -d memory_limit=1G vendor/bin/pest --coverage
```

### Parallel Testing with Coverage
```bash
# Run tests in parallel (coverage will be merged)
pest --parallel --coverage
```

## Coverage Metrics Explained

### Line Coverage
Percentage of executable lines that were executed during tests.

**Target**: 80%+ for production code

### Branch Coverage
Percentage of decision branches (if/else, switch) that were executed.

**Target**: 70%+ for critical paths

### Method Coverage
Percentage of methods that were called during tests.

**Target**: 90%+ for public APIs

### Type Coverage
Percentage of code with proper type declarations (Pest-specific).

**Target**: 99.9% (enforced via `composer test:type-coverage`)

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
# Check if PCOV is installed
php -m | grep pcov

# If not installed, install via PECL
pecl install pcov

# Enable in php.ini
echo "extension=pcov.so" >> /path/to/php.ini
```

### Coverage Report Empty
```bash
# Ensure PCOV is enabled
php --ri pcov

# Check PCOV directory setting
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

### Slow Coverage Generation
```bash
# Use PCOV instead of Xdebug
php -d xdebug.mode=off vendor/bin/pest --coverage

# Exclude unnecessary directories
# Update phpunit.xml <exclude> section
```

## Coverage Reports

### HTML Report Structure
```
coverage-html/
├── index.html          # Overview
├── dashboard.html      # Metrics dashboard
├── app/               # App coverage
│   ├── Models/
│   ├── Services/
│   └── ...
└── .css/              # Styling
```

### Clover XML Format
Used by CI tools (Codecov, Coveralls, SonarQube):
```xml
<?xml version="1.0"?>
<coverage generated="1234567890">
  <project timestamp="1234567890">
    <file name="app/Models/User.php">
      <line num="10" type="stmt" count="5"/>
      <line num="11" type="stmt" count="0"/>
    </file>
  </project>
</coverage>
```

## Integration with Existing Tools

### PHPStan Integration
```bash
# Run static analysis with coverage
composer test:types
composer test:coverage
```

### Rector Integration
```bash
# Refactor code, then check coverage
composer lint
composer test:coverage
```

### Pest Integration
```bash
# Type coverage + code coverage
composer test:type-coverage
composer test:coverage
```

## Monitoring Coverage Trends

### Coverage History Service
```php
use App\Services\Testing\CodeCoverageService;

$service = app(CodeCoverageService::class);

// Get coverage history
$history = $service->getCoverageHistory(30); // Last 30 days

// Get coverage trend
$trend = $service->getCoverageTrend(); // 'up', 'down', 'stable'
```

### Filament Widget
The `CodeCoverageWidget` displays:
- Current coverage percentage
- Trend indicator (↑ ↓ →)
- Coverage by category (Models, Services, Controllers)
- Quick actions

## Advanced Configuration

### Custom Coverage Filters
```php
// tests/Pest.php
use SebastianBergmann\CodeCoverage\Filter;
use SebastianBergmann\CodeCoverage\CodeCoverage;

pest()->extend(Tests\TestCase::class)
    ->beforeEach(function () {
        if (extension_loaded('pcov')) {
            ini_set('pcov.enabled', '1');
            ini_set('pcov.directory', base_path());
            ini_set('pcov.exclude', '~vendor~');
        }
    });
```

### Coverage Annotations
```php
/**
 * @codeCoverageIgnore
 */
class DeprecatedClass
{
    // This class will be excluded from coverage
}

/**
 * @codeCoverageIgnoreStart
 */
function legacyCode()
{
    // This function will be excluded
}
/**
 * @codeCoverageIgnoreEnd
 */
```

## Related Documentation
- `docs/testing-infrastructure.md` - Testing setup and patterns
- `.kiro/steering/testing-standards.md` - Testing conventions
- `.kiro/steering/pest-route-testing.md` - Route testing patterns
- `docs/pest-laravel-expectations.md` - Pest expectations plugin

## Package Information
- **Extension**: PCOV (PHP Extension)
- **Version**: Latest stable
- **Service**: `App\Services\Testing\CodeCoverageService`
- **Widget**: `App\Filament\Widgets\System\CodeCoverageWidget`
- **Page**: `App\Filament\Pages\System\CodeCoverage`
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

### CI Integration
```yaml
- uses: shivammathur/setup-php@v2
  with:
    coverage: pcov
```

### Filament Access
Navigate to: **System → Code Coverage**
