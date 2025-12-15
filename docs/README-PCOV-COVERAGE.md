# PCOV Code Coverage - Quick Start Guide

## What is PCOV?

PCOV is a lightweight PHP extension designed specifically for code coverage. It's **10-30x faster** than Xdebug and uses minimal memory, making it perfect for running coverage analysis regularly during development.

## Installation

### macOS (Homebrew/Herd)

```bash
# Install PCOV via PECL
pecl install pcov

# Verify installation
php -m | grep pcov
```

### Linux (Ubuntu/Debian)

```bash
# Install PCOV
sudo apt-get install php8.4-pcov

# Or via PECL
sudo pecl install pcov
```

### Docker

Add to your Dockerfile:

```dockerfile
RUN pecl install pcov && docker-php-ext-enable pcov
```

## Configuration

### 1. Enable PCOV in php.ini

```ini
extension=pcov.so
pcov.enabled = 1
pcov.directory = /path/to/your/project
pcov.exclude = "~vendor~"
```

### 2. Set Environment Variables

Add to your `.env`:

```env
PCOV_ENABLED=true
PCOV_DIRECTORY=.
PCOV_EXCLUDE="~vendor~"
COVERAGE_MIN_PERCENTAGE=80
COVERAGE_MIN_TYPE_COVERAGE=99.9
```

## Usage

### Quick Commands

```bash
# Run tests with coverage (80% minimum)
composer test:coverage

# Run type coverage check (99.9% minimum)
composer test:type-coverage

# View HTML report
open coverage-html/index.html
```

### Detailed Commands

```bash
# Generate HTML report
pest --coverage-html=coverage-html

# Generate XML report (for CI)
pest --coverage-clover=coverage.xml

# Show coverage in terminal
pest --coverage-text

# Run coverage for specific suite
pest --testsuite=Feature --coverage
pest --testsuite=Unit --coverage
```

## Viewing Coverage

### Option 1: Filament v4.3+ UI (Recommended)

1. Navigate to **System → Code Coverage** in Filament
2. Click **Run Coverage** to generate fresh reports
3. View detailed statistics and trends
4. Download HTML/XML reports

### Option 2: HTML Report

```bash
# Generate and open HTML report
composer test:coverage
open coverage-html/index.html
```

### Option 3: Terminal Output

```bash
# Show coverage in terminal
pest --coverage-text
```

## Coverage Thresholds

The project enforces the following coverage thresholds:

- **Overall Coverage**: 80% minimum
- **Type Coverage**: 99.9% minimum
- **Method Coverage**: 90% target for public APIs
- **Class Coverage**: 80% target for application classes

## Filament Integration

### Dashboard Widget

The Code Coverage Widget displays:
- Overall coverage percentage with color coding
- Method and class coverage metrics
- 7-day coverage trend chart
- Quick actions to run coverage

### Management Page

Access at **System → Code Coverage** to:
- View PCOV status and configuration
- See detailed coverage statistics
- View coverage by category (Models, Services, Controllers)
- Run full coverage analysis
- Download HTML/XML reports
- Clear coverage cache

## CI/CD Integration

### GitHub Actions

See `.github/workflows/coverage.yml.example` for a complete workflow that:
- Sets up PHP with PCOV
- Runs tests with coverage
- Uploads to Codecov/Coveralls
- Comments coverage on PRs
- Enforces 80% threshold

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
```

## Troubleshooting

### PCOV Not Found

```bash
# Check if installed
php -m | grep pcov

# If not found, install
pecl install pcov

# Enable in php.ini
echo "extension=pcov.so" >> $(php --ini | grep "Loaded Configuration" | sed -e "s|.*:\s*||")
```

### Coverage Report Empty

```bash
# Verify PCOV is enabled
php --ri pcov

# Check configuration
php -i | grep pcov

# Verify source paths in phpunit.xml
cat phpunit.xml | grep -A 10 "<source>"
```

### Memory Limit Errors

```bash
# Increase memory limit
php -d memory_limit=1G vendor/bin/pest --coverage

# Or update php.ini
memory_limit = 1G
```

## Best Practices

### ✅ DO:
- Run coverage locally before pushing
- Aim for 80%+ overall coverage
- Focus on critical business logic
- Exclude generated/boilerplate code
- Monitor coverage trends over time
- Review coverage reports regularly

### ❌ DON'T:
- Aim for 100% coverage blindly
- Test getters/setters just for coverage
- Skip edge cases to maintain coverage
- Ignore coverage drops in PRs
- Include vendor code in coverage
- Run coverage on every test run (it's slow)

## Performance Tips

### Parallel Testing

```bash
# Run tests in parallel (coverage merged automatically)
pest --parallel --coverage
```

### Selective Coverage

```bash
# Run coverage for specific directory
pest tests/Unit --coverage

# Run coverage for specific file
pest tests/Unit/Services/CodeCoverageServiceTest.php --coverage
```

### Memory Optimization

```bash
# Increase memory for large projects
php -d memory_limit=1G vendor/bin/pest --coverage
```

## Excluding Code from Coverage

### Via PHPUnit Configuration

Edit `phpunit.xml`:

```xml
<source>
    <exclude>
        <directory>app/Console/Kernel.php</directory>
        <directory>app/Exceptions/Handler.php</directory>
        <file>app/Providers/AppServiceProvider.php</file>
    </exclude>
</source>
```

### Via Annotations

```php
/**
 * @codeCoverageIgnore
 */
class DeprecatedClass
{
    // This class will be excluded
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

## Automated Monitoring

The project includes a Kiro hook (`.kiro/hooks/code-coverage-monitor.kiro.hook`) that:
- Monitors test file and application code changes
- Suggests running coverage analysis
- Provides quick commands for coverage generation
- Reminds about coverage thresholds

## Related Documentation

- **Complete Guide**: `docs/pcov-code-coverage-integration.md`
- **Steering Rules**: `.kiro/steering/pcov-code-coverage.md`
- **Testing Standards**: `.kiro/steering/testing-standards.md`
- **Service Pattern**: `docs/laravel-container-services.md`

## Quick Reference Card

| Task | Command |
|------|---------|
| Install PCOV | `pecl install pcov` |
| Check if installed | `php -m \| grep pcov` |
| Run coverage | `composer test:coverage` |
| View HTML report | `open coverage-html/index.html` |
| View in Filament | Navigate to System → Code Coverage |
| Run specific suite | `pest --testsuite=Feature --coverage` |
| Generate XML | `pest --coverage-clover=coverage.xml` |
| Check threshold | `pest --coverage --min=80` |

## Support

For issues or questions:
1. Check the troubleshooting section above
2. Review `docs/pcov-code-coverage-integration.md`
3. Check `.kiro/steering/pcov-code-coverage.md`
4. Review test examples in `tests/Unit/Services/Testing/`

## Next Steps

1. ✅ Install PCOV: `pecl install pcov`
2. ✅ Verify installation: `php -m | grep pcov`
3. ✅ Run coverage: `composer test:coverage`
4. ✅ View report: `open coverage-html/index.html`
5. ✅ Check Filament UI: System → Code Coverage
6. ✅ Set up CI/CD with coverage reporting
