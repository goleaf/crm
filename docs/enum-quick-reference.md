# Enum Quick Reference Card

## Creating a New Enum

```php
<?php

namespace App\Enums;

use App\Enums\Concerns\EnumHelpers;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum YourEnum: string implements HasColor, HasLabel
{
    use EnumHelpers;

    case OPTION_ONE = 'option_one';
    case OPTION_TWO = 'option_two';

    public function getLabel(): string
    {
        return match ($this) {
            self::OPTION_ONE => __('app.your_enum.option_one'),
            self::OPTION_TWO => __('app.your_enum.option_two'),
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::OPTION_ONE => 'primary',
            self::OPTION_TWO => 'success',
        };
    }

    public function getIcon(): string
    {
        return match ($this) {
            self::OPTION_ONE => 'heroicon-o-star',
            self::OPTION_TWO => 'heroicon-o-check',
        };
    }
}
```

## Common Operations

| Operation | Code |
|-----------|------|
| Get all values | `YourEnum::values()` |
| Get all names | `YourEnum::names()` |
| Convert from value | `YourEnum::from('value')` |
| Safe convert | `YourEnum::fromValueOrNull('value')` |
| Convert from name | `YourEnum::fromName('NAME')` |
| Random case | `YourEnum::random()` |
| Validate value | `YourEnum::isValid('value')` |
| Get validation rule | `YourEnum::rule()` |
| Get as collection | `YourEnum::collect()` |
| Get for select | `YourEnum::toSelectArray()` |
| Count cases | `YourEnum::count()` |

## Filament Integration

### Form Select
```php
Select::make('status')
    ->options(YourEnum::toSelectArray())
    ->default(YourEnum::OPTION_ONE->value);
```

### Table Column
```php
TextColumn::make('status')
    ->badge()
    ->color(fn (YourEnum $state) => $state->getColor());
```

### Filter
```php
SelectFilter::make('status')
    ->options(YourEnum::toSelectArray());
```

## Model Integration

```php
class YourModel extends Model
{
    protected $casts = [
        'status' => YourEnum::class,
    ];
}

// Usage
$model->status = YourEnum::OPTION_ONE;
$model->save();

if ($model->status === YourEnum::OPTION_ONE) {
    // Do something
}
```

## Validation

```php
use App\Rules\EnumValue;

// Custom rule
$request->validate([
    'status' => ['required', new EnumValue(YourEnum::class)],
]);

// Built-in helper
$request->validate([
    'status' => ['required', YourEnum::rule()],
]);
```

## Testing

```php
it('has correct values', function () {
    expect(YourEnum::values())->toBe(['option_one', 'option_two']);
});

it('converts to select array', function () {
    $array = YourEnum::toSelectArray();
    expect($array)->toHaveKey('option_one');
});

it('validates values', function () {
    expect(YourEnum::isValid('option_one'))->toBeTrue();
    expect(YourEnum::isValid('invalid'))->toBeFalse();
});
```

## Translations

Add to `lang/en/app.php`:
```php
'your_enum' => [
    'option_one' => 'Option One',
    'option_two' => 'Option Two',
],
```

## Common Patterns

### State Checks
```php
public function isActive(): bool
{
    return $this === self::ACTIVE;
}
```

### Transitions
```php
public function canTransitionTo(self $status): bool
{
    return in_array($status, $this->allowedTransitions(), true);
}
```

### Scopes
```php
public function scopeWithStatus($query, YourEnum $status)
{
    return $query->where('status', $status);
}
```

## Tips

✅ **DO:**
- Use `EnumHelpers` trait
- Implement `HasLabel` and `HasColor`
- Add translations
- Write tests
- Type-hint enum parameters

❌ **DON'T:**
- Hardcode enum values as strings
- Skip validation
- Forget translations
- Mix enums with strings

## Resources

- Full docs: `docs/enums.md`
- Examples: `docs/enum-usage-examples.md`
- Template: `app/Enums/Examples/ExampleEnum.php`
- Tests: `tests/Unit/Enums/ProjectStatusTest.php`
