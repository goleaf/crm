# Rector v2 Integration Guide

## Overview

Rector v2 is a powerful PHP automated refactoring tool that helps maintain code quality, enforce modern PHP patterns, and automate Laravel framework upgrades. This project uses Rector v2 with the `driftingly/rector-laravel` extension for Laravel-specific refactoring rules.

## What's New in Rector v2

### Major Improvements
- **Composer-Based Detection**: Automatically detects Laravel version and packages from `composer.json`
- **Set Providers**: New `LaravelSetProvider` for dynamic rule loading based on your Laravel version
- **Better Performance**: Faster analysis and processing with improved caching
- **Enhanced Type Inference**: Better understanding of Laravel magic methods and facades
- **Improved Skip Configuration**: More granular control over which rules to skip

### Breaking Changes from v1
- Configuration API changed to use `RectorConfig::configure()`
- Set loading now uses `withSets()` instead of `sets()`
- Path configuration uses `withPaths()` instead of `paths()`
- Skip configuration uses `withSkip()` instead of `skip()`

## Configuration

### Current Setup (`rector.php`)

```php
<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use RectorLaravel\Set\LaravelSetList;
use RectorLaravel\Set\LaravelSetProvider;

return RectorConfig::configure()
    ->withSetProviders(LaravelSetProvider::class)
    ->withComposerBased(laravel: true)
    ->withSets([
        LaravelSetList::LARAVEL_CODE_QUALITY,
        LaravelSetList::LARAVEL_COLLECTION,
        LaravelSetList::LARAVEL_TESTING,
        LaravelSetList::LARAVEL_TYPE_DECLARATIONS,
    ])
    ->withPaths([
        __DIR__.'/app',
        __DIR__.'/app-modules',
        __DIR__.'/bootstrap/app.php',
        __DIR__.'/config',
        __DIR__.'/database',
        __DIR__.'/lang',
        __DIR__.'/public',
        __DIR__.'/routes',
        __DIR__.'/tests',
    ])
    ->withPreparedSets(
        deadCode: true,
        codeQuality: true,
        typeDeclarations: true,
        privatization: true,
        earlyReturn: true,
    )
    ->withPhpSets();
```

### Configuration Breakdown

#### Set Providers
```php
->withSetProviders(LaravelSetProvider::class)
```
Automatically loads Laravel-specific rules based on your installed Laravel version.

#### Composer-Based Detection
```php
->withComposerBased(laravel: true)
```
Reads `composer.json` to detect Laravel version and first-party packages, enabling version-specific refactoring rules.

#### Laravel Rule Sets

**LARAVEL_CODE_QUALITY**
- Converts array helpers to Collection methods
- Modernizes request handling
- Improves validation patterns
- Enforces Laravel best practices

**LARAVEL_COLLECTION**
- Converts array functions to Collection methods
- Optimizes collection usage
- Adds type hints for collections

**LARAVEL_TESTING**
- Modernizes PHPUnit assertions
- Converts to Pest syntax where applicable
- Improves test readability

**LARAVEL_TYPE_DECLARATIONS**
- Adds return types to methods
- Adds parameter types
- Improves type safety across Laravel code

#### Prepared Sets

**deadCode: true**
- Removes unused private methods
- Removes unused variables
- Removes unreachable code

**codeQuality: true**
- Simplifies boolean expressions
- Removes unnecessary parentheses
- Improves code readability

**typeDeclarations: true**
- Adds missing return types
- Adds missing parameter types
- Enforces strict typing

**privatization: true**
- Makes methods private when possible
- Improves encapsulation

**earlyReturn: true**
- Converts nested conditions to early returns
- Improves code flow

#### PHP Sets
```php
->withPhpSets()
```
Automatically applies PHP version-specific rules based on your `composer.json` PHP requirement (^8.4).

## Usage

### Running Rector

#### Apply Fixes (Write Mode)
```bash
# Via composer script (recommended)
composer lint

# Direct execution
vendor/bin/rector

# Process specific paths
vendor/bin/rector process app/Models
vendor/bin/rector process app/Filament/Resources
```

#### Dry Run (Preview Changes)
```bash
# Via composer script
composer test:refactor

# Direct execution
vendor/bin/rector --dry-run

# With detailed output
vendor/bin/rector --dry-run --debug
```

#### Clear Cache
```bash
vendor/bin/rector clear-cache
```

### Integration with Development Workflow

#### Pre-Commit
```bash
# Run before committing
composer lint
```
This runs Rector followed by Pint to ensure code quality and formatting.

#### CI/CD Pipeline
```bash
# In CI (read-only check)
composer test:refactor
```
This runs Rector in dry-run mode to verify no refactoring is needed.

#### Full Test Suite
```bash
# Complete test suite including Rector
composer test
```
Runs: lint check, Rector dry-run, type coverage, PHPStan, and Pest tests.

## Skipped Rules

### Filament Importer Lifecycle Hooks
```php
->withSkip([
    RemoveUnusedPrivateMethodRector::class => [
        __DIR__.'/app/Filament/Imports/*',
    ],
    PrivatizeFinalClassMethodRector::class => [
        __DIR__.'/app/Filament/Imports/*',
    ],
])
```
Filament importers use dynamic lifecycle hooks called via `callHook()`, so Rector shouldn't mark them as unused or privatize them.

### First-Class Callable Conflicts
```php
->withSkip([
    FirstClassCallableRector::class => [
        __DIR__.'/app/Providers/AppServiceProvider.php',
    ],
    FunctionLikeToFirstClassCallableRector::class => [
        __DIR__.'/app/Providers/AppServiceProvider.php',
    ],
])
```
`class_exists()` has an optional bool parameter that conflicts with `Collection::first()` signature when converting to first-class callables.

### Override Attributes
```php
->withSkip([
    AddOverrideAttributeToOverriddenMethodsRector::class,
])
```
Skipped globally to avoid adding `#[Override]` attributes until PHP 8.3+ is minimum requirement.

## Custom Rules

### Remove Debug Code
```php
use RectorLaravel\Rector\FuncCall\RemoveDumpDataDeadCodeRector;

->withConfiguredRule(RemoveDumpDataDeadCodeRector::class, [
    'dd',
    'ddd',
    'dump',
    'ray',
    'var_dump',
])
```
Automatically removes debugging helpers from committed code.

## Common Refactoring Patterns

### Array to Collection
```php
// Before
$names = array_map(function ($user) {
    return $user->name;
}, $users);

// After (Rector applies)
$names = collect($users)->map(fn ($user) => $user->name);
```

### Request Validation
```php
// Before
$validated = $request->validate([
    'email' => 'required|email',
]);

// After (Rector applies)
$validated = $request->validated();
```

### Type Declarations
```php
// Before
public function getName()
{
    return $this->name;
}

// After (Rector applies)
public function getName(): string
{
    return $this->name;
}
```

### Early Returns
```php
// Before
public function process($data)
{
    if ($data !== null) {
        if ($data->isValid()) {
            return $data->process();
        }
    }
    return null;
}

// After (Rector applies)
public function process($data): mixed
{
    if ($data === null) {
        return null;
    }
    
    if (!$data->isValid()) {
        return null;
    }
    
    return $data->process();
}
```

## Extending Rector

### Adding Custom Rules

Create a custom rule in `app/Rector/`:

```php
<?php

namespace App\Rector;

use PhpParser\Node;
use Rector\Rector\AbstractRector;
use Symplify\RuleDocGenerator\ValueObject\RuleDefinition;

final class CustomRector extends AbstractRector
{
    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition('Description of what this rule does', []);
    }

    public function getNodeTypes(): array
    {
        return [Node\Expr\MethodCall::class];
    }

    public function refactor(Node $node): ?Node
    {
        // Your refactoring logic
        return $node;
    }
}
```

Register in `rector.php`:

```php
use App\Rector\CustomRector;

return RectorConfig::configure()
    // ... existing config
    ->withRules([
        CustomRector::class,
    ]);
```

### Adding More Laravel Sets

```php
use RectorLaravel\Set\LaravelSetList;

->withSets([
    // Existing sets
    LaravelSetList::LARAVEL_CODE_QUALITY,
    LaravelSetList::LARAVEL_COLLECTION,
    LaravelSetList::LARAVEL_TESTING,
    LaravelSetList::LARAVEL_TYPE_DECLARATIONS,
    
    // Additional sets
    LaravelSetList::LARAVEL_ARRAY_STR_FUNCTIONS_TO_STATIC_CALL,
    LaravelSetList::LARAVEL_ELOQUENT_MAGIC_METHOD_TO_QUERY_BUILDER,
    LaravelSetList::LARAVEL_FACADE_ALIASES_TO_FULL_NAMES,
])
```

### Project-Specific Debug Helpers

If you add custom debug helpers:

```php
->withConfiguredRule(RemoveDumpDataDeadCodeRector::class, [
    'dd',
    'ddd',
    'dump',
    'ray',
    'var_dump',
    'debug_log',      // Custom helper
    'trace_query',    // Custom helper
])
```

## Troubleshooting

### Rector Not Finding Files
```bash
# Clear cache
vendor/bin/rector clear-cache

# Verify paths
vendor/bin/rector list-rules
```

### Memory Issues
```bash
# Increase memory limit
php -d memory_limit=1G vendor/bin/rector
```

### Conflicting Rules
Add to skip configuration:
```php
->withSkip([
    ProblematicRector::class => [
        __DIR__.'/app/Specific/Path/*',
    ],
])
```

### False Positives
Use inline skip comments:
```php
/** @noRector */
public function methodToSkip()
{
    // Rector will skip this method
}
```

## Performance Tips

### Parallel Processing
Rector v2 automatically uses parallel processing when possible.

### Incremental Processing
Process specific directories when working on features:
```bash
vendor/bin/rector process app/Services/NewFeature
```

### Cache Management
Rector caches analysis results in `var/cache/rector/`. Clear when:
- Updating Rector version
- Changing configuration
- Experiencing unexpected behavior

## Best Practices

### DO:
- ✅ Run `composer lint` before every commit
- ✅ Review Rector changes before committing
- ✅ Use `--dry-run` to preview changes
- ✅ Clear cache after configuration changes
- ✅ Add project-specific rules when needed
- ✅ Skip rules that cause false positives
- ✅ Keep Rector updated regularly

### DON'T:
- ❌ Blindly accept all Rector changes
- ❌ Skip Rector in CI pipeline
- ❌ Ignore Rector warnings
- ❌ Disable rules without understanding why
- ❌ Forget to test after Rector refactoring
- ❌ Commit without running `composer lint`

## Integration with Other Tools

### With Pint (Laravel Formatter)
```bash
# Rector runs first, then Pint
composer lint
```
Rector handles refactoring, Pint handles formatting.

### With PHPStan (Static Analysis)
```bash
# Full quality check
composer test
```
Rector improves code, PHPStan verifies types.

### With Pest (Testing)
```bash
# Ensure tests pass after refactoring
composer test:pest
```

## Monitoring Rector Impact

### Before Committing
```bash
# See what Rector will change
vendor/bin/rector --dry-run

# Apply changes
vendor/bin/rector

# Verify with tests
composer test
```

### In CI/CD
```yaml
# GitHub Actions example
- name: Check Rector
  run: composer test:refactor
```

## Resources

- [Rector Documentation](https://getrector.com/documentation)
- [Rector Laravel Package](https://github.com/driftingly/rector-laravel)
- [Available Laravel Sets](https://github.com/driftingly/rector-laravel/blob/main/docs/rector_rules_overview.md)
- [Writing Custom Rules](https://getrector.com/documentation/custom-rule)

## Version History

### Rector v2.0
- Initial integration with Laravel 12
- Composer-based detection enabled
- Laravel-specific rule sets configured
- Custom skip rules for Filament

### Future Enhancements
- Add more Laravel-specific rules as needed
- Create custom rules for project patterns
- Integrate with pre-commit hooks
- Add Rector metrics to CI dashboard
