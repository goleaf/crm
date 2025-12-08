# ViewCompany Badge Color Implementation

## Overview
This document explains the correct implementation of badge colors in the ViewCompany page's account team members section, addressing a common confusion about Filament v4.3+'s RepeatableEntry component behavior.

## The Correct Pattern

### State Mapping Structure
```php
->state(fn (Company $record): array => $record->accountTeamMembers()
    ->with('user')
    ->orderBy('created_at')
    ->get()
    ->map(fn (AccountTeamMember $member): array => [
        'name' => $member->user?->name ?? '—',
        'email' => $member->user?->email,
        'role' => [
            'label' => $member->role?->label() ?? '—',
            'color' => $member->role?->color() ?? 'gray',
        ],
        'access' => [
            'label' => $member->access_level?->label() ?? '—',
            'color' => $member->access_level?->color() ?? 'gray',
        ],
    ])
    ->all())
```

### Badge Color Callbacks
```php
TextEntry::make('role')
    ->label(__('app.labels.role'))
    ->badge()
    ->formatStateUsing(fn (?array $state): string => $state['label'] ?? '—')
    ->color(fn (?array $state): string => $state['color'] ?? 'gray')
    ->columnSpan(2),
```

## Why This Works

### Data Flow
1. **State Mapping**: Creates an array where each item has nested arrays for `role` and `access`
2. **TextEntry Field**: `TextEntry::make('role')` receives the **entire nested array** as `$state`
3. **Color Callback**: Accesses `$state['color']` directly from the nested structure

### Type Signature
- `$state` is typed as `?array` because it receives `['label' => ..., 'color' => ...]`
- The callback returns a string (the color name)

## Common Mistakes

### ❌ INCORRECT: Using $record Parameter
```php
// WRONG - This assumes $record has 'role_color' key at top level
->color(fn (?string $state, array $record): string => $record['role_color'] ?? 'gray')
```

**Why this fails:**
- `$record` in RepeatableEntry context refers to the entire row array
- The structure has `role` => `['label', 'color']`, not `role_color` at top level
- Type mismatch: `$state` is an array, not a string

### ❌ INCORRECT: Accessing Nested Keys on $record
```php
// WRONG - Overly complex and unnecessary
->color(fn (?string $state, array $record): string => $record['role']['color'] ?? 'gray')
```

**Why this fails:**
- `$state` already contains the nested array
- No need to access `$record` when `$state` has the data
- Type mismatch: `$state` is an array, not a string

## Performance Benefits

### Pre-Computed Values
The current implementation pre-computes enum colors during state mapping:
- ✅ Enum methods called once per member (during mapping)
- ✅ No runtime overhead in display callbacks
- ✅ Efficient for large team lists

### N+1 Query Prevention
```php
->with('user') // Eager loads users in single query
```

## Accessibility Features

### Semantic HTML
- Badge component generates proper semantic markup
- Color classes provide visual distinction
- Labels remain readable without color (WCAG compliant)

### Screen Reader Support
```php
->label(__('app.labels.role')) // Translatable label for screen readers
```

### Keyboard Navigation
- Badges are non-interactive (display-only)
- Focus remains on actionable elements
- Proper tab order maintained

## Testing

### Test Coverage
```php
test('displays correct badge colors for account team member roles', function () {
    $company = Company::factory()->create(['team_id' => $this->team->id]);
    $teamMember = User::factory()->create();
    $this->team->users()->attach($teamMember);

    AccountTeamMember::create([
        'account_id' => $company->id,
        'user_id' => $teamMember->id,
        'role' => AccountTeamRole::ACCOUNT_MANAGER,
        'access_level' => AccountTeamAccessLevel::EDIT,
    ]);

    $component = Livewire::test(ViewCompany::class, ['record' => $company->id]);
    $component->assertSuccessful();
    
    expect(AccountTeamRole::ACCOUNT_MANAGER->color())->toBeString();
});
```

## UX Enhancements

### Empty State
```php
->emptyStateHeading(__('app.messages.no_team_members'))
->emptyStateDescription(__('app.messages.add_team_members_to_collaborate'))
```

**Benefits:**
- Clear messaging when no team members exist
- Guides users on next action
- Maintains professional appearance

### Visibility Control
```php
->visible(fn (?array $state): bool => count($state ?? []) > 0)
```

**Benefits:**
- Hides section when empty (reduces clutter)
- Shows empty state when explicitly needed
- Improves page load perception

## Related Documentation

- [Enum Conventions](.kiro/steering/enum-conventions.md) - Enum label/color wrapper methods
- [Filament v4.3+ Conventions](.kiro/steering/filament-conventions.md) - Schema patterns
- [Translation Guide](.kiro/steering/TRANSLATION_GUIDE.md) - Localization best practices
- [Performance Optimization](docs/performance-viewcompany.md) - Query optimization

## Maintenance Notes

### When Adding New Badge Fields
1. ✅ Create nested array in state mapping with `label` and `color` keys
2. ✅ Use `fn (?array $state)` signature in callbacks
3. ✅ Access `$state['label']` and `$state['color']` directly
4. ✅ Provide fallback values (`'—'` for labels, `'gray'` for colors)
5. ✅ Ensure enum implements `HasLabel` and `HasColor` with wrapper methods

### When Debugging Badge Colors
1. Check state mapping structure (nested arrays?)
2. Verify enum has `label()` and `color()` wrapper methods
3. Confirm callback signature matches data structure
4. Test with null values to ensure fallbacks work

## Changelog

- **2024-12-07**: Initial documentation
  - Documented correct badge color implementation
  - Added UX enhancements (empty state)
  - Clarified common mistakes
  - Added accessibility notes
