# Activity Logging System

## Overview

The Relaticle CRM implements a comprehensive activity logging system that tracks all changes to important entities like Companies, People, Opportunities, Tasks, and more. This system provides a complete audit trail for compliance and debugging purposes.

## Architecture

### Core Components

1. **LogsActivity Trait** (`app/Models/Concerns/LogsActivity.php`)
2. **Activity Model** (`app/Models/Activity.php`)
3. **Activity Database Table** (`activities`)

### How It Works

The `LogsActivity` trait is applied to models that need activity tracking. It automatically logs:
- **Created events** - When a new record is created
- **Updated events** - When a record is modified (with change details)
- **Deleted events** - When a record is deleted

## Implementation Details

### LogsActivity Trait

```php
trait LogsActivity
{
    public function activities(): MorphMany
    {
        return $this->morphMany(Activity::class, 'subject')->latest();
    }

    protected static function bootLogsActivity(): void
    {
        static::created(function (Model $model): void {
            $model->recordActivity('created');
        });

        static::updated(function (Model $model): void {
            $changes = $model->getActivityChanges();
            if ($changes !== []) {
                $model->recordActivity('updated', $changes);
            }
        });

        static::deleted(function (Model $model): void {
            $model->recordActivity('deleted');
        });
    }
}
```

### Activity Model

The Activity model stores activity records with the following structure:

```php
final class Activity extends Model
{
    protected $fillable = [
        'team_id',      // Multi-tenant scoping
        'event',        // 'created', 'updated', 'deleted'
        'changes',      // JSON data with old/new values
        'causer_id',    // User who made the change
    ];

    protected function casts(): array
    {
        return [
            'changes' => 'array',
        ];
    }
}
```

### Change Tracking Format

For update events, the `changes` field contains:

```json
{
    "attributes": {
        "field_name": "new_value"
    },
    "old": {
        "field_name": "old_value"
    }
}
```

## Property 28: Account Type Change Audit Trail

### Requirement

**Property 28** validates that account type changes are properly preserved in the activity history. This is critical for compliance and audit purposes.

### Implementation

When a Company's `account_type` field is changed:

1. The `LogsActivity` trait detects the change
2. An activity record is created with event type 'updated'
3. The changes field contains both old and new account type values
4. The activity is scoped to the appropriate team

### Test Implementation

The system includes both formal tests and a standalone validation script:

#### Formal Test (`tests/Unit/Models/CompanyTest.php`)
```php
test('account type changes are preserved in activity history', function (): void {
    // Create company with initial account type
    $company = Company::factory()->create(['account_type' => $initialType]);
    
    // Change account type
    $company->account_type = $newType;
    $company->save();
    
    // Verify activity logging
    $activities = $company->activities()->where('event', 'updated')->get();
    expect($activities->isNotEmpty())->toBeTrue();
    
    // Verify change details
    $changes = json_decode($activity->getAttributes()['changes'], true);
    expect($changes['attributes']['account_type'])->toBe($newType->value);
    expect($changes['old']['account_type'])->toBe($initialType->value);
})->repeat(100);
```

#### Standalone Test (`test_property_28.php`)
A standalone script for quick validation during development:

```php
// Create test data
$company = Company::factory()->create(['account_type' => AccountType::CUSTOMER]);

// Change account type
$company->account_type = AccountType::PROSPECT;
$company->save();

// Verify logging
$activities = $company->activities()->where('event', 'updated')->get();
// ... validation logic
```

## Models Using Activity Logging

The following models implement the `LogsActivity` trait:

- **Company** - Account management and contact information
- **People** - Individual contacts and leads
- **Opportunity** - Sales opportunities and deals
- **Task** - Task management and assignments
- **Lead** - Lead tracking and conversion
- **Order** - Order processing and fulfillment
- **Quote** - Quote generation and management
- **PurchaseOrder** - Purchase order management

## Data Handling Considerations

### JSON Storage Format

Activity changes are stored as JSON in the database but accessed through Laravel's array casting. The Activity model provides compatibility methods for different access patterns:

```php
// Collection-based access (Filament compatibility)
public function getChangesAttribute($value = null): Collection
{
    return collect($value ?? []);
}

// Array-based access (Spatie compatibility)
public function getPropertiesAttribute(): array
{
    return $this->getAttributes()['changes'] ?? [];
}
```

### Multi-Tenant Scoping

All activities are automatically scoped to the appropriate team:

```php
protected function recordActivity(string $event, array $changes = []): void
{
    $teamId = $this->getAttribute('team_id') ?? CurrentTeamResolver::resolveId(Auth::user());
    
    $this->activities()->create([
        'team_id' => $teamId,
        'event' => $event,
        'causer_id' => Auth::id(),
        'changes' => $changes === [] ? null : $changes,
    ]);
}
```

## Best Practices

### Adding Activity Logging to New Models

1. Add the `LogsActivity` trait to your model
2. Ensure the model has a `team_id` field for multi-tenant scoping
3. Test the activity logging with property tests
4. Verify change tracking works for all important fields

### Testing Activity Logging

1. **Property Tests**: Use property-based testing to validate logging across many scenarios
2. **Standalone Tests**: Create focused tests for critical audit requirements
3. **Change Validation**: Always verify both old and new values are captured
4. **Event Types**: Test all event types (created, updated, deleted)

### Performance Considerations

1. **Selective Logging**: Only log changes to important fields
2. **Batch Operations**: Consider disabling logging for bulk operations if needed
3. **Cleanup**: Implement activity log cleanup for old records
4. **Indexing**: Ensure proper database indexes on frequently queried fields

## Related Documentation

- [Accounts Module Specification](.kiro/specs/accounts-module/design.md)
- [Testing Standards](.kiro/steering/testing-standards.md)
- [Laravel Conventions](.kiro/steering/laravel-conventions.md)
- [Filament Conventions](.kiro/steering/filament-conventions.md)

## Version History

- **v1.0.0** (2025-12-11): Initial activity logging system implementation
- **v1.1.0** (2025-12-11): Added Property 28 validation and standalone testing