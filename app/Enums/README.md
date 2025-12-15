# Enums Directory

This directory contains all application enums enhanced with the BenSampo Laravel Enum package.

## Structure

```
app/Enums/
├── Concerns/
│   └── EnumHelpers.php          # Shared enum functionality
├── CustomFields/                 # Custom field related enums
├── Knowledge/                    # Knowledge base enums
├── Examples/
│   └── ExampleEnum.php          # Template for new enums
├── ProjectStatus.php            # Example: Enhanced enum
├── [Other enums...]
└── README.md                    # This file
```

## Quick Start

### 1. Create a New Enum

```php
<?php

namespace App\Enums;

use App\Enums\Concerns\EnumHelpers;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum YourEnum: string implements HasColor, HasLabel
{
    use EnumHelpers;

    case VALUE_ONE = 'value_one';
    case VALUE_TWO = 'value_two';

    public function getLabel(): string
    {
        return match ($this) {
            self::VALUE_ONE => __('app.your_enum.value_one'),
            self::VALUE_TWO => __('app.your_enum.value_two'),
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::VALUE_ONE => 'primary',
            self::VALUE_TWO => 'success',
        };
    }

    public function getIcon(): string
    {
        return match ($this) {
            self::VALUE_ONE => 'heroicon-o-star',
            self::VALUE_TWO => 'heroicon-o-check',
        };
    }
}
```

### 2. Add Translations

In `lang/en/app.php`:
```php
'your_enum' => [
    'value_one' => 'Value One',
    'value_two' => 'Value Two',
],
```

### 3. Use in Models

```php
class YourModel extends Model
{
    protected $casts = [
        'status' => YourEnum::class,
    ];
}
```

### 4. Use in Filament

```php
Select::make('status')
    ->options(YourEnum::toSelectArray())
    ->default(YourEnum::VALUE_ONE->value);
```

## Available Helper Methods

All enums using the `EnumHelpers` trait have access to:

| Method | Description | Example |
|--------|-------------|---------|
| `values()` | Get all enum values | `['value_one', 'value_two']` |
| `names()` | Get all enum names | `['VALUE_ONE', 'VALUE_TWO']` |
| `fromValueOrNull()` | Safe conversion | `YourEnum::fromValueOrNull('value_one')` |
| `fromName()` | Convert from name | `YourEnum::fromName('VALUE_ONE')` |
| `random()` | Random case | `YourEnum::random()` |
| `isValid()` | Validate value | `YourEnum::isValid('value_one')` |
| `rule()` | Validation rule | `'in:value_one,value_two'` |
| `rules()` | Rule array | `['in:value_one,value_two']` |
| `collect()` | As collection | `Collection` of cases |
| `toSelectArray()` | For selects | `['value_one' => 'Value One', ...]` |
| `toArray()` | As array | `[['value' => '...', 'label' => '...'], ...]` |
| `hasValue()` | Check value | `YourEnum::hasValue('value_one')` |
| `hasName()` | Check name | `YourEnum::hasName('VALUE_ONE')` |
| `count()` | Count cases | `2` |

## Conventions

### Naming
- **Enum names**: PascalCase (e.g., `ProjectStatus`, `TaskPriority`)
- **Case names**: SCREAMING_SNAKE_CASE (e.g., `ACTIVE`, `HIGH_PRIORITY`)
- **Case values**: snake_case (e.g., `'active'`, `'high_priority'`)

### File Organization
- Place domain-specific enums in subdirectories (e.g., `CustomFields/`, `Knowledge/`)
- Keep general enums in the root `Enums/` directory
- One enum per file

### Required Implementations
All enums should:
1. Use the `EnumHelpers` trait
2. Implement `HasLabel` interface (for Filament)
3. Implement `HasColor` interface (for Filament)
4. Optionally implement `getIcon()` method
5. Have corresponding translation keys

### Translation Keys
Follow this pattern in `lang/en/app.php`:
```php
'enum_name' => [
    'case_value' => 'Human Readable Label',
],
```

## Testing

Create tests in `tests/Unit/Enums/YourEnumTest.php`:

```php
use App\Enums\YourEnum;

describe('YourEnum', function () {
    it('has correct values', function () {
        expect(YourEnum::values())->toBe(['value_one', 'value_two']);
    });

    it('converts to select array', function () {
        $array = YourEnum::toSelectArray();
        expect($array)->toHaveKey('value_one');
    });

    it('validates values', function () {
        expect(YourEnum::isValid('value_one'))->toBeTrue();
        expect(YourEnum::isValid('invalid'))->toBeFalse();
    });
});
```

## Validation

### In Form Requests
```php
use App\Rules\EnumValue;

public function rules(): array
{
    return [
        'status' => ['required', new EnumValue(YourEnum::class)],
    ];
}
```

### In Controllers
```php
$request->validate([
    'status' => ['required', YourEnum::rule()],
]);
```

## Database

### Migrations
```php
$table->string('status')->default(YourEnum::VALUE_ONE->value);
```

### Casting
```php
protected $casts = [
    'status' => YourEnum::class,
];
```

## Common Patterns

### State Checks
```php
public function isActive(): bool
{
    return $this === self::ACTIVE;
}

public function isTerminal(): bool
{
    return in_array($this, [self::COMPLETED, self::CANCELLED], true);
}
```

### Transitions
```php
public function allowedTransitions(): array
{
    return match ($this) {
        self::DRAFT => [self::PUBLISHED, self::ARCHIVED],
        self::PUBLISHED => [self::ARCHIVED],
        self::ARCHIVED => [],
    };
}

public function canTransitionTo(self $status): bool
{
    return in_array($status, $this->allowedTransitions(), true);
}
```

### Business Logic
```php
public function allowsEditing(): bool
{
    return !$this->isTerminal();
}

public function requiresApproval(): bool
{
    return $this === self::PENDING_APPROVAL;
}
```

## Documentation

- **Full Guide**: `docs/enums.md`
- **Usage Examples**: `docs/enum-usage-examples.md`
- **Quick Reference**: `docs/enum-quick-reference.md`
- **Integration Summary**: `docs/enum-integration-summary.md`

## Package Information

This project uses:
- **Package**: `bensampo/laravel-enum` (^6.12)
- **Native PHP Enums**: PHP 8.1+
- **Filament Integration**: v4.x

## Best Practices

✅ **DO:**
- Use `EnumHelpers` trait for all enums
- Implement `HasLabel` and `HasColor` for Filament
- Add translations for all labels
- Write comprehensive tests
- Document business logic methods
- Use type hints everywhere
- Follow naming conventions

❌ **DON'T:**
- Hardcode enum values as strings
- Skip validation on enum fields
- Forget to add translations
- Mix enum instances with string values
- Create enums without the helpers trait
- Use dynamic enum values

## Migration Guide

### From String Constants
```php
// Before
class Project extends Model
{
    const STATUS_ACTIVE = 'active';
}

// After
use App\Enums\ProjectStatus;

class Project extends Model
{
    protected $casts = [
        'status' => ProjectStatus::class,
    ];
}
```

### From Old Enum Package
If migrating from an older enum implementation:
1. Add `use EnumHelpers;` trait
2. Replace custom `options()` with `toSelectArray()`
3. Update validation rules to use `EnumValue` rule
4. Add tests for new helper methods

## Support

For questions or issues:
1. Check the documentation in `docs/`
2. Review examples in `app/Enums/Examples/`
3. Look at tests in `tests/Unit/Enums/`
4. Refer to the package docs: https://github.com/BenSampo/laravel-enum

## Contributing

When adding new enums:
1. Follow the conventions above
2. Add comprehensive tests
3. Document any business logic
4. Add translation keys
5. Run `composer lint` before committing
6. Update this README if adding new patterns
