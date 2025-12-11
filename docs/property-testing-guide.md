# Property Testing Guide

## Overview

Property testing is a powerful testing methodology that validates system behaviors across many different inputs and scenarios. This guide covers how to implement and use property tests in the Relaticle CRM system.

## What is Property Testing?

Property testing validates that certain properties (invariants) hold true across a wide range of inputs. Instead of testing specific examples, property tests generate many random inputs and verify that the system behaves correctly for all of them.

### Benefits

1. **Comprehensive Coverage**: Tests many scenarios automatically
2. **Edge Case Discovery**: Finds edge cases you might not think of
3. **Regression Prevention**: Catches regressions across the entire input space
4. **Documentation**: Properties serve as executable specifications

## Property Testing in Relaticle CRM

### Accounts Module Properties

The accounts module implements several property tests to validate core business logic:

#### Property 28: Account Type Change Audit Trail

**Specification**: For any account where the account type is changed, the system should update the account and preserve the change in the activity history.

**Implementation**:
```php
// tests/Unit/Models/CompanyTest.php
test('account type changes are preserved in activity history', function (): void {
    $team = Team::factory()->create();
    $user = User::factory()->create(['current_team_id' => $team->getKey()]);
    $this->actingAs($user);

    // Create a company with an initial account type
    $initialType = fake()->randomElement(AccountType::cases());
    $company = Company::factory()->create([
        'team_id' => $team->getKey(),
        'account_type' => $initialType,
    ]);

    // Clear any creation activities
    $company->activities()->delete();

    // Change the account type to a different random type
    $availableTypes = array_filter(
        AccountType::cases(),
        fn (AccountType $type): bool => $type !== $initialType,
    );
    $newType = fake()->randomElement($availableTypes);

    // Update using the enum value string to ensure it's detected as a change
    $company->account_type = $newType;
    $company->save();

    // Verify the change was persisted
    $company->refresh();
    expect($company->account_type)->toBe($newType);

    // Verify activity log exists for the update
    $activities = $company->activities()->where('event', 'updated')->get();
    expect($activities->isNotEmpty())->toBeTrue('Expected at least one update activity');

    // Find the activity that logged the account_type change
    $accountTypeChangeActivity = $activities->first(function ($activity): bool {
        // Get the raw changes from the database and decode the JSON
        $rawChanges = $activity->getAttributes()['changes'] ?? null;
        if (is_string($rawChanges)) {
            $changes = json_decode($rawChanges, true);
            return isset($changes['attributes']['account_type']);
        }
        return false;
    });

    expect($accountTypeChangeActivity)->not->toBeNull('Expected to find activity logging account_type change');

    // Verify the activity contains the old and new values
    $rawChanges = $accountTypeChangeActivity->getAttributes()['changes'];
    $changes = json_decode($rawChanges, true);

    expect($changes)->toBeArray()
        ->and($changes['attributes']['account_type'])->toBe($newType->value)
        ->and($changes['old']['account_type'])->toBe($initialType->value);
})->repeat(100);
```

**Key Features**:
- Runs 100 times with different random inputs
- Tests all possible account type transitions
- Validates both data persistence and audit logging
- Ensures proper JSON structure in activity changes

### Standalone Property Validation

For critical properties like Property 28, we also provide standalone validation scripts:

#### test_property_28.php

A focused test script that can be run independently to validate the account type audit trail:

```php
<?php
/**
 * Property 28 Test: Account Type Change Audit Trail
 * 
 * This standalone test validates that account type changes are properly logged
 * in the activity history system.
 */

require_once 'vendor/autoload.php';

use App\Enums\AccountType;
use App\Models\Company;
use App\Models\Team;
use App\Models\User;

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

// Create test data
$team = Team::factory()->create();
$user = User::factory()->create(['current_team_id' => $team->getKey()]);
auth()->login($user);

// Create a company with an initial account type
$initialType = AccountType::CUSTOMER;
$company = Company::factory()->create([
    'team_id' => $team->getKey(),
    'account_type' => $initialType,
]);

// Clear any creation activities to isolate the update test
$company->activities()->delete();

// Change the account type
$newType = AccountType::PROSPECT;
$company->account_type = $newType;
$company->save();

// Verify the change is logged in activities
$activities = $company->activities()->where('event', 'updated')->get();

if ($activities->isNotEmpty()) {
    $activity = $activities->first();
    $changes = $activity->changes;
    
    // Handle Collection format from Activity model
    if ($changes instanceof Collection) {
        $changes = $changes->first();
    }
    
    // Parse JSON string if needed
    if (is_string($changes)) {
        $changes = json_decode($changes, true);
    }
    
    if (isset($changes['attributes']['account_type'])) {
        echo "SUCCESS: Account type change was logged!\n";
        echo "Old value: " . $changes['old']['account_type'] . "\n";
        echo "New value: " . $changes['attributes']['account_type'] . "\n";
    } else {
        echo "FAILURE: Account type change was not logged in activity\n";
    }
} else {
    echo "FAILURE: No update activities found\n";
}
```

**Usage**:
```bash
php test_property_28.php
```

## Property Testing Best Practices

### 1. Define Clear Properties

Properties should be:
- **Specific**: Clearly define what should always be true
- **Testable**: Can be verified programmatically
- **Meaningful**: Represent important business rules

### 2. Use Appropriate Generators

Leverage Faker and factories to generate realistic test data:

```php
// Good: Uses realistic enum values
$accountType = fake()->randomElement(AccountType::cases());

// Good: Ensures different values for transitions
$availableTypes = array_filter(
    AccountType::cases(),
    fn (AccountType $type): bool => $type !== $initialType,
);
$newType = fake()->randomElement($availableTypes);
```

### 3. Validate Complete Behavior

Don't just test the happy path - validate:
- Data persistence
- Side effects (like activity logging)
- Error conditions
- Edge cases

### 4. Use Sufficient Repetitions

Run property tests enough times to catch edge cases:
- Simple properties: 10-50 repetitions
- Complex properties: 100+ repetitions
- Critical properties: 1000+ repetitions

### 5. Provide Clear Failure Messages

Include descriptive messages for assertions:

```php
expect($activities->isNotEmpty())
    ->toBeTrue('Expected at least one update activity');

expect($accountTypeChangeActivity)
    ->not->toBeNull('Expected to find activity logging account_type change');
```

## Property Categories

### Data Integrity Properties

Validate that data is correctly stored and retrieved:
- Account creation persistence (Property 1)
- Account update persistence (Property 2)
- Soft deletion preserves relationships (Property 3)

### Relationship Properties

Validate that relationships work correctly:
- Bidirectional relationship consistency (Property 4)
- Relationship deletion preserves entities (Property 5)
- Complete data retrieval with relationships (Property 6)

### Business Logic Properties

Validate that business rules are enforced:
- Activity chronological ordering (Property 7)
- Activity linkage (Property 8)
- Pipeline value calculation (Property 19)

### Audit Trail Properties

Validate that changes are properly tracked:
- Account type change audit trail (Property 28)
- Merge audit trail (Property 16)
- Team member removal preserves history (Property 30)

## Testing Infrastructure

### Required Setup

Property tests require:
1. **Database**: Fresh database for each test
2. **Authentication**: Authenticated user context
3. **Factories**: Model factories for data generation
4. **Cleanup**: Proper cleanup between tests

### Common Patterns

```php
// Standard setup pattern
beforeEach(function () {
    $this->team = Team::factory()->create();
    $this->user = User::factory()->create(['current_team_id' => $this->team->getKey()]);
    $this->actingAs($this->user);
});

// Property test pattern
test('property description', function (): void {
    // Arrange: Create test data with random inputs
    $input = generateRandomInput();
    
    // Act: Perform the operation
    $result = performOperation($input);
    
    // Assert: Verify the property holds
    expect($result)->toSatisfyProperty();
})->repeat(100);
```

## Debugging Property Tests

### When Property Tests Fail

1. **Identify the failing input**: Note what input caused the failure
2. **Reproduce manually**: Create a focused test with that specific input
3. **Debug step by step**: Add logging to understand the failure
4. **Fix the root cause**: Address the underlying issue
5. **Verify the fix**: Re-run the property test

### Common Issues

1. **Flaky tests**: Usually indicate race conditions or improper cleanup
2. **False positives**: Property definition may be too strict
3. **False negatives**: Property definition may be too loose
4. **Performance issues**: Too many repetitions or expensive operations

## Related Documentation

- [Activity Logging System](activity-logging-system.md)
- [Accounts Module Specification](.kiro/specs/accounts-module/design.md)
- [Testing Standards](.kiro/steering/testing-standards.md)
- [Laravel Conventions](.kiro/steering/laravel-conventions.md)

## Version History

- **v1.0.0** (2025-12-11): Initial property testing guide with Property 28 example