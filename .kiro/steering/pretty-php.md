# Pretty PHP Code Formatting

## Overview
Pretty PHP is an opinionated PHP code formatter that provides more aggressive formatting than Laravel Pint. This project will integrate Pretty PHP once PHP 8.4 support is available.

## Current Status
⚠️ **Awaiting PHP 8.4 Support**: Pretty PHP currently supports PHP 7.4 - 8.2. This project uses PHP 8.4, so integration is pending package updates.

## Interim Approach
Until Pretty PHP supports PHP 8.4, we've enhanced our Pint configuration to incorporate Pretty PHP's formatting philosophy:

### Enhanced Pint Rules
- **Array Syntax**: Short array syntax enforced
- **Binary Operators**: Single space around operators
- **Blank Lines**: Strategic blank lines before returns, throws, and try blocks
- **Class Separation**: One blank line between properties and methods
- **Concat Spacing**: Single space around concatenation operators
- **Method Chaining**: Proper indentation for chained methods
- **Import Ordering**: Alphabetically sorted imports
- **PHPDoc Alignment**: Vertical alignment for better readability
- **Trailing Commas**: Required in multiline arrays, arguments, and parameters

## Formatting Workflow
The current formatting workflow follows this order:

1. **Rector v2** - Automated refactoring and code quality improvements
2. **Pint** - Code formatting with enhanced rules

```bash
composer lint  # Runs: rector && pint --parallel
```

## Future Integration (When PHP 8.4 Support Available)

### Installation
```bash
composer require --dev lkrms/pretty-php
```

### Configuration
Create `.prettyphp` in project root:

```json
{
    "preset": "laravel",
    "src": ["app", "app-modules", "config", "database", "routes", "tests"],
    "exclude": ["vendor", "node_modules", "storage", "bootstrap/cache"],
    "declarationSpacing": {
        "Properties": "line",
        "Methods": "line"
    },
    "operators": {
        "Ternary": "line",
        "Logical": "line"
    },
    "heredoc": {
        "indent": true
    }
}
```

### Updated Workflow
Once integrated, the workflow will be:

1. **Rector v2** - Automated refactoring
2. **Pretty PHP** - Opinionated formatting
3. **Pint** - Final Laravel-specific formatting

```bash
composer lint  # Will run: rector && pretty-php && pint --parallel
```

## Formatting Philosophy

### Declaration Spacing
Enforce blank lines between class members for better readability:

```php
// Preferred
class User
{
    private string $name;
    
    private string $email;
    
    public function getName(): string
    {
        return $this->name;
    }
    
    public function getEmail(): string
    {
        return $this->email;
    }
}
```

### Operator Alignment
Break complex ternary and logical operators across lines:

```php
// Preferred
$result = $condition
    ? $value1
    : $value2;

$isValid = $checkOne
    && $checkTwo
    && $checkThree;
```

### Array Formatting
Consistent multiline array formatting:

```php
// Preferred
$config = [
    'key1' => 'value1',
    'key2' => 'value2',
    'key3' => 'value3',
];
```

### Heredoc Indentation
Properly indent heredoc content:

```php
// Preferred
$html = <<<HTML
    <div>
        <p>Content</p>
    </div>
    HTML;
```

## Best Practices

### DO:
- ✅ Run formatters in the correct order (Rector → Pretty PHP → Pint)
- ✅ Commit formatting changes separately from logic changes
- ✅ Use `composer lint` before every commit
- ✅ Review formatting changes in git diff
- ✅ Keep formatting configuration in version control

### DON'T:
- ❌ Skip formatting checks in CI/CD
- ❌ Mix formatting and logic changes in same commit
- ❌ Manually format code that tools can handle
- ❌ Ignore formatting errors
- ❌ Format vendor code

## CI/CD Integration

### Current (Pint Only)
```yaml
- name: Check Code Style
  run: composer test:lint
```

### Future (With Pretty PHP)
```yaml
- name: Check Code Formatting
  run: |
    composer format:check
    composer test:lint
```

## Monitoring for Updates

Check for Pretty PHP PHP 8.4 support:

```bash
# Check current version constraints
composer show lkrms/pretty-php --all

# Watch GitHub releases
# https://github.com/lkrms/pretty-php/releases

# Subscribe to Packagist updates
# https://packagist.org/packages/lkrms/pretty-php
```

## Integration Checklist

When PHP 8.4 support becomes available:

1. ✅ Install Pretty PHP package
2. ✅ Create `.prettyphp` configuration
3. ✅ Update `composer.json` scripts
4. ✅ Run initial format on entire codebase
5. ✅ Review and commit formatting changes
6. ✅ Update CI/CD pipelines
7. ✅ Update this steering file
8. ✅ Update `docs/pretty-php-integration.md`
9. ✅ Update `AGENTS.md` with new workflow
10. ✅ Train team on new formatting workflow

## Related Documentation
- `docs/pretty-php-integration.md` - Complete integration guide
- `.kiro/steering/laravel-conventions.md` - Laravel coding standards
- `.kiro/steering/rector-v2.md` - Rector integration
- `.kiro/steering/testing-standards.md` - Testing and code quality

## References
- Laravel News: https://laravel-news.com/pretty-php
- GitHub: https://github.com/lkrms/pretty-php
- Packagist: https://packagist.org/packages/lkrms/pretty-php
