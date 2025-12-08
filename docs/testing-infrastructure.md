# Testing Infrastructure Documentation

**Version:** Laravel 12.x | Filament 4.x | Pest 4.x  
**Last Updated:** December 2025  
**Feature:** Tasks & Activities Enhancement - Property-Based Testing

## Overview

The testing infrastructure provides a comprehensive framework for property-based testing of the Tasks & Activities system. It includes base test cases, data generators, helper functions, and utilities designed to validate correctness properties across multiple iterations with randomly generated data.

## Laravel expectations plugin

- The suite now ships with `defstudio/pest-plugin-laravel-expectations`; prefer its expectations for HTTP/model/storage assertions so Filament v4.3+ and API tests stay readable (e.g., `expect($response)->toBeOk()->toContainText('Dashboard')`, `expect($model)->toExist()->toBelongTo($team)`, `expect('report.pdf')->toExistInStorage()`).

## Stress testing with Pest Stressless

- We ship `pestphp/pest-plugin-stressless` for lightweight load testing built on k6 (AGPL binary is downloaded on first run). Use it for perf/stability spot-checks on API/Filament entry points without wiring a separate tool.
- Quick CLI probe: `./vendor/bin/pest stress https://staging.example.com/filament/app --concurrency=5 --duration=10`.
- Expectation-driven checks live under `tests/Stressless` and are opt-in: `RUN_STRESS_TESTS=1 STRESSLESS_TARGET=https://staging.example.com/health STRESSLESS_CONCURRENCY=3 STRESSLESS_DURATION=5 ./vendor/bin/pest --group=stressless`.
- Keep concurrency/duration small for shared/staging infra and set `STRESSLESS_P95_THRESHOLD_MS` to guard acceptable latency. Never point stress runs at production without explicit approval.
- Favor HTTP endpoints that exercise Filament dashboards/pages over long-running jobs so results reflect UI responsiveness (e.g., `filament.app.pages.dashboard`, calendar endpoints).

## Architecture

### Core Components

```
tests/
├── Support/
│   ├── PropertyTestCase.php          # Base test case for property tests
│   ├── property_test_helpers.php     # Global helper functions
│   ├── Generators/
│   │   ├── TaskGenerator.php         # Task entity generator
│   │   ├── NoteGenerator.php         # Note entity generator
│   │   ├── ActivityGenerator.php     # Activity event generator
│   │   └── TaskRelatedGenerator.php  # Task-related entities generator
│   └── README.md                     # Usage documentation
├── Unit/
│   ├── Properties/
│   │   └── TasksActivities/
│   │       └── InfrastructureTest.php
│   └── Support/
│       └── PropertyTestCaseTest.php
└── Pest.php                          # Pest configuration
```

## PropertyTestCase

### Class Overview

**Namespace:** `Tests\Support\PropertyTestCase`  
**Extends:** `Tests\TestCase`  
**Traits:** `Illuminate\Foundation\Testing\RefreshDatabase`

The `PropertyTestCase` is an abstract base class that provides common setup and utilities for property-based testing. It automatically creates a team and user context for each test, ensuring proper multi-tenancy and authentication.

### Properties

```php
protected Team $team;
protected User $user;
```

Both properties are automatically initialized in `setUp()` and available to all extending test classes.

### Methods

#### setUp(): void

Automatically executed before each test. Creates a team, user, attaches the user to the team, switches to the team context, and authenticates the user.

**Example:**
```php
// Automatically available in all tests extending PropertyTestCase
expect($this->team)->toBeInstanceOf(Team::class);
expect($this->user)->toBeInstanceOf(User::class);
expect(auth()->check())->toBeTrue();
```

#### runPropertyTest(callable $test, int $iterations = 100): void

Executes a test function multiple times with different random data to validate properties hold across the input space.

**Parameters:**
- `$test` (callable): Test function receiving iteration number as parameter
- `$iterations` (int): Number of times to run the test (default: 100)

**Throws:**
- `\InvalidArgumentException`: If iterations < 1
- `\RuntimeException`: If test fails, wrapping the original exception with iteration context

**Example:**
```php
$this->runPropertyTest(function (int $i): void {
    $task = generateTask($this->team);
    expect($task->team_id)->toBe($this->team->id);
}, 100);
```

#### randomSubset(array $items): array

Generates a random subset of an array, useful for testing with variable numbers of relationships.

**Parameters:**
- `$items` (array<T>): Source array

**Returns:** `array<T>` - Random subset (can be empty or full set)

**Example:**
```php
$users = User::factory()->count(10)->create();
$assignees = $this->randomSubset($users->all());
// $assignees contains 0-10 users randomly selected
```

#### randomDate(?string $startDate = '-1 year', ?string $endDate = '+1 year'): Carbon

Generates a random date within a specified range.

**Parameters:**
- `$startDate` (string|null): Start of range (default: '-1 year')
- `$endDate` (string|null): End of range (default: '+1 year')

**Returns:** `Carbon` - Random date within range

**Throws:** `\Exception` - If date parsing fails

**Example:**
```php
$dueDate = $this->randomDate('now', '+6 months');
$createdAt = $this->randomDate('-2 years', '-1 year');
```

#### randomBoolean(float $trueProbability = 0.5): bool

Generates a random boolean with configurable bias.

**Parameters:**
- `$trueProbability` (float): Probability of returning true (0.0 to 1.0)

**Returns:** `bool`

**Throws:** `\InvalidArgumentException` - If probability outside valid range

**Example:**
```php
$isActive = $this->randomBoolean(0.7); // 70% chance of true
$isMilestone = $this->randomBoolean(0.2); // 20% chance of true
```

#### randomInt(int $min = 0, int $max = 100): int

Generates a random integer within a range.

**Parameters:**
- `$min` (int): Minimum value (inclusive, default: 0)
- `$max` (int): Maximum value (inclusive, default: 100)

**Returns:** `int`

**Throws:** `\InvalidArgumentException` - If min > max

**Example:**
```php
$priority = $this->randomInt(1, 5);
$duration = $this->randomInt(15, 480); // minutes
```

#### randomString(int $length = 10): string

Generates a random alphabetic string of specified length.

**Parameters:**
- `$length` (int): String length (default: 10)

**Returns:** `string`

**Throws:** `\InvalidArgumentException` - If length < 1

**Example:**
```php
$code = $this->randomString(8);
$identifier = $this->randomString(16);
```

#### randomEmail(): string

Generates a unique random email address.

**Returns:** `string` - Valid email address

**Example:**
```php
$email = $this->randomEmail();
// Returns: unique-string@example.com
```

#### createTeamUsers(int $count = 1): array

Creates additional users attached to the current team.

**Parameters:**
- `$count` (int): Number of users to create (default: 1)

**Returns:** `array<User>` - Array of created users

**Throws:** `\InvalidArgumentException` - If count < 1

**Example:**
```php
$assignees = $this->createTeamUsers(5);
foreach ($assignees as $user) {
    expect($user->belongsToTeam($this->team))->toBeTrue();
}
```

#### resetPropertyTestState(): void

Refreshes team and user instances and re-authenticates. Useful when you need a clean slate between iterations while keeping the same base entities.

**Example:**
```php
$this->runPropertyTest(function (): void {
    // Perform test operations
    $this->resetPropertyTestState();
    // Continue with fresh state
}, 50);
```

## Global Helper Functions

Located in `tests/Support/property_test_helpers.php`, these functions provide convenient access to generators.

### generateTask(Team $team, ?User $creator = null, array $overrides = []): Task

Generates a random task with all fields populated.

**Example:**
```php
$task = generateTask($team);
$customTask = generateTask($team, $user, ['is_milestone' => true]);
```

### generateNote(Team $team, ?User $creator = null, array $overrides = []): Note

Generates a random note with all fields populated.

**Example:**
```php
$note = generateNote($team);
$privateNote = generateNote($team, $user, ['visibility' => NoteVisibility::PRIVATE]);
```

### generateActivity(Team $team, Model $subject, ?User $causer = null, array $overrides = []): Activity

Generates a random activity event.

**Example:**
```php
$activity = generateActivity($team, $task, $user);
```

### generateTaskReminder(Task $task, ?User $user = null, array $overrides = []): TaskReminder

Generates a task reminder.

**Example:**
```php
$reminder = generateTaskReminder($task, $user);
```

### generateTaskRecurrence(Task $task, array $overrides = []): TaskRecurrence

Generates a task recurrence pattern.

**Example:**
```php
$recurrence = generateTaskRecurrence($task, ['frequency' => 'weekly']);
```

### generateTaskDelegation(Task $task, User $fromUser, User $toUser, array $overrides = []): TaskDelegation

Generates a task delegation.

**Example:**
```php
$delegation = generateTaskDelegation($task, $manager, $employee);
```

### generateTaskChecklistItem(Task $task, array $overrides = []): TaskChecklistItem

Generates a checklist item.

**Example:**
```php
$item = generateTaskChecklistItem($task);
```

### generateTaskComment(Task $task, ?User $user = null, array $overrides = []): TaskComment

Generates a task comment.

**Example:**
```php
$comment = generateTaskComment($task, $user);
```

### generateTaskTimeEntry(Task $task, ?User $user = null, array $overrides = []): TaskTimeEntry

Generates a time entry.

**Example:**
```php
$timeEntry = generateTaskTimeEntry($task, $user, ['is_billable' => true]);
```

### runPropertyTest(callable $test, int $iterations = 100): void

Global function to run property tests without extending PropertyTestCase.

**Example:**
```php
it('validates some property', function (): void {
    $team = Team::factory()->create();
    
    runPropertyTest(function () use ($team): void {
        $task = generateTask($team);
        expect($task)->toBeInstanceOf(Task::class);
    }, 100);
});
```

### randomSubset(array $items): array

Global version of the subset generator.

### randomDate(?string $startDate = '-1 year', ?string $endDate = '+1 year'): Carbon

Global version of the date generator.

### randomBoolean(float $trueProbability = 0.5): bool

Global version of the boolean generator.

## Data Generators

### TaskGenerator

**Location:** `tests/Support/Generators/TaskGenerator.php`

#### Methods

- `generate(Team $team, ?User $creator = null, array $overrides = []): Task`
- `generateWithSubtasks(Team $team, int $subtaskCount = 3): Task`
- `generateWithAssignees(Team $team, int $assigneeCount = 2): Task`
- `generateWithCategories(Team $team, int $categoryCount = 2): Task`
- `generateWithDependencies(Team $team, int $dependencyCount = 2): Task`
- `generateDateRange(): array{start_date: Carbon, end_date: Carbon}`
- `generateData(Team $team, ?User $creator = null): array`
- `generateCompleted(Team $team): Task`
- `generateIncomplete(Team $team): Task`
- `generateMilestone(Team $team): Task`

### NoteGenerator

**Location:** `tests/Support/Generators/NoteGenerator.php`

#### Methods

- `generate(Team $team, ?User $creator = null, array $overrides = []): Note`
- `generateWithVisibility(Team $team, NoteVisibility $visibility): Note`
- `generatePrivate(Team $team, User $creator): Note`
- `generateInternal(Team $team): Note`
- `generateExternal(Team $team): Note`
- `generateWithCategory(Team $team, NoteCategory $category): Note`
- `generateData(Team $team, ?User $creator = null): array`
- `generateTemplate(Team $team): Note`
- `generateAllVisibilities(Team $team, User $creator): array`
- `generateAllCategories(Team $team): array`

### ActivityGenerator

**Location:** `tests/Support/Generators/ActivityGenerator.php`

#### Methods

- `generate(Team $team, Model $subject, ?User $causer = null, array $overrides = []): Activity`
- `generateCreated(Team $team, Model $subject, ?User $causer = null): Activity`
- `generateUpdated(Team $team, Model $subject, ?User $causer = null): Activity`
- `generateDeleted(Team $team, Model $subject, ?User $causer = null): Activity`
- `generateRestored(Team $team, Model $subject, ?User $causer = null): Activity`
- `generateMultiple(Team $team, Model $subject, int $count = 5, ?User $causer = null): array`
- `generateAllEventTypes(Team $team, Model $subject, ?User $causer = null): array`

### TaskRelatedGenerator

**Location:** `tests/Support/Generators/TaskRelatedGenerator.php`

#### Methods

- `generateReminder(Task $task, ?User $user = null, array $overrides = []): TaskReminder`
- `generateRecurrence(Task $task, array $overrides = []): TaskRecurrence`
- `generateDelegation(Task $task, User $fromUser, User $toUser, array $overrides = []): TaskDelegation`
- `generateChecklistItem(Task $task, array $overrides = []): TaskChecklistItem`
- `generateChecklistItems(Task $task, int $count = 3): array`
- `generateComment(Task $task, ?User $user = null, array $overrides = []): TaskComment`
- `generateTimeEntry(Task $task, ?User $user = null, array $overrides = []): TaskTimeEntry`
- `generateTimeEntries(Task $task, User $user, int $count = 3): array`
- `generateBillableTimeEntry(Task $task, ?User $user = null): TaskTimeEntry`
- `generateNonBillableTimeEntry(Task $task, ?User $user = null): TaskTimeEntry`

## Usage Patterns

### Basic Property Test

```php
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('validates task creation with all fields', function (): void {
    $team = Team::factory()->create();
    
    runPropertyTest(function () use ($team): void {
        $task = generateTask($team);
        
        expect($task)->toBeInstanceOf(Task::class)
            ->and($task->team_id)->toBe($team->id)
            ->and($task->title)->not->toBeEmpty();
    }, 100);
});
```

### Using PropertyTestCase

```php
use Tests\Support\PropertyTestCase;

final class TaskPropertyTest extends PropertyTestCase
{
    public function test_task_assignee_relationship(): void
    {
        $this->runPropertyTest(function (): void {
            $users = $this->createTeamUsers(3);
            $task = generateTask($this->team);
            
            $task->assignees()->attach($users);
            
            expect($task->assignees)->toHaveCount(3);
        }, 100);
    }
}
```

### Complex Property Test

```php
it('validates task dependency blocking', function (): void {
    $team = Team::factory()->create();
    
    runPropertyTest(function () use ($team): void {
        // Generate task with dependencies
        $task = TaskGenerator::generateWithDependencies($team, 3);
        
        // Verify task is blocked
        expect($task->isBlocked())->toBeTrue();
        
        // Complete all dependencies
        $task->dependencies->each->update(['percent_complete' => 100]);
        
        // Verify task is unblocked
        expect($task->fresh()->isBlocked())->toBeFalse();
    }, 50);
});
```

## Best Practices

### 1. Use Appropriate Iteration Counts

- **Quick validation:** 10-20 iterations
- **Standard properties:** 100 iterations (default)
- **Critical properties:** 200+ iterations
- **Performance tests:** 1000+ iterations

### 2. Leverage Generators

Always use generators instead of manually creating test data:

```php
// ✅ Good
$task = generateTask($team);

// ❌ Bad
$task = Task::factory()->create(['team_id' => $team->id]);
```

### 3. Test Properties, Not Examples

Focus on universal rules:

```php
// ✅ Good - Tests a property
it('validates all tasks have a team', function (): void {
    runPropertyTest(function (): void {
        $team = Team::factory()->create();
        $task = generateTask($team);
        expect($task->team_id)->toBe($team->id);
    }, 100);
});

// ❌ Bad - Tests a specific example
it('creates a task with name "Test Task"', function (): void {
    $task = Task::factory()->create(['title' => 'Test Task']);
    expect($task->title)->toBe('Test Task');
});
```

### 4. Document Properties Clearly

```php
/**
 * Feature: tasks-activities-enhancement, Property 1: Task creation with all fields
 * Validates: Requirements 1.1, 1.2, 1.3, 1.4, 1.5
 * 
 * Property: For any valid task data including title, description, status, priority,
 * dates, duration, team context, creator, and optional parent, creating a task should
 * result in a task record with all fields correctly populated and relationships established.
 */
it('validates Property 1: Task creation with all fields', function (): void {
    // Test implementation
});
```

### 5. Handle Edge Cases

```php
runPropertyTest(function (): void {
    $team = Team::factory()->create();
    
    // Test with empty subset
    $categories = $this->randomSubset([]);
    expect($categories)->toBeEmpty();
    
    // Test with full set
    $allUsers = User::factory()->count(5)->create();
    $subset = $this->randomSubset($allUsers->all());
    expect(count($subset))->toBeLessThanOrEqual(5);
}, 100);
```

## Testing the Infrastructure

The infrastructure itself is tested in:

- `tests/Unit/Support/PropertyTestCaseTest.php` - Tests all PropertyTestCase methods
- `tests/Unit/Properties/TasksActivities/InfrastructureTest.php` - Validates generators work correctly

Run infrastructure tests:

```bash
php artisan test tests/Unit/Support/PropertyTestCaseTest.php
php artisan test tests/Unit/Properties/TasksActivities/InfrastructureTest.php
```

## Performance Considerations

### Database Transactions

The `RefreshDatabase` trait ensures each test runs in a transaction that's rolled back, providing isolation without slow migrations.

### Factory Optimization

Generators use existing factories efficiently:

```php
// Efficient - reuses factory
$task = TaskGenerator::generate($team);

// Less efficient - creates unnecessary data
$task = Task::factory()
    ->for($team)
    ->has(User::factory()->count(5), 'assignees')
    ->create();
```

### Caching

For frequently used data:

```php
protected function setUp(): void
{
    parent::setUp();
    
    // Cache categories for reuse
    $this->categories = TaskCategory::factory()
        ->count(5)
        ->create(['team_id' => $this->team->id]);
}
```

## Troubleshooting

### Generator Failures

**Issue:** Generator creates invalid data

**Solution:** Check factory definitions and ensure all required relationships exist

```php
// Ensure team exists before generating
$team = Team::factory()->create();
$task = generateTask($team); // Now works
```

### Test Failures

**Issue:** Property test fails on specific iteration

**Solution:** The exception message includes iteration number for debugging

```
RuntimeException: Property test failed on iteration 42: ...
```

### Performance Issues

**Issue:** Tests run slowly

**Solution:** Reduce iteration count during development

```php
// Development
$this->runPropertyTest($test, 10);

// CI/Production
$this->runPropertyTest($test, 100);
```

## Integration with Pest

The infrastructure integrates seamlessly with Pest through `tests/Pest.php`:

```php
require_once __DIR__.'/Support/property_test_helpers.php';

uses(Tests\TestCase::class)->in('Feature');
uses(RefreshDatabase::class)->in('Feature', 'Unit');
```

All helper functions are globally available in all test files.

## Version History

| Version | Date | Changes |
|---------|------|---------|
| 1.0.0 | Dec 2025 | Initial implementation with PropertyTestCase, generators, and helpers |

## Related Documentation

- [Testing Standards](./testing-standards.md)
- [Property-Based Testing Guide](../tests/Support/README.md)
- [Tasks & Activities Enhancement Spec](../.kiro/specs/tasks-activities-enhancement/)
- [Pest Documentation](https://pestphp.com/)
- [Laravel Testing](https://laravel.com/docs/testing)

## See Also

- **PropertyTestCase Source:** `tests/Support/PropertyTestCase.php`
- **Helper Functions:** `tests/Support/property_test_helpers.php`
- **Generators:** `tests/Support/Generators/`
- **Example Tests:** `tests/Unit/Properties/TasksActivities/`
