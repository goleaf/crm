# ViewCompany Test Coverage

## Overview
Comprehensive test suite for `app/Filament/Resources/CompanyResource/Pages/ViewCompany.php` covering the infolist display, color callback fixes, and edge cases.

## Recent Changes Tested
The test suite validates the account team member badge color implementation:
- Color callbacks use `fn (?array $state): string => $state['color'] ?? 'gray'` ✅ CORRECT
- Badge colors are correctly retrieved from the nested array structure where state contains both 'label' and 'color' keys
- The state mapping creates nested arrays: `'role' => ['label' => ..., 'color' => ...]` and `'access' => ['label' => ..., 'color' => ...]`
- **Important**: `$state` receives the entire nested array, NOT just the label string
- See `docs/ui-ux/viewcompany-badge-colors.md` for detailed explanation

## Test Coverage

### 1. Page Rendering (4 tests)
- ✅ Basic page rendering with default data
- ✅ Minimal data (null/empty fields)
- ✅ Full data with all fields populated
- ✅ Cross-tenant access prevention

### 2. Account Team Members Display (6 tests)
- ✅ Display team members with roles and access levels
- ✅ Correct badge colors for roles (using `record['role_color']`)
- ✅ Correct badge colors for access levels (using `record['access_color']`)
- ✅ Hide section when no members exist
- ✅ Handle null/missing user gracefully
- ✅ Display multiple team members

### 3. Child Companies Display (3 tests)
- ✅ Display child companies
- ✅ Hide section when no children exist
- ✅ Display with account type badges

### 4. Annual Revenue Display (3 tests)
- ✅ Display latest annual revenue with year
- ✅ Fallback to company revenue field
- ✅ Handle null revenue gracefully

### 5. Address Display (3 tests)
- ✅ Display billing address
- ✅ Display shipping address
- ✅ Handle empty addresses with placeholders

### 6. Header Actions (3 tests)
- ✅ Edit action exists
- ✅ Delete action exists
- ✅ Delete action soft-deletes company

### 7. Favicon Fetch on Edit (2 tests)
- ✅ Dispatch job when domain custom field changes
- ✅ Don't dispatch job when domain unchanged

### 8. Relation Managers (6 tests)
- ✅ Annual Revenues relation manager
- ✅ Cases relation manager
- ✅ People relation manager
- ✅ Tasks relation manager
- ✅ Notes relation manager
- ✅ Activities relation manager

### 9. Edge Cases (4 tests)
- ✅ Null enum values display placeholder
- ✅ Empty string values handled
- ✅ Large employee count formatting (1,000,000)
- ✅ Zero employee count display

## Total: 37 Tests

## Key Testing Patterns

### Enum Color Callbacks
The tests verify that enum color methods work correctly:
```php
// Enums must have both interface methods AND wrapper methods
AccountTeamRole::ACCOUNT_MANAGER->color() // Returns string
AccountTeamAccessLevel::VIEW->color() // Returns string
```

### Tenancy Testing
All tests use proper team setup:
```php
beforeEach(function (): void {
    $this->team = Team::factory()->create();
    $this->user = User::factory()->create();
    $this->team->users()->attach($this->user);
    actingAs($this->user);
    $this->user->switchTeam($this->team);
});
```

### Livewire Component Testing
```php
Livewire::test(CompanyResource\Pages\ViewCompany::class, ['record' => $company->id])
    ->assertSuccessful()
    ->assertSee('Expected Text');
```

## Running the Tests

```bash
# Run all ViewCompany tests
vendor/bin/pest tests/Feature/Filament/Resources/CompanyResource/ViewCompanyTest.php

# Run specific test group
vendor/bin/pest tests/Feature/Filament/Resources/CompanyResource/ViewCompanyTest.php --filter="Account Team Members"

# Run with coverage
vendor/bin/pest tests/Feature/Filament/Resources/CompanyResource/ViewCompanyTest.php --coverage
```

## Related Files

- **Source**: `app/Filament/Resources/CompanyResource/Pages/ViewCompany.php`
- **Enums**: 
  - `app/Enums/AccountTeamRole.php`
  - `app/Enums/AccountTeamAccessLevel.php`
  - `app/Enums/AccountType.php`
  - `app/Enums/Industry.php`
- **Models**:
  - `app/Models/Company.php`
  - `app/Models/AccountTeamMember.php`
  - `app/Models/CompanyRevenue.php`
- **Steering**: `.kiro/steering/enum-conventions.md`
