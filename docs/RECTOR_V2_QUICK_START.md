# Rector v2 Quick Start Guide

## What is Rector?

Rector is an automated PHP refactoring tool that helps you:
- Upgrade Laravel versions automatically
- Add type declarations to your code
- Convert arrays to collections
- Remove dead code
- Enforce code quality standards
- Modernize PHP patterns

## Quick Commands

### Apply Refactoring (Write Mode)
```bash
# Run Rector + Pint (recommended before commits)
composer lint

# Run Rector only
vendor/bin/rector
```

### Preview Changes (Dry Run)
```bash
# Check what Rector would change
composer test:refactor

# Or directly
vendor/bin/rector --dry-run
```

### Process Specific Paths
```bash
# Refactor a specific directory
vendor/bin/rector process app/Services

# Refactor a specific file
vendor/bin/rector process app/Models/User.php
```

### Clear Cache
```bash
vendor/bin/rector clear-cache
```

## What Rector Does Automatically

### 1. Privatizes Methods
```php
// Before
final class MyClass
{
    protected function helperMethod(): void
    {
        // Only used internally
    }
}

// After (Rector applies)
final class MyClass
{
    private function helperMethod(): void
    {
        // Only used internally
    }
}
```

### 2. Converts to Arrow Functions
```php
// Before
Http::assertSent(function ($request): bool {
    return $request->hasHeader('Authorization', 'Bearer token');
});

// After (Rector applies)
Http::assertSent(fn($request): bool => 
    $request->hasHeader('Authorization', 'Bearer token')
);
```

### 3. Adds Return Types
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

### 4. Converts Arrays to Collections
```php
// Before
$names = array_map(function ($user) {
    return $user->name;
}, $users);

// After (Rector applies)
$names = collect($users)->map(fn($user) => $user->name);
```

### 5. Removes Debug Code
```php
// Before
public function process($data)
{
    dd($data); // Rector removes this
    return $data->process();
}

// After (Rector applies)
public function process($data)
{
    return $data->process();
}
```

### 6. Early Returns
```php
// Before
public function handle($data)
{
    if ($data !== null) {
        if ($data->isValid()) {
            return $data->process();
        }
    }
    return null;
}

// After (Rector applies)
public function handle($data): mixed
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

## Daily Workflow

### Before Committing
```bash
# 1. Make your changes
# 2. Run Rector + Pint
composer lint

# 3. Review changes in git diff
git diff

# 4. Run tests
composer test

# 5. Commit
git add .
git commit -m "feat: add new feature"
```

### When Rector Suggests Changes
```bash
# Preview what Rector wants to change
composer test:refactor

# If changes look good, apply them
composer lint

# If you disagree with a change, skip it
# Add to rector.php skip configuration
```

## Common Scenarios

### Rector Wants to Change Something You Don't Want

**Option 1: Skip in Configuration**
```php
// In rector.php
->withSkip([
    ProblematicRector::class => [
        __DIR__.'/app/Specific/Path/*',
    ],
])
```

**Option 2: Skip with Comment**
```php
/** @noRector */
public function methodToSkip()
{
    // Rector will skip this method
}
```

### Rector Changes Break Tests

```bash
# 1. Revert Rector changes
git checkout -- .

# 2. Run Rector on specific path
vendor/bin/rector process app/Services/ProblematicService.php

# 3. Review and fix
# 4. Add skip rule if needed
```

### Memory Issues

```bash
# Increase memory limit
php -d memory_limit=1G vendor/bin/rector
```

## Integration with CI/CD

### GitHub Actions Example
```yaml
- name: Check Code Quality
  run: |
    composer test:refactor
    composer test:lint
```

### Pre-Commit Hook
```bash
#!/bin/bash
# .git/hooks/pre-commit

composer lint
if [ $? -ne 0 ]; then
    echo "Rector/Pint failed. Please fix and try again."
    exit 1
fi
```

## What Rector Doesn't Do

- ❌ Doesn't fix syntax errors (use Pint for that)
- ❌ Doesn't run tests (use Pest for that)
- ❌ Doesn't check types (use PHPStan for that)
- ❌ Doesn't format code (use Pint for that)

Rector focuses on **refactoring** and **modernizing** code patterns.

## Troubleshooting

### "Rector changed something wrong"
```bash
# Revert the change
git checkout -- path/to/file.php

# Add skip rule in rector.php
```

### "Rector is slow"
```bash
# Clear cache
vendor/bin/rector clear-cache

# Process smaller chunks
vendor/bin/rector process app/Services
```

### "Rector conflicts with my code style"
```bash
# Run Pint after Rector
composer lint
```

## Learn More

- **Comprehensive Guide**: `docs/rector-v2-integration.md`
- **Laravel-Specific Rules**: `docs/rector-laravel.md`
- **Configuration**: `rector.php`
- **Steering Rules**: `.kiro/steering/rector-v2.md`

## Quick Reference

| Command | Purpose |
|---------|---------|
| `composer lint` | Apply Rector + Pint |
| `composer test:refactor` | Preview Rector changes |
| `vendor/bin/rector` | Run Rector |
| `vendor/bin/rector --dry-run` | Preview changes |
| `vendor/bin/rector clear-cache` | Clear cache |
| `vendor/bin/rector process app/` | Process specific path |

## Tips

✅ **DO:**
- Run `composer lint` before every commit
- Review Rector changes in git diff
- Use `--dry-run` to preview changes
- Clear cache after config changes

❌ **DON'T:**
- Blindly accept all Rector changes
- Skip Rector in CI pipeline
- Commit without running `composer lint`
- Disable rules without understanding why
