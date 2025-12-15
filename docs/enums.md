# Enum Integration Guide

## Overview

This project uses native PHP 8.1+ enums enhanced with the BenSampo Laravel Enum package for additional functionality. All enums follow consistent patterns for Filament integration, validation, and database casting.

## Package Integration

We've integrated `bensampo/laravel-enum` v6.12+ to provide:
- Enhanced validation rules
- Array conversion helpers
- Database casting utilities
- IDE-friendly methods

## Base Trait: EnumHelpers

All enums should use the `App\Enums\Concerns\EnumHelpers` trait for consistent functionality:

```php
use App\Enums\Concerns\EnumHelpers;

enum ProjectStatus: string implements HasColor, HasLabel
{
    use EnumHelpers;
    
    case PLANNING = 'planning';
    case ACTIVE = 'active';
    // ...
}
```

### Available Methods

#### Static Methods

```php
// Get all values
ProjectStatus::values(); // ['planning', 'active', 'on_hold', ...]

// Get all names
ProjectStatus::names(); // ['PLANNING', 'ACTIVE', 'ON_HOLD', ...]

// Safe conversion from value
ProjectStatus::fromValueOrNull('active'); // ProjectStatus::ACTIVE
ProjectStatus::fromValueOrNull('invalid'); // null

// Convert from name
ProjectStatus::fromName('ACTIVE'); // ProjectStatus::ACTIVE

// Get random instance
ProjectStatus::random(); // Random case

// Validation
ProjectStatus::isValid('active'); // true
ProjectStatus::isValid('invalid'); // false

// Validation rules
ProjectStatus::rule(); // 'in:planning,active,on_hold,completed,cancelled'
ProjectStatus::rules(); // ['in:planning,active,on_hold,completed,cancelled']

// Collection
ProjectStatus::collect(); // Collection of all cases

// Array conversions
ProjectStatus::toSelectArray(); // ['planning' => 'Planning', 'active' => 'Active', ...]
ProjectStatus::toArray(); // [['value' => 'planning', 'label' => 'Planning'], ...]

// Checks
ProjectStatus::hasValue('active'); // true
ProjectStatus::hasName('ACTIVE'); // true
ProjectStatus::count(); // 5
```

## Filament Integration

### Resource Forms

```php
use Filament\Forms\Components\Select;

Select::make('status')
    ->label(__('app.labels.status'))
    ->options(ProjectStatus::toSelectArray())
    ->default(ProjectStatus::PLANNING->value)
    ->required()
    ->native(false);
```

### Table Columns

```php
use Filament\Tables\Columns\TextColumn;

TextColumn::make('status')
    ->label(__('app.labels.status'))
    ->badge()
    ->color(fn (ProjectStatus $state): string => $state->getColor())
    ->icon(fn (ProjectStatus $state): string => $state->getIcon())
    ->formatStateUsing(fn (ProjectStatus $state): string => $state->getLabel());
```

### Filters

```php
use Filament\Tables\Filters\SelectFilter;

SelectFilter::make('status')
    ->label(__('app.labels.status'))
    ->options(ProjectStatus::toSelectArray())
    ->multiple();
```

## Model Integration

### Casting

Use native Laravel enum casting (recommended):

```php
use App\Enums\ProjectStatus;

class Project extends Model
{
    protected $casts = [
        'status' => ProjectStatus::class,
    ];
}
```

Or use the custom `EnumCast` for enhanced error handling:

```php
use App\Casts\EnumCast;
use App\Enums\ProjectStatus;

class Project extends Model
{
    protected $casts = [
        'status' => EnumCast::class.':'.ProjectStatus::class,
    ];
}
```

### Usage in Models

```php
// Create with enum
$project = Project::create([
    'name' => 'New Project',
    'status' => ProjectStatus::PLANNING,
]);

// Update with enum
$project->update([
    'status' => ProjectStatus::ACTIVE,
]);

// Query with enum
$activeProjects = Project::where('status', ProjectStatus::ACTIVE)->get();

// Access enum methods
if ($project->status->isActive()) {
    // Do something
}

// Check transitions
if ($project->status->canTransitionTo(ProjectStatus::COMPLETED)) {
    $project->update(['status' => ProjectStatus::COMPLETED]);
}
```

## Validation

### Using EnumValue Rule

```php
use App\Rules\EnumValue;
use App\Enums\ProjectStatus;

$request->validate([
    'status' => ['required', new EnumValue(ProjectStatus::class)],
]);
```

### Using Built-in Helpers

```php
use App\Enums\ProjectStatus;
use Illuminate\Validation\Rule;

$request->validate([
    'status' => ['required', ProjectStatus::rule()],
    // Or
    'status' => ['required', Rule::in(ProjectStatus::values())],
]);
```

### Form Request Example

```php
use App\Enums\ProjectStatus;
use App\Rules\EnumValue;

class UpdateProjectRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'status' => ['required', new EnumValue(ProjectStatus::class)],
            'description' => ['nullable', 'string'],
        ];
    }
}
```

## Creating New Enums

### Basic Enum Template

```php
<?php

declare(strict_types=1);

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

### Translation Keys

Add to `lang/en/app.php`:

```php
'your_enum' => [
    'option_one' => 'Option One',
    'option_two' => 'Option Two',
],
```

### Database Migration

```php
$table->string('status')->default(YourEnum::OPTION_ONE->value);
```

## Testing Enums

### Unit Tests

```php
use App\Enums\ProjectStatus;

it('has correct values', function () {
    expect(ProjectStatus::values())->toBe([
        'planning',
        'active',
        'on_hold',
        'completed',
        'cancelled',
    ]);
});

it('converts to select array', function () {
    $array = ProjectStatus::toSelectArray();
    
    expect($array)->toHaveKey('planning');
    expect($array['planning'])->toBe('Planning');
});

it('validates transitions', function () {
    $status = ProjectStatus::PLANNING;
    
    expect($status->canTransitionTo(ProjectStatus::ACTIVE))->toBeTrue();
    expect($status->canTransitionTo(ProjectStatus::COMPLETED))->toBeFalse();
});

it('provides validation rules', function () {
    $rule = ProjectStatus::rule();
    
    expect($rule)->toContain('in:');
    expect($rule)->toContain('planning');
});
```

### Feature Tests

```php
use App\Enums\ProjectStatus;
use App\Models\Project;

it('can create project with enum status', function () {
    $project = Project::factory()->create([
        'status' => ProjectStatus::PLANNING,
    ]);
    
    expect($project->status)->toBe(ProjectStatus::PLANNING);
});

it('validates enum values in requests', function () {
    $response = $this->postJson('/api/projects', [
        'name' => 'Test Project',
        'status' => 'invalid_status',
    ]);
    
    $response->assertJsonValidationErrors('status');
});
```

## Best Practices

### DO:
- ✅ Use `EnumHelpers` trait for all enums
- ✅ Implement `HasLabel` and `HasColor` for Filament enums
- ✅ Add translation keys for all enum labels
- ✅ Use native enum casting in models
- ✅ Validate enum values in form requests
- ✅ Add business logic methods (like `canTransitionTo()`)
- ✅ Document enum purpose and usage

### DON'T:
- ❌ Hardcode enum values as strings
- ❌ Skip validation on enum fields
- ❌ Forget to add translations
- ❌ Mix enum instances with string values
- ❌ Create enums without the `EnumHelpers` trait

## Migration from String Constants

If migrating from string constants:

```php
// Before
class Project extends Model
{
    const STATUS_PLANNING = 'planning';
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

Update queries:

```php
// Before
Project::where('status', Project::STATUS_ACTIVE)->get();

// After
Project::where('status', ProjectStatus::ACTIVE)->get();
```

## Additional Resources

- [BenSampo Laravel Enum Documentation](https://github.com/BenSampo/laravel-enum)
- [PHP 8.1 Enums Documentation](https://www.php.net/manual/en/language.enumerations.php)
- [Filament Enum Integration](https://filamentphp.com/docs/4.x/support/enums)
