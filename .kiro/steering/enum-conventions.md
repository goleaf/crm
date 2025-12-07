---
inclusion: always
---

# Enum Conventions & Best Practices

## Wrapper Methods (CRITICAL)

### Required Methods for Filament Enums
**All enums implementing `HasLabel` MUST provide a `label()` wrapper method.**
**All enums implementing `HasColor` MUST provide a `color()` wrapper method.**

This prevents "Call to undefined method" errors when enums are used in Filament table columns with `->formatStateUsing()` or `->color()` callbacks.

**Status:** ✅ All 67 enum files now have proper wrapper methods (58 with `label()`, 34 with `color()`).

### Standard Pattern
```php
<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum MyEnum: string implements HasColor, HasLabel
{
    case VALUE = 'value';
    
    // Required by HasLabel interface
    public function getLabel(): string
    {
        return __('enums.my_enum.value');
    }
    
    // Required wrapper for Filament table callbacks
    public function label(): string
    {
        return $this->getLabel();
    }
    
    // Required by HasColor interface
    public function getColor(): string
    {
        return 'primary';
    }
    
    // Required wrapper for Filament table callbacks
    public function color(): string
    {
        return $this->getColor();
    }
}
```

## Why Wrapper Methods Are Required

Filament table columns often use closures that call methods directly on enum instances:

```php
// This will fail without the wrapper method
TextColumn::make('status')
    ->color(fn (MyEnum $state): string => $state->color())
    ->formatStateUsing(fn (MyEnum $state): string => $state->label());
```

Without the `label()` and `color()` wrapper methods, you'll get:
- `Call to undefined method App\Enums\MyEnum::color()`
- `Call to undefined method App\Enums\MyEnum::label()`

## Examples of Correct Implementation

### Enums with Proper Wrappers
- `NoteCategory` - Has both `label()` and `color()` wrappers
- `NoteVisibility` - Has both `label()` and `color()` wrappers
- `LeadStatus` - Has both `label()` and `color()` wrappers
- `LeadGrade` - Has both `label()` and `color()` wrappers
- `LeadNurtureStatus` - Has both `label()` and `color()` wrappers
- `AccountType` - Has both `label()` and `color()` wrappers
- `EmployeeStatus` - Has both `label()` and `color()` wrappers

## Translation Integration

Always use translation keys in `getLabel()` for consistency:

```php
public function getLabel(): string
{
    return match ($this) {
        self::ACTIVE => __('enums.status.active'),
        self::INACTIVE => __('enums.status.inactive'),
    };
}
```

Ensure corresponding translation entries exist in `lang/*/enums.php`:

```php
// lang/en/enums.php
return [
    'status' => [
        'active' => 'Active',
        'inactive' => 'Inactive',
    ],
];
```

**Status:** ✅ All 349 enum translation keys are defined in `lang/en/enums.php` covering:
- Account & Team enums (account_type, account_team_role, account_team_access_level)
- Lead Management (lead_status, lead_grade, lead_nurture_status, lead_source, lead_assignment_strategy)
- Sales & Orders (invoice_status, order_status, quote_status, purchase_order_status)
- Support & Cases (case_status, case_priority, case_channel, case_type)
- Process & Workflow (process_status, process_execution_status, workflow_trigger_type, workflow_condition_operator)
- Knowledge Base (article_status, article_visibility, faq_status, comment_status, approval_status)
- HR & Time Off (employee_status, time_off_status, time_off_type)
- Notes & Categories (note_category, note_visibility, note_history_event)
- System & Technical (extension_type, hook_event, bounce_type, calendar_sync_status)
- Custom Fields (company_field, people_field, opportunity_field, task_field, note_field)

## Checklist for New Enums

When creating a new enum that implements `HasLabel` or `HasColor`:

1. ✅ Implement the required interface methods (`getLabel()`, `getColor()`)
2. ✅ Add wrapper methods (`label()`, `color()`)
3. ✅ Use translation keys in `getLabel()`
4. ✅ Add translation entries to `lang/*/enums.php`
5. ✅ Test in Filament table columns to verify no method errors

## Common Mistakes to Avoid

❌ **DON'T** implement only the interface methods:
```php
// WRONG - Missing wrapper methods
enum Status implements HasLabel, HasColor
{
    public function getLabel(): string { ... }
    public function getColor(): string { ... }
    // Missing label() and color() wrappers!
}
```

✅ **DO** implement both interface methods AND wrappers:
```php
// CORRECT - Has all required methods
enum Status implements HasLabel, HasColor
{
    public function getLabel(): string { ... }
    public function label(): string { return $this->getLabel(); }
    
    public function getColor(): string { ... }
    public function color(): string { return $this->getColor(); }
}
```

## Fixing Existing Enums

If you encounter a "Call to undefined method" error on an enum:

1. Locate the enum file in `app/Enums/`
2. Check if it implements `HasLabel` or `HasColor`
3. Add the missing wrapper method(s)
4. Update this steering file if you discover a pattern that should be documented

## Related Files

- Enum implementations: `app/Enums/*.php`
- Translation files: `lang/*/enums.php`
- Filament resources using enums: `app/Filament/Resources/*Resource.php`
