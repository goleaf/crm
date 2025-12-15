# Design Document: Technical Debt TODO Resolution

## Overview

This design addresses three technical debt items identified in the codebase through TODO comments. Each item represents a deferred improvement that will enhance application performance, code quality, and user experience. The implementation will be done incrementally, with each TODO item treated as an independent feature that can be enabled and tested separately.

## Architecture

The solution follows Laravel's standard architecture patterns:

1. **Configuration Layer**: Bootstrap and service provider configuration for application-wide settings
2. **Model Layer**: Eloquent models with relationship definitions and query optimization
3. **Resource Layer**: Filament resources handling UI interactions and business logic
4. **Notification Layer**: Laravel's notification system for user communications
5. **Testing Layer**: Pest PHP tests for verification and regression prevention

### Component Interaction

```
Bootstrap (app.php)
    ↓
Service Providers (AppServiceProvider)
    ↓
Models (Task, User, etc.)
    ↓
Resources (TaskResource)
    ↓
Notifications (Database Notifications)
```

## Components and Interfaces

### 1. Eager Loading Configuration

**Location**: `bootstrap/app.php`

**Purpose**: Enable automatic eager loading to prevent N+1 query problems

**Interface**:
```php
Application::configure()
    ->booting(function (): void {
        Model::automaticallyEagerLoadRelationships();
    })
```

**Dependencies**:
- `Illuminate\Database\Eloquent\Model`
- All Eloquent models in the application

### 2. Strict Mode Configuration

**Location**: `app/Providers/AppServiceProvider.php`

**Purpose**: Enable Laravel's strict mode to catch development issues early

**Interface**:
```php
private function configureModels(): void
{
    Model::unguard();
    Model::shouldBeStrict(! $this->app->isProduction());
    // ... rest of configuration
}
```

**Dependencies**:
- `Illuminate\Database\Eloquent\Model`
- Application environment configuration

### 3. Task Assignment Notification System

**Location**: `app/Filament/Resources/TaskResource.php`

**Purpose**: Prevent duplicate notifications when users are assigned to tasks

**Current Implementation**:
```php
// Check if notification exists by task_id only
$notificationExists = $recipient->notifications()
    ->where('data->viewData->task_id', $record->id)
    ->exists();
```

**Improved Interface**:
```php
// Check if user is already assigned AND has been notified
private function shouldNotifyAssignee(User $user, Task $task): bool
{
    // Check if user was previously assigned
    $wasAssigned = DB::table('task_user')
        ->where('task_id', $task->id)
        ->where('user_id', $user->id)
        ->whereNotNull('notified_at')
        ->exists();
    
    return !$wasAssigned;
}
```

**Dependencies**:
- `App\Models\Task`
- `App\Models\User`
- `Filament\Notifications\Notification`
- `task_user` pivot table

## Data Models

### Task Model

**Relationships**:
- `assignees()`: BelongsToMany<User> - Users assigned to the task
- `companies()`: MorphToMany<Company> - Related companies
- `opportunities()`: MorphToMany<Opportunity> - Related opportunities
- `people()`: MorphToMany<People> - Related people
- `creator`: BelongsTo<User> - User who created the task

### User Model

**Relationships**:
- `tasks()`: BelongsToMany<Task> - Tasks assigned to the user
- `notifications()`: HasMany<DatabaseNotification> - User's notifications
- `teams()`: BelongsToMany<Team> - Teams the user belongs to

### Task-User Pivot Table Enhancement

**Current Schema**:
```
task_user
- task_id
- user_id
```

**Enhanced Schema** (for notification tracking):
```
task_user
- task_id
- user_id
- notified_at (nullable timestamp) - When the user was notified about this assignment
```

## Correctness Properties

*A property is a characteristic or behavior that should hold true across all valid executions of a system—essentially, a formal statement about what the system should do. Properties serve as the bridge between human-readable specifications and machine-verifiable correctness guarantees.*

### Property 1: Eager Loading Prevents N+1 Queries

*For any* model with defined relationships, when that model is queried with automatic eager loading enabled, the number of database queries should not increase linearly with the number of related records.

**Validates: Requirements 1.1, 1.3**

### Property 2: Strict Mode Prevents Lazy Loading

*For any* model with relationships, when strict mode is enabled and a relationship is accessed without eager loading, the system should throw a `LazyLoadingViolationException`.

**Validates: Requirements 2.2**

### Property 3: Strict Mode Prevents Mass Assignment Violations

*For any* model with guarded attributes, when strict mode is enabled and mass assignment is attempted on a guarded attribute, the system should throw a `MassAssignmentException`.

**Validates: Requirements 2.3**

### Property 4: Strict Mode Prevents Missing Attribute Access

*For any* model instance, when strict mode is enabled and a non-existent attribute is accessed, the system should throw a `MissingAttributeException`.

**Validates: Requirements 2.4**

### Property 5: New Assignees Receive Notifications

*For any* task and any user not previously assigned to that task, when the user is added as an assignee, the system should send exactly one notification to that user.

**Validates: Requirements 3.1, 3.6**

### Property 6: Existing Assignees Don't Receive Duplicate Notifications

*For any* task and any user already assigned to that task, when the task is updated without changing the assignee list, the system should not send additional notifications to that user.

**Validates: Requirements 3.2, 3.4**

### Property 7: Only New Assignees Receive Notifications on Update

*For any* task with existing assignees, when new assignees are added, the system should send notifications only to the newly added assignees and not to the existing assignees.

**Validates: Requirements 3.3**

## Error Handling

### Eager Loading Errors

**Scenario**: Automatic eager loading causes test failures due to circular dependencies or memory issues

**Handling**:
1. Identify problematic relationships through test execution
2. Exclude specific relationships from automatic eager loading using `Model::preventLazyLoading()`
3. Document excluded relationships and reasons
4. Add explicit eager loading in queries where needed

### Strict Mode Violations

**Scenario**: Enabling strict mode reveals existing code issues

**Handling**:
1. Run test suite to identify all violations
2. Fix violations by:
   - Adding explicit eager loading for lazy-loaded relationships
   - Updating mass assignment protection
   - Fixing attribute access patterns
3. Enable strict mode only after all violations are resolved

### Notification System Errors

**Scenario**: Notification sending fails or database queries fail

**Handling**:
1. Wrap notification logic in try-catch blocks
2. Log errors for debugging
3. Use database transactions to ensure data consistency
4. Implement retry logic for transient failures

**Error Recovery**:
```php
try {
    DB::beginTransaction();
    
    // Update task and assignees
    $record->update($data);
    
    // Send notifications
    $this->notifyNewAssignees($record);
    
    DB::commit();
} catch (Throwable $e) {
    DB::rollBack();
    Log::error('Task update failed', [
        'task_id' => $record->id,
        'error' => $e->getMessage()
    ]);
    throw $e;
}
```

## Testing Strategy

### Dual Testing Approach

This implementation will use both unit testing and property-based testing:

- **Unit tests** verify specific examples, edge cases, and error conditions
- **Property tests** verify universal properties that should hold across all inputs
- Together they provide comprehensive coverage: unit tests catch concrete bugs, property tests verify general correctness

### Property-Based Testing

**Framework**: Pest PHP with custom property testing helpers (or a library like `pest-plugin-faker` for property-based testing patterns)

**Configuration**: Each property-based test will run a minimum of 100 iterations to ensure thorough coverage of the input space.

**Test Tagging**: Each property-based test will be tagged with a comment explicitly referencing the correctness property in this design document using the format: `**Feature: technical-debt-todos, Property {number}: {property_text}**`

**Property Test Examples**:

1. **Eager Loading Property Test**:
   - Generate random models with varying numbers of relationships
   - Enable query logging
   - Query models and verify query count remains constant regardless of relationship count
   - **Feature: technical-debt-todos, Property 1: Eager Loading Prevents N+1 Queries**

2. **Strict Mode Lazy Loading Test**:
   - Generate random model instances
   - Enable strict mode
   - Attempt to access relationships without eager loading
   - Verify `LazyLoadingViolationException` is thrown
   - **Feature: technical-debt-todos, Property 2: Strict Mode Prevents Lazy Loading**

3. **Notification Deduplication Test**:
   - Generate random tasks with random assignee sets
   - Add assignees and verify notifications
   - Update task without changing assignees
   - Verify no duplicate notifications sent
   - **Feature: technical-debt-todos, Property 6: Existing Assignees Don't Receive Duplicate Notifications**

### Unit Testing

**Framework**: Pest PHP

**Test Categories**:

1. **Configuration Tests**:
   - Verify eager loading is enabled in bootstrap
   - Verify strict mode is enabled based on environment
   - Test environment-specific behavior

2. **Notification Logic Tests**:
   - Test notification sending for new assignees
   - Test notification prevention for existing assignees
   - Test mixed scenarios (some new, some existing assignees)
   - Test edge cases (empty assignee list, single assignee, etc.)

3. **Integration Tests**:
   - Test full task update flow with notifications
   - Test database transaction rollback on errors
   - Test notification data structure and content

### Test Execution Strategy

1. **Phase 1**: Run existing test suite as baseline
2. **Phase 2**: Enable each feature individually and run tests
3. **Phase 3**: Fix any test failures or violations
4. **Phase 4**: Add new property-based tests for each feature
5. **Phase 5**: Run full test suite with all features enabled

### Performance Testing

While not part of the automated test suite, manual performance verification should include:

1. **Query Count Monitoring**: Use Laravel Debugbar or Telescope to verify query counts before/after eager loading
2. **Memory Usage**: Monitor memory consumption with large datasets
3. **Response Time**: Measure API response times for common operations

## Implementation Notes

### Migration Strategy

1. **Eager Loading**: Enable in development first, monitor for issues, then enable in production
2. **Strict Mode**: Enable in development/staging first, fix all violations, then enable in production
3. **Notifications**: Deploy improved logic with database migration for pivot table, backfill existing assignments

### Database Migration for Notification Tracking

```php
Schema::table('task_user', function (Blueprint $table) {
    $table->timestamp('notified_at')->nullable()->after('user_id');
    $table->index(['task_id', 'user_id', 'notified_at']);
});
```

### Backward Compatibility

- Eager loading: Fully backward compatible
- Strict mode: May reveal existing bugs (this is intentional)
- Notifications: Fully backward compatible, existing assignments will be treated as "already notified"

### Rollback Plan

Each feature can be independently disabled by commenting out the configuration:

1. Eager loading: Comment out `Model::automaticallyEagerLoadRelationships()`
2. Strict mode: Comment out `Model::shouldBeStrict()`
3. Notifications: Revert to previous notification logic

## Security Considerations

- **Notification Privacy**: Ensure notifications only go to authorized users
- **Database Transactions**: Prevent race conditions in concurrent task updates
- **Input Validation**: Validate assignee IDs before sending notifications
- **Authorization**: Verify user permissions before sending task notifications

## Performance Considerations

- **Eager Loading**: Reduces query count but increases memory usage per query
- **Notification Batching**: Consider batching notifications for bulk operations
- **Index Optimization**: Add database indexes for notification queries
- **Caching**: Consider caching frequently accessed relationships

## Monitoring and Observability

- **Query Logging**: Monitor query counts in production to verify eager loading effectiveness
- **Error Tracking**: Track strict mode violations in Sentry
- **Notification Metrics**: Track notification send rates and failures
- **Performance Metrics**: Monitor response times and memory usage
