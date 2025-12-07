# Property-Based Testing Infrastructure

This directory contains the infrastructure for property-based testing of the Tasks & Activities enhancement.

## Overview

Property-based testing validates that properties (universal rules) hold across many randomly generated inputs. This approach is more comprehensive than example-based testing and helps catch edge cases.

## Components

### Base Test Case

**`PropertyTestCase.php`** - Abstract base class for property-based tests
- Provides common setup (team, user, authentication)
- Includes utility methods for running property tests
- Offers helpers for generating random data

### Generators

Located in `Generators/` directory:

- **`TaskGenerator.php`** - Generates random Task instances with various configurations
  - Simple tasks
  - Tasks with subtasks
  - Tasks with assignees, categories, dependencies
  - Milestone tasks
  - Completed/incomplete tasks

- **`NoteGenerator.php`** - Generates random Note instances
  - Notes with different visibility levels (private, internal, external)
  - Notes with different categories
  - Note templates

- **`ActivityGenerator.php`** - Generates random Activity events
  - Created, updated, deleted, restored events
  - With proper change tracking

- **`TaskRelatedGenerator.php`** - Generates task-related entities
  - Task reminders
  - Task recurrence patterns
  - Task delegations
  - Checklist items
  - Comments
  - Time entries (billable and non-billable)

### Helper Functions

**`property_test_helpers.php`** - Global helper functions for easy access to generators

Available functions:
- `generateTask()` - Generate a random task
- `generateNote()` - Generate a random note
- `generateActivity()` - Generate a random activity
- `generateTaskReminder()` - Generate a task reminder
- `generateTaskRecurrence()` - Generate a recurrence pattern
- `generateTaskDelegation()` - Generate a delegation
- `generateTaskChecklistItem()` - Generate a checklist item
- `generateTaskComment()` - Generate a comment
- `generateTaskTimeEntry()` - Generate a time entry
- `runPropertyTest()` - Run a test multiple times
- `randomSubset()` - Get a random subset of an array
- `randomDate()` - Generate a random date
- `randomBoolean()` - Generate a random boolean with bias

## Usage

### Basic Property Test

```php
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('validates some property', function (): void {
    $team = Team::factory()->create();
    
    // Run test 100 times with random data
    runPropertyTest(function () use ($team): void {
        $task = generateTask($team);
        
        // Assert property holds
        expect($task->title)->not->toBeEmpty();
    }, 100);
});
```

### Using Generators Directly

```php
use Tests\Support\Generators\TaskGenerator;

it('tests task with subtasks', function (): void {
    $team = Team::factory()->create();
    
    $task = TaskGenerator::generateWithSubtasks($team, 5);
    
    expect($task->subtasks)->toHaveCount(5);
});
```

### Using the Base Test Case

```php
use Tests\Support\PropertyTestCase;

final class MyPropertyTest extends PropertyTestCase
{
    public function test_some_property(): void
    {
        // $this->team and $this->user are already set up
        $task = generateTask($this->team, $this->user);
        
        $this->assertNotNull($task->title);
    }
}
```

## Test Data Seeder

**`database/seeders/TestDataSeeder.php`** - Seeds comprehensive test data

Run with:
```bash
php artisan db:seed --class=TestDataSeeder
```

Creates:
- Multiple teams with users
- Tasks with various configurations
- Notes with different visibility levels
- Task-related entities (reminders, delegations, time entries, etc.)

## Property Test Format

Each property test should follow this format:

```php
/**
 * Feature: tasks-activities-enhancement, Property X: Property name
 * Validates: Requirements X.Y
 * 
 * Property: For any [input description], [expected behavior].
 */
it('tests property X', function (): void {
    // Test implementation
})->repeat(100); // Run 100 times
```

## Configuration

Property tests are configured to run a minimum of 100 iterations by default (as specified in the design document). This can be adjusted per test using the `repeat()` method or the `runPropertyTest()` function.

## Best Practices

1. **Use Generators** - Always use generators instead of manually creating test data
2. **Test Properties, Not Examples** - Focus on universal rules that should hold for all inputs
3. **Run Multiple Iterations** - Use at least 100 iterations for comprehensive coverage
4. **Document Properties** - Clearly document what property is being tested
5. **Reference Requirements** - Link each property to specific requirements
6. **Keep Tests Focused** - Each test should validate one property
7. **Use Descriptive Names** - Test names should clearly describe the property being tested

## Integration with Pest

The infrastructure is integrated with Pest through `tests/Pest.php`:

```php
require_once __DIR__.'/Support/property_test_helpers.php';
```

This makes all helper functions globally available in tests.

## Troubleshooting

### Generator Issues

If generators fail, check:
- Database migrations are up to date
- Required factories exist
- Team context is properly set

### Test Failures

When a property test fails:
1. Note the iteration number
2. Check the generated data
3. Verify the property is correctly specified
4. Consider if the property needs refinement

### Performance

If tests are slow:
- Reduce iteration count during development
- Use database transactions (RefreshDatabase trait)
- Consider caching frequently used data
