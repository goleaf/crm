# Laravel Validation Enhancements

> **Quick Reference**: This is the comprehensive validation guide. For concise rules, see `.kiro/steering/laravel-precognition.md` and `.kiro/steering/translations.md`.

## Overview
This document outlines modern Laravel validation patterns and enhancements integrated into the CRM application, following Laravel 11+ best practices.

## Core Validation Improvements

### 1. Nested Array Validation
Laravel now supports cleaner nested array validation syntax:

```php
// Old way
'users.*.name' => 'required',
'users.*.email' => 'required|email',

// New way (Laravel 11+)
'users' => 'array',
'users.*.name' => 'required|string|max:255',
'users.*.email' => 'required|email|max:255',
```

### 2. Conditional Validation with `when()`
Use fluent conditional validation instead of complex closures:

```php
use Illuminate\Validation\Rule;

public function rules(): array
{
    return [
        'email' => [
            'required',
            'email',
            Rule::when(
                $this->isUpdate(),
                Rule::unique('users')->ignore($this->user),
                Rule::unique('users')
            ),
        ],
    ];
}
```

### 3. Custom Validation Rules with Invokable Classes
Modern validation rules use invokable classes for better organization:

```php
namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class ValidPhoneNumber implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! preg_match('/^\+?[1-9]\d{1,14}$/', $value)) {
            $fail(__('validation.phone_invalid'));
        }
    }
}
```

### 4. Enum Validation
Laravel 11+ provides native enum validation:

```php
use App\Enums\LeadStatus;
use Illuminate\Validation\Rule;

public function rules(): array
{
    return [
        'status' => ['required', Rule::enum(LeadStatus::class)],
    ];
}
```

### 5. Multiple Field Validation
Validate multiple fields together with `Rule::requiredIf()`:

```php
use Illuminate\Validation\Rule;

public function rules(): array
{
    return [
        'shipping_address' => [
            Rule::requiredIf(fn () => $this->input('needs_shipping') === true),
        ],
        'billing_address' => [
            Rule::requiredIf(fn () => $this->input('needs_billing') === true),
        ],
    ];
}
```

### 6. Precognition Validation
Real-time validation with Laravel Precognition:

```php
// In Form Request
public function rules(): array
{
    return [
        'email' => [
            'required',
            'email',
            Rule::unique('users')->ignore($this->user),
        ],
    ];
}

// Frontend automatically validates on blur/change
// See .kiro/steering/laravel-precognition.md for details
```

### 7. Validation Attributes
Define human-readable attribute names:

```php
public function attributes(): array
{
    return [
        'email' => __('app.labels.email'),
        'phone' => __('app.labels.phone'),
        'company_id' => __('app.labels.company'),
    ];
}
```

### 8. Custom Error Messages
Provide context-specific error messages:

```php
public function messages(): array
{
    return [
        'email.required' => __('validation.email_required'),
        'email.unique' => __('validation.email_already_exists'),
        'company_id.exists' => __('validation.company_not_found'),
    ];
}
```

## Advanced Validation Patterns

### Dependent Field Validation
```php
use Illuminate\Validation\Rule;

public function rules(): array
{
    return [
        'country_id' => ['required', 'exists:countries,id'],
        'state_id' => [
            'nullable',
            Rule::exists('states', 'id')
                ->where('country_id', $this->input('country_id')),
        ],
        'city_id' => [
            'nullable',
            Rule::exists('cities', 'id')
                ->where('state_id', $this->input('state_id')),
        ],
    ];
}
```

### Complex Conditional Validation
```php
use Illuminate\Validation\Rule;

public function rules(): array
{
    return [
        'lead_type' => ['required', 'in:individual,company'],
        'person_name' => [
            Rule::requiredIf(fn () => $this->input('lead_type') === 'individual'),
            'nullable',
            'string',
            'max:255',
        ],
        'company_name' => [
            Rule::requiredIf(fn () => $this->input('lead_type') === 'company'),
            'nullable',
            'string',
            'max:255',
        ],
    ];
}
```

### Array Validation with Nested Rules
```php
public function rules(): array
{
    return [
        'line_items' => ['required', 'array', 'min:1'],
        'line_items.*.product_id' => ['required', 'exists:products,id'],
        'line_items.*.quantity' => ['required', 'integer', 'min:1'],
        'line_items.*.price' => ['required', 'numeric', 'min:0'],
        'line_items.*.discount' => ['nullable', 'numeric', 'min:0', 'max:100'],
    ];
}
```

### File Upload Validation
```php
use Illuminate\Validation\Rules\File;

public function rules(): array
{
    return [
        'avatar' => [
            'nullable',
            File::image()
                ->max(2048) // 2MB
                ->dimensions(Rule::dimensions()->maxWidth(1000)->maxHeight(1000)),
        ],
        'document' => [
            'required',
            File::types(['pdf', 'doc', 'docx'])
                ->max(10240), // 10MB
        ],
    ];
}
```

### Password Validation
```php
use Illuminate\Validation\Rules\Password;

public function rules(): array
{
    return [
        'password' => [
            'required',
            'confirmed',
            Password::min(8)
                ->letters()
                ->mixedCase()
                ->numbers()
                ->symbols()
                ->uncompromised(),
        ],
    ];
}
```

> Passwords are also checked against common-password blocklists via `LaraUtilX\Rules\RejectCommonPasswords` in `PasswordValidationRules`, layered on top of `Password::default()` and the existing zxcvbn strength rule.

## Validation Helpers

### ValidationHelper Class
```php
namespace App\Support\Helpers;

use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class ValidationHelper
{
    /**
     * Validate data and return validated data or throw exception.
     */
    public static function validate(array $data, array $rules, array $messages = []): array
    {
        $validator = Validator::make($data, $rules, $messages);
        
        if ($validator->fails()) {
            throw new ValidationException($validator);
        }
        
        return $validator->validated();
    }
    
    /**
     * Check if data passes validation without throwing.
     */
    public static function passes(array $data, array $rules): bool
    {
        return Validator::make($data, $rules)->passes();
    }
    
    /**
     * Get validation errors without throwing.
     */
    public static function errors(array $data, array $rules): array
    {
        $validator = Validator::make($data, $rules);
        $validator->validate();
        
        return $validator->errors()->toArray();
    }
}
```

## Filament Integration

### Form Validation with Precognition
```php
use Filament\Forms\Components\TextInput;
use App\Rules\ValidPhoneNumber;

TextInput::make('phone')
    ->label(__('app.labels.phone'))
    ->rules([new ValidPhoneNumber])
    ->precognitive() // Enable real-time validation
    ->live(onBlur: true);
```

### Custom Validation Messages in Filament
```php
TextInput::make('email')
    ->label(__('app.labels.email'))
    ->email()
    ->unique('users', 'email', ignoreRecord: true)
    ->validationMessages([
        'unique' => __('validation.email_already_exists'),
        'email' => __('validation.email_invalid'),
    ]);
```

## Testing Validation

### Feature Tests
```php
use function Pest\Laravel\postJson;

it('validates required fields', function () {
    postJson('/api/contacts', [])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['name', 'email', 'company_id']);
});

it('validates email format', function () {
    postJson('/api/contacts', [
        'name' => 'John Doe',
        'email' => 'invalid-email',
        'company_id' => 1,
    ])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['email']);
});

it('validates unique email within team', function () {
    $team = Team::factory()->create();
    $user = User::factory()->create(['current_team_id' => $team->id]);
    
    People::factory()->create([
        'email' => 'john@example.com',
        'team_id' => $team->id,
    ]);
    
    actingAs($user)
        ->postJson('/api/contacts', [
            'name' => 'Jane Doe',
            'email' => 'john@example.com',
            'company_id' => 1,
        ])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['email']);
});
```

### Unit Tests for Custom Rules
```php
use App\Rules\ValidPhoneNumber;
use Illuminate\Support\Facades\Validator;

it('validates phone numbers correctly', function () {
    $rule = new ValidPhoneNumber;
    
    $validator = Validator::make(
        ['phone' => '+1234567890'],
        ['phone' => $rule]
    );
    
    expect($validator->passes())->toBeTrue();
});

it('rejects invalid phone numbers', function () {
    $rule = new ValidPhoneNumber;
    
    $validator = Validator::make(
        ['phone' => 'invalid'],
        ['phone' => $rule]
    );
    
    expect($validator->fails())->toBeTrue();
});
```

## Best Practices

### DO:
- ✅ Use Form Requests for all validation logic
- ✅ Implement custom validation rules as invokable classes
- ✅ Use `Rule::enum()` for enum validation
- ✅ Provide translated error messages
- ✅ Use `attributes()` for human-readable field names
- ✅ Test validation rules thoroughly
- ✅ Use Precognition for real-time validation
- ✅ Validate nested arrays with proper syntax
- ✅ Use conditional validation with `Rule::when()`
- ✅ Implement dependent field validation

### DON'T:
- ❌ Put validation logic in controllers
- ❌ Skip validation on API endpoints
- ❌ Hardcode error messages
- ❌ Forget to validate nested data
- ❌ Ignore edge cases in custom rules
- ❌ Skip testing validation logic
- ❌ Use string-based validation when objects are available
- ❌ Forget to handle file upload validation

## Migration Checklist

When updating existing validation:

1. ✅ Convert string rules to array syntax
2. ✅ Extract complex validation to custom rules
3. ✅ Add `attributes()` method for translations
4. ✅ Add `messages()` method for custom errors
5. ✅ Use `Rule::enum()` for enum fields
6. ✅ Add Precognition support where appropriate
7. ✅ Write tests for validation logic
8. ✅ Update translation files
9. ✅ Document custom validation rules
10. ✅ Review and update API documentation

## Related Documentation
- `.kiro/steering/laravel-precognition.md` - Real-time validation
- `.kiro/steering/translations.md` - Translation conventions
- `.kiro/steering/testing-standards.md` - Testing patterns
- `docs/helper-functions-guide.md` - Validation helpers
- Laravel Validation Docs: https://laravel.com/docs/validation
