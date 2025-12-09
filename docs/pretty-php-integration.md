# Pretty PHP Integration Guide

## Overview
Pretty PHP (`lkrms/pretty-php`) is an opinionated PHP code formatter that provides more aggressive formatting than Laravel Pint, with a focus on modern, expressive PHP code.

## Current Status
⚠️ **PHP 8.4 Compatibility**: As of December 2024, Pretty PHP does not yet support PHP 8.4. This project uses PHP 8.4, so we cannot install Pretty PHP until it's updated.

- **Latest Version**: v0.4.95
- **PHP Support**: PHP 7.4 - 8.2
- **Required**: PHP 8.4+ (this project)
- **GitHub**: https://github.com/lkrms/pretty-php
- **Packagist**: https://packagist.org/packages/lkrms/pretty-php

## Installation (When PHP 8.4 Support is Available)

```bash
composer require --dev lkrms/pretty-php
```

## Configuration

### Basic Configuration
Create `.prettyphp` configuration file in project root:

```json
{
    "preset": "laravel",
    "src": [
        "app",
        "app-modules",
        "config",
        "database",
        "routes",
        "tests"
    ],
    "exclude": [
        "vendor",
        "node_modules",
        "storage",
        "bootstrap/cache"
    ]
}
```

### Advanced Configuration
```json
{
    "preset": "laravel",
    "src": ["app", "app-modules", "config", "database", "routes", "tests"],
    "exclude": ["vendor", "node_modules", "storage", "bootstrap/cache"],
    "tab": "    ",
    "eol": "\n",
    "insertSpaces": true,
    "tabSize": 4,
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

## Usage

### Format All Files
```bash
vendor/bin/pretty-php
```

### Format Specific Directory
```bash
vendor/bin/pretty-php app/
```

### Check Without Modifying (Dry Run)
```bash
vendor/bin/pretty-php --diff
```

### Format Single File
```bash
vendor/bin/pretty-php app/Models/User.php
```

## Composer Scripts Integration

Add to `composer.json`:

```json
{
    "scripts": {
        "format": "pretty-php",
        "format:check": "pretty-php --diff",
        "format:app": "pretty-php app/",
        "format:tests": "pretty-php tests/",
        "lint": "rector && pretty-php && pint --parallel"
    }
}
```

## CI/CD Integration

### GitHub Actions
```yaml
- name: Check Code Formatting
  run: composer format:check
```

### Pre-commit Hook
Create `.githooks/pre-commit`:

```bash
#!/bin/bash
vendor/bin/pretty-php --diff
if [ $? -ne 0 ]; then
    echo "Code formatting issues detected. Run 'composer format' to fix."
    exit 1
fi
```

## Pretty PHP vs Pint

### Pretty PHP Advantages
- More opinionated formatting
- Better handling of complex expressions
- Improved alignment and spacing
- More consistent array formatting
- Better heredoc/nowdoc formatting

### Pint Advantages
- Official Laravel tool
- Faster execution
- Better Laravel ecosystem integration
- More stable and mature

### Recommended Workflow
1. **Rector** - Automated refactoring and code quality
2. **Pretty PHP** - Opinionated formatting
3. **Pint** - Final Laravel-specific formatting

```bash
composer lint  # Runs: rector && pretty-php && pint --parallel
```

## Features

### Declaration Spacing
Pretty PHP can enforce consistent spacing between class members:

```php
// Before
class User {
    private string $name;
    private string $email;
    public function getName(): string {
        return $this->name;
    }
    public function getEmail(): string {
        return $this->email;
    }
}

// After (with line spacing)
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
```php
// Before
$result = $condition ? $value1 : $value2;

// After (with line breaks)
$result = $condition
    ? $value1
    : $value2;
```

### Array Formatting
```php
// Before
$array = ['key1' => 'value1', 'key2' => 'value2', 'key3' => 'value3'];

// After
$array = [
    'key1' => 'value1',
    'key2' => 'value2',
    'key3' => 'value3',
];
```

### Heredoc Indentation
```php
// Before
$html = <<<HTML
<div>
<p>Content</p>
</div>
HTML;

// After (with indentation)
$html = <<<HTML
    <div>
        <p>Content</p>
    </div>
    HTML;
```

## Best Practices

### DO:
- ✅ Run Pretty PHP before Pint in your lint workflow
- ✅ Configure exclusions for generated code
- ✅ Use in CI/CD to enforce formatting
- ✅ Commit `.prettyphp` configuration to version control
- ✅ Run on pre-commit hooks for consistency

### DON'T:
- ❌ Run Pretty PHP and Pint in opposite order
- ❌ Skip configuration - use project-specific settings
- ❌ Format vendor code
- ❌ Ignore formatting errors in CI

## Troubleshooting

### Memory Limit Issues
```bash
php -d memory_limit=1G vendor/bin/pretty-php
```

### Slow Performance
- Exclude unnecessary directories
- Use specific paths instead of entire project
- Run in parallel with xargs:

```bash
find app -name "*.php" | xargs -P 4 -I {} vendor/bin/pretty-php {}
```

### Conflicts with Pint
If Pretty PHP and Pint conflict:
1. Run Pretty PHP first
2. Then run Pint
3. Adjust `.prettyphp` config to align with Pint rules

## Migration from Pint Only

1. Install Pretty PHP (when PHP 8.4 support is available)
2. Create `.prettyphp` configuration
3. Run on entire codebase: `composer format`
4. Review changes in git diff
5. Commit formatted code
6. Update CI/CD pipelines
7. Update team documentation

## Interim Solution (Until PHP 8.4 Support)

While waiting for PHP 8.4 support, enhance Pint configuration to incorporate Pretty PHP's philosophy:

### Enhanced `pint.json`
```json
{
    "preset": "laravel",
    "rules": {
        "declare_strict_types": true,
        "final_class": true,
        "final_internal_class": true,
        "strict_comparison": true,
        "array_syntax": {
            "syntax": "short"
        },
        "binary_operator_spaces": {
            "default": "single_space"
        },
        "blank_line_after_namespace": true,
        "blank_line_after_opening_tag": true,
        "blank_line_before_statement": {
            "statements": ["return", "throw", "try"]
        },
        "class_attributes_separation": {
            "elements": {
                "method": "one",
                "property": "one"
            }
        },
        "concat_space": {
            "spacing": "one"
        },
        "method_chaining_indentation": true,
        "multiline_whitespace_before_semicolons": {
            "strategy": "no_multi_line"
        },
        "no_extra_blank_lines": {
            "tokens": [
                "extra",
                "throw",
                "use"
            ]
        },
        "no_spaces_around_offset": {
            "positions": ["inside", "outside"]
        },
        "no_unused_imports": true,
        "no_whitespace_before_comma_in_array": true,
        "ordered_imports": {
            "sort_algorithm": "alpha"
        },
        "phpdoc_align": {
            "align": "vertical"
        },
        "phpdoc_separation": true,
        "phpdoc_single_line_var_spacing": true,
        "return_type_declaration": {
            "space_before": "none"
        },
        "single_quote": true,
        "trailing_comma_in_multiline": {
            "elements": ["arrays", "arguments", "parameters"]
        },
        "trim_array_spaces": true,
        "whitespace_after_comma_in_array": true
    }
}
```

## Related Documentation
- `.kiro/steering/laravel-conventions.md` - Laravel coding standards
- `.kiro/steering/testing-standards.md` - Testing and code quality
- `.kiro/steering/rector-v2.md` - Rector integration
- `docs/rector-v2-integration.md` - Rector usage guide

## References
- Laravel News Article: https://laravel-news.com/pretty-php
- GitHub Repository: https://github.com/lkrms/pretty-php
- Packagist: https://packagist.org/packages/lkrms/pretty-php

## Monitoring for PHP 8.4 Support

Check for updates:
```bash
composer show lkrms/pretty-php --all
```

Watch GitHub releases:
- https://github.com/lkrms/pretty-php/releases

Subscribe to Packagist:
- https://packagist.org/packages/lkrms/pretty-php

## Future Integration Checklist

When PHP 8.4 support is available:

- [ ] Install Pretty PHP: `composer require --dev lkrms/pretty-php`
- [ ] Create `.prettyphp` configuration file
- [ ] Update `composer.json` scripts
- [ ] Run initial format: `composer format`
- [ ] Review and commit changes
- [ ] Update CI/CD pipelines
- [ ] Update `.kiro/steering/laravel-conventions.md`
- [ ] Update team documentation
- [ ] Add pre-commit hook
- [ ] Train team on new workflow
