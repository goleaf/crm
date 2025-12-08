# Design Document

## Overview

The Tasks & Activities Enhancement builds upon the existing foundational models (Task, Note, Activity) to provide a comprehensive task management, note-taking, and activity tracking system. The design leverages Laravel's Eloquent ORM, Filament v4.3+ for the UI layer, and the existing custom fields system for flexible data storage. The architecture emphasizes data integrity, performance, and user experience through proper relationship management, validation, and real-time updates.

## Architecture

### High-Level Architecture

```
┌─────────────────────────────────────────────────────────────┐
│                     Presentation Layer                       │
│  (Filament Resources, Pages, Relation Managers, Actions)    │
└────────────────────┬────────────────────────────────────────┘
                     │
┌────────────────────▼────────────────────────────────────────┐
│                    Application Layer                         │
│     (Services, Observers, Jobs, Notifications)              │
└────────────────────┬────────────────────────────────────────┘
                     │
┌────────────────────▼────────────────────────────────────────┐
│                      Domain Layer                            │
│  (Models: Task, Note, Activity, TaskReminder, etc.)         │
└────────────────────┬────────────────────────────────────────┘
                     │
┌────────────────────▼────────────────────────────────────────┐
│                   Infrastructure Layer                       │
│    (Database, Storage, Queue, Cache, Notifications)         │
└─────────────────────────────────────────────────────────────┘
```

### Component Interaction Flow

```
User Action → Filament Resource/Page → Service/Observer → Model → Database
                                    ↓
                              Notification/Job → Queue → Background Processing
                                    ↓
                              Activity Logging → Activity Model → Database
```

## Components and Interfaces

### Core Models

#### Task Model (Enhanced)
- **Responsibilities**: Task lifecycle management, dependency validation, completion tracking
- **Key Methods**:
  - `isBlocked()`: Check if task has incomplete dependencies
  - `isCompleted()`: Determine completion status from custom field
  - `calculatePercentComplete()`: Recursive calculation based on subtasks
  - `updatePercentComplete()`: Update completion and propagate to parent
  - `getEarliestStartDate()`: Calculate based on dependencies
  - `violatesDependencyConstraints()`: Validate date constraints
  - `getTotalBillableTime()`: Sum billable time entries
  - `getTotalBillingAmount()`: Calculate billing from time entries
  - `dueDate()`: Parse due date from custom field
  - `statusLabel()`, `priorityLabel()`: Human-readable labels

#### Note Model (Enhanced)
- **Responsibilities**: Note content management, visibility control, attachment handling
- **Key Methods**:
  - `isPrivate()`, `isExternal()`: Visibility checks
  - `categoryLabel()`: Human-readable category
  - `body()`: Retrieve rich text content from custom field
  - `plainBody()`: Strip HTML tags for search/preview
  - `attachments()`: Relationship to media files

#### Activity Model (Enhanced)
- **Responsibilities**: Event logging, change tracking, audit trail
- **Key Methods**:
  - `subject()`: Polymorphic relationship to any entity
  - `causer()`: User who triggered the event
  - `team()`: Team context for multi-tenancy

#### Supporting Models

**TaskReminder**
- Fields: `task_id`, `user_id`, `remind_at`, `sent_at`, `canceled_at`, `channel`, `status`
- Relationships: `task()`, `user()`

**TaskRecurrence**
- Fields: `task_id`, `frequency`, `interval`, `days_of_week`, `starts_on`, `ends_on`, `max_occurrences`, `timezone`, `is_active`
- Relationships: `task()`

**TaskDelegation**
- Fields: `task_id`, `from_user_id`, `to_user_id`, `status`, `delegated_at`, `accepted_at`, `declined_at`, `note`
- Relationships: `task()`, `from()`, `to()`

**TaskChecklistItem**
- Fields: `task_id`, `title`, `is_completed`, `position`
- Relationships: `task()`

**TaskComment**
- Fields: `task_id`, `user_id`, `parent_id`, `body`
- Relationships: `task()`, `user()`, `parent()`, `replies()`

**TaskTimeEntry**
- Fields: `task_id`, `user_id`, `started_at`, `ended_at`, `duration_minutes`, `is_billable`, `billing_rate`, `note`
- Relationships: `task()`, `user()`
- Validation: `validateNoOverlap()`, `validateNoDuplicate()`

**NoteHistory**
- Fields: `note_id`, `user_id`, `event`, `old_values`, `new_values`
- Relationships: `note()`, `user()`

### Services

#### TaskReminderService
- **Responsibilities**: Schedule, send, and cancel task reminders
- **Methods**:
  - `scheduleReminder(Task $task, Carbon $remindAt, User $user, string $channel)`: Create reminder
  - `sendDueReminders()`: Process pending reminders
  - `cancelTaskReminders(Task $task)`: Cancel all reminders for a task
  - `sendReminderNotification(TaskReminder $reminder)`: Send notification via specified channel

#### TaskRecurrenceService
- **Responsibilities**: Generate recurring task instances
- **Methods**:
  - `createRecurrence(Task $task, array $pattern)`: Set up recurrence pattern
  - `generateNextInstance(Task $task)`: Create next recurring task
  - `shouldGenerateNext(TaskRecurrence $recurrence)`: Check if next instance is due
  - `calculateNextDate(TaskRecurrence $recurrence)`: Compute next occurrence date

#### TaskDelegationService
- **Responsibilities**: Handle task delegation workflow
- **Methods**:
  - `delegateTask(Task $task, User $from, User $to, ?string $note)`: Create delegation
  - `acceptDelegation(TaskDelegation $delegation)`: Accept delegated task
  - `declineDelegation(TaskDelegation $delegation, string $reason)`: Decline delegation
  - `notifyDelegation(TaskDelegation $delegation)`: Send delegation notifications

#### ActivityLogService
- **Responsibilities**: Record and query activity events
- **Methods**:
  - `logActivity(Model $subject, string $event, ?User $causer, array $changes)`: Create activity record
  - `getActivityFeed(Model $subject, array $filters)`: Retrieve filtered activities
  - `searchActivities(string $keyword, array $filters)`: Search activity content

### Observers

#### TaskObserver (Enhanced)
- **Events**:
  - `created`: Log activity, invalidate AI summaries
  - `updated`: Log changes, update parent completion, invalidate summaries
  - `deleted`: Log deletion, cancel reminders, invalidate summaries
  - `restored`: Log restoration, invalidate summaries

#### NoteObserver (Enhanced)
- **Events**:
  - `created`: Create history record, invalidate AI summaries
  - `updated`: Create history record, invalidate summaries
  - `deleted`: Preserve history, invalidate summaries
  - `restored`: Create history record, invalidate summaries

### Jobs

#### SendTaskReminderJob
- **Purpose**: Send scheduled task reminders
- **Payload**: `TaskReminder $reminder`
- **Process**: Send notification, mark as sent, handle failures

#### GenerateRecurringTaskJob
- **Purpose**: Create next instance of recurring tasks
- **Payload**: `Task $task`
- **Process**: Check recurrence pattern, create new task, update recurrence state

#### ProcessTaskDelegationJob
- **Purpose**: Handle delegation notifications and state changes
- **Payload**: `TaskDelegation $delegation`
- **Process**: Send notifications, update assignees, log activity

## Data Models

### Task Entity Relationships

```
Task
├── parent (BelongsTo Task)
├── subtasks (HasMany Task)
├── template (BelongsTo TaskTemplate)
├── assignees (BelongsToMany User)
├── dependencies (BelongsToMany Task)
├── dependents (BelongsToMany Task)
├── categories (BelongsToMany TaskCategory)
├── checklistItems (HasMany TaskChecklistItem)
├── comments (HasMany TaskComment)
├── timeEntries (HasMany TaskTimeEntry)
├── reminders (HasMany TaskReminder)
├── recurrence (HasOne TaskRecurrence)
├── delegations (HasMany TaskDelegation)
├── notes (MorphToMany Note)
├── companies (MorphToMany Company)
├── opportunities (MorphToMany Opportunity)
├── people (MorphToMany People)
├── cases (MorphToMany SupportCase)
├── leads (MorphToMany Lead)
└── projects (BelongsToMany Project)
```

### Note Entity Relationships

```
Note
├── histories (HasMany NoteHistory)
├── attachments (MorphMany Media)
├── companies (MorphToMany Company)
├── people (MorphToMany People)
├── opportunities (MorphToMany Opportunity)
├── cases (MorphToMany SupportCase)
├── leads (MorphToMany Lead)
├── tasks (MorphToMany Task)
└── deliveries (MorphToMany Delivery)
```

### Activity Entity Relationships

```
Activity
├── subject (MorphTo)
├── causer (BelongsTo User)
└── team (BelongsTo Team)
```

### Database Schema Considerations

**Indexes for Performance**:
- `tasks.team_id, tasks.deleted_at` (team scoping with soft deletes)
- `tasks.parent_id` (subtask queries)
- `task_user.task_id, task_user.user_id` (assignee lookups)
- `task_dependencies.task_id, task_dependencies.depends_on_task_id` (dependency checks)
- `task_reminders.remind_at, task_reminders.sent_at` (reminder processing)
- `task_time_entries.user_id, task_time_entries.started_at` (overlap validation)
- `notes.team_id, notes.visibility, notes.deleted_at` (visibility filtering)
- `activities.subject_type, activities.subject_id, activities.created_at` (activity feeds)
- `activities.team_id, activities.event` (filtered activity queries)

**Polymorphic Pivot Tables**:
- `noteables` (note_id, noteable_type, noteable_id)
- `taskables` (task_id, taskable_type, taskable_id)

## Correctness Properties

*A property is a characteristic or behavior that should hold true across all valid executions of a system—essentially, a formal statement about what the system should do. Properties serve as the bridge between human-readable specifications and machine-verifiable correctness guarantees.*


### Property Reflection

After reviewing all testable properties from the prework analysis, several opportunities for consolidation emerge:

**Redundancy Analysis:**

1. **Task Creation Properties (1.1-1.5)** can be consolidated into a single comprehensive property that validates all aspects of task creation
2. **Assignee Management (2.1-2.4)** can be combined into properties about assignee relationship management
3. **Status/Priority Validation (4.1-4.2)** are essentially the same pattern applied to different fields - can be generalized
4. **Status/Priority Filtering (4.3-4.4)** follow the same pattern - can be generalized
5. **Category Management (5.1-5.5)** can be consolidated into fewer properties about many-to-many relationships
6. **Note Creation (7.1, 7.4, 7.5)** can be combined into a single comprehensive property
7. **File Attachment Management (8.1-8.5)** can be consolidated into properties about attachment lifecycle
8. **Note Visibility (9.1-9.5)** can be consolidated into a single property about access control
9. **Note Categories (10.1-10.5)** can be consolidated into fewer properties
10. **Polymorphic Linking (20.1-20.3)** are the same pattern for different entity types - can be generalized
11. **Soft Delete Operations (24.1-24.5)** follow the same pattern for tasks and notes - can be generalized

**Consolidated Property Set:**

After reflection, we'll focus on unique, high-value properties that provide comprehensive validation without redundancy.

### Correctness Properties

Property 1: Task creation with all fields
*For any* valid task data including title, description, status, priority, dates, duration, team context, creator, and optional parent, creating a task should result in a task record with all fields correctly populated and relationships established
**Validates: Requirements 1.1, 1.2, 1.3, 1.4, 1.5**

Property 2: Assignee relationship management
*For any* task and set of users, assigning the users to the task should create all assignee relationships, and removing assignees should delete those relationships
**Validates: Requirements 2.1, 2.4**

Property 3: Assignee task visibility
*For any* user, querying their assigned tasks should return exactly the set of tasks where they are an assignee
**Validates: Requirements 2.3**

Property 4: Custom field validation
*For any* custom field (status, priority, due date, etc.) and value, attempting to set an invalid value should be rejected, and setting a valid value should succeed
**Validates: Requirements 3.1, 4.1, 4.2**

Property 5: Task filtering by custom fields
*For any* set of tasks with various custom field values, filtering by specific values should return only tasks matching those values
**Validates: Requirements 4.3, 4.4**

Property 6: Task sorting by priority
*For any* set of tasks with different priorities, sorting by priority should order tasks according to their priority ranking
**Validates: Requirements 4.5**

Property 7: Category relationship management
*For any* task and set of categories, assigning categories should create all relationships, and category deletion should remove all related task-category relationships
**Validates: Requirements 5.1, 5.2, 5.5**

Property 8: Category filtering
*For any* set of tasks with various categories, filtering by specific categories should return only tasks associated with those categories
**Validates: Requirements 5.3**

Property 9: Recurrence pattern storage and generation
*For any* valid recurrence pattern (frequency, interval, end conditions), creating a recurring task should store the pattern correctly, and when the due date passes, the next instance should be generated according to the pattern
**Validates: Requirements 6.1, 6.2, 6.3**

Property 10: Note creation with all fields
*For any* valid note data including title, body, category, visibility, creator, and timestamp, creating a note should result in a note record with all fields correctly populated
**Validates: Requirements 7.1, 7.4, 7.5**

Property 11: Polymorphic note attachment
*For any* note and CRM entity (company, opportunity, case, lead, task, delivery), attaching the note should create a polymorphic relationship, and querying the entity should return all attached notes ordered by creation date
**Validates: Requirements 7.2, 7.3**

Property 12: Note attachment lifecycle
*For any* note and set of files, uploading files should store them in the configured disk, all files should be retrievable with download links, and deleting the note should remove all file attachments from storage
**Validates: Requirements 8.1, 8.2, 8.3, 8.4**

Property 13: Note visibility access control
*For any* note with a specific visibility level (private, internal, external) and user, access should be granted only if the user's role matches the visibility level (private = creator only, internal = team members, external = team + portal users)
**Validates: Requirements 9.1, 9.2, 9.3, 9.4, 9.5**

Property 14: Note category management
*For any* note, if no category is specified, it should default to "General", and filtering notes by category should return only notes matching that category
**Validates: Requirements 10.1, 10.2, 10.4**

Property 15: Note history tracking
*For any* note edit, a history record should be created capturing the event type, user, timestamp, and changed data, and viewing history should return all records in chronological order
**Validates: Requirements 11.1, 11.2, 11.3**

Property 16: Note history preservation
*For any* note, deleting and restoring the note should preserve all history records including the deletion event
**Validates: Requirements 11.4, 11.5**

Property 17: Activity event logging
*For any* CRM entity operation (create, update, delete), an activity event should be recorded with event type, causer, timestamp, and changed attributes
**Validates: Requirements 12.1, 12.3**

Property 18: Activity feed ordering and display
*For any* entity, viewing its activity feed should return all events in reverse chronological order with causer information and change details
**Validates: Requirements 12.2, 12.4, 12.5**

Property 19: Activity filtering
*For any* set of activities, applying filters for event type, date range, user, or keyword should return only events matching all specified criteria
**Validates: Requirements 13.1, 13.2, 13.3, 13.4, 13.5**

Property 20: Task dependency blocking
*For any* task with dependencies, if any dependency is incomplete, the task should be marked as blocked, and attempting to complete the blocked task should be prevented with an error
**Validates: Requirements 14.2, 14.3**

Property 21: Task dependency unblocking
*For any* blocked task, when all dependencies are completed, the blocked status should be removed
**Validates: Requirements 14.4**

Property 22: Checklist item management
*For any* task, adding checklist items should create them with correct position ordering, marking items complete should update their status, and when all items are complete, the task's completion percentage should be updated
**Validates: Requirements 15.1, 15.2, 15.3, 15.5**

Property 23: Task comment management
*For any* task, adding a comment should create it with user, timestamp, and content, and viewing comments should return them in chronological order
**Validates: Requirements 16.1, 16.2**

Property 24: Time entry management and calculation
*For any* task, logging time should create entries with all fields, and calculating totals should correctly sum duration and billing amounts (billable hours × billing rate)
**Validates: Requirements 17.1, 17.2, 17.3, 17.5**

Property 25: Task delegation workflow
*For any* task delegation, a delegation record should be created with delegator, delegatee, and timestamp, the delegatee should be added as an assignee, and the delegation history should be retrievable
**Validates: Requirements 18.1, 18.2, 18.4**

Property 26: Task template instantiation
*For any* task template with properties and subtasks, creating a task from the template should populate all fields from the template and create all subtasks
**Validates: Requirements 19.1, 19.2, 19.5**

Property 27: Polymorphic task linking
*For any* task and CRM entity (company, opportunity, case, lead), linking the task should create a polymorphic relationship, and filtering tasks by entity should return only linked tasks
**Validates: Requirements 20.1, 20.2, 20.3, 20.5**

Property 28: Task completion percentage calculation
*For any* task without subtasks, the percent_complete should be its own value; for tasks with subtasks, it should be the average of subtask percentages; and when a subtask changes, the parent should update automatically
**Validates: Requirements 21.1, 21.2, 21.3**

Property 29: Task completion sets percentage to 100
*For any* task, marking it as complete should set percent_complete to 100
**Validates: Requirements 21.4**

Property 30: Dependency date constraint validation
*For any* task with dependencies, the earliest possible start date should be calculated as the maximum of all dependency end dates, and setting a start date before this should trigger a validation warning
**Validates: Requirements 22.1, 22.2**

Property 31: Milestone task management
*For any* task marked as a milestone, the is_milestone flag should be set, filtering for milestones should return only milestone tasks, and milestone completion status should be calculable
**Validates: Requirements 23.1, 23.3, 23.5**

Property 32: Soft delete and restore
*For any* task or note, deleting should set deleted_at timestamp, default queries should exclude soft deleted records, querying deleted items should return them, and restoring should clear deleted_at and restore functionality
**Validates: Requirements 24.1, 24.2, 24.3, 24.4, 24.5**

## Error Handling

### Validation Errors

**Task Validation**:
- Invalid status/priority values → Reject with clear error message listing valid options
- Start date before dependency end dates → Warning message with constraint details
- Completing blocked task → DomainException with message "Complete dependent tasks first to clear this dependency"
- Invalid recurrence pattern → Validation error with pattern requirements

**Time Entry Validation**:
- Overlapping time entries → DomainException "Time entry overlaps with an existing entry for this user"
- Duplicate time entries → DomainException "This time entry already exists"
- Invalid duration → Validation error "Duration must be positive"

**Note Validation**:
- Invalid visibility level → Validation error listing valid options
- Invalid category → Validation error listing valid categories
- Missing required fields → Validation error specifying required fields

**Access Control Errors**:
- Unauthorized note access → 403 Forbidden with message "You don't have permission to view this note"
- Unauthorized task modification → 403 Forbidden with message "You don't have permission to modify this task"

### System Errors

**Database Errors**:
- Connection failures → Retry with exponential backoff, log error, display user-friendly message
- Constraint violations → Rollback transaction, log error, display specific constraint message
- Deadlocks → Retry transaction up to 3 times, then fail with error message

**Storage Errors**:
- File upload failures → Log error, display message "Failed to upload file. Please try again"
- File deletion failures → Log error, continue operation (orphaned files cleaned by scheduled job)
- Disk space issues → Log critical error, notify administrators, display message to user

**Queue Errors**:
- Job failures → Retry with exponential backoff (3 attempts), log failure, send admin notification
- Reminder send failures → Mark reminder as failed, log error, retry on next scheduled run
- Recurring task generation failures → Log error, mark recurrence as needing attention

### Recovery Strategies

**Transaction Rollback**:
- All multi-step operations wrapped in database transactions
- On error, rollback to maintain data consistency
- Log rollback events for debugging

**Graceful Degradation**:
- If notification sending fails, log error but don't block operation
- If activity logging fails, log error but don't block operation

**User Feedback**:
- All errors display user-friendly messages
- Technical details logged for debugging
- Validation errors include specific field information and correction guidance

## Testing Strategy

### Unit Testing

**Model Tests**:
- Task model methods (isBlocked, calculatePercentComplete, violatesDependencyConstraints, etc.)
- Note model methods (isPrivate, body, plainBody, categoryLabel, etc.)
- Activity model relationships and scopes
- Custom field value resolution and caching
- Time entry validation logic

**Service Tests**:
- TaskReminderService scheduling and sending logic
- TaskRecurrenceService pattern calculation and instance generation
- TaskDelegationService workflow and notifications
- ActivityLogService event recording and filtering

**Observer Tests**:
- TaskObserver event handling and side effects
- NoteObserver history creation and AI summary invalidation
- Proper transaction handling in observers

### Property-Based Testing

**Testing Framework**: We will use [Pest](https://pestphp.com/) with the [pest-plugin-arch](https://github.com/pestphp/pest-plugin-arch) for architectural testing and custom property-based testing helpers.

**Configuration**: Each property-based test will run a minimum of 100 iterations to ensure comprehensive coverage across the input space.

**Test Tagging**: Each property-based test will include a comment explicitly referencing the correctness property using the format: `**Feature: tasks-activities-enhancement, Property {number}: {property_text}**`

**Property Test Coverage**:

Each of the 33 correctness properties defined above will be implemented as a property-based test. The tests will:

1. Generate random valid inputs for the property being tested
2. Execute the system operation
3. Assert that the property holds for all generated inputs
4. Use appropriate generators to cover edge cases (empty sets, boundary values, etc.)

**Example Property Test Structure**:

```php
it('validates Property 1: Task creation with all fields', function () {
    // **Feature: tasks-activities-enhancement, Property 1: Task creation with all fields**
    
    // Generate 100 random task configurations
    foreach (range(1, 100) as $iteration) {
        $taskData = generateRandomTaskData();
        $team = Team::factory()->create();
        $user = User::factory()->create();
        
        actingAs($user)->withTeam($team);
        
        $task = Task::create($taskData);
        
        // Assert all fields are correctly populated
        expect($task->title)->toBe($taskData['title']);
        expect($task->team_id)->toBe($team->id);
        expect($task->creator_id)->toBe($user->id);
        // ... additional assertions
    }
})->group('property-based', 'tasks');
```

**Generator Strategies**:

- **Task Data**: Random titles, descriptions, dates (past, present, future), priorities, statuses
- **User Sets**: Empty, single user, multiple users, large sets
- **Relationships**: No relationships, single relationships, multiple relationships, circular references
- **Custom Field Values**: Valid values, boundary values, null values
- **Date Ranges**: Past, present, future, overlapping, non-overlapping
- **File Uploads**: Single file, multiple files, various MIME types, size variations

### Integration Testing

**Filament Resource Tests**:
- Task creation, editing, deletion through Filament UI
- Filtering, sorting, searching tasks
- Bulk actions on tasks
- Relation manager interactions

**API Tests** (if applicable):
- RESTful endpoints for task/note CRUD
- Authentication and authorization
- Rate limiting and throttling

**End-to-End Tests**:
- Complete task workflow (create → assign → update → complete)
- Note attachment workflow (create → attach files → link to entities)
- Recurring task generation over time
- Delegation workflow (delegate → accept → complete)

### Performance Testing

**Query Performance**:
- Task list queries with various filters (target: <100ms for 10,000 tasks)
- Activity feed queries (target: <200ms for 1,000 activities)
- Dependency checking (target: <50ms for 100 dependencies)

**Bulk Operations**:
- Bulk task assignment (target: <1s for 100 tasks)
- Bulk status updates (target: <1s for 100 tasks)
- Bulk deletion (target: <1s for 100 tasks)

**Concurrent Operations**:
- Multiple users updating same task
- Concurrent time entry creation
- Concurrent reminder processing

### Test Data Management

**Factories**:
- Comprehensive factories for all models with realistic data
- Relationship factories for complex scenarios
- State factories for different task/note states

**Seeders**:
- Development seeder with representative data
- Test seeder with edge cases
- Performance test seeder with large datasets

## Implementation Notes

### Custom Fields Integration

The system heavily relies on the existing custom fields system for flexible data storage:

- Task status, priority, and due date are stored as custom fields
- Note body content is stored as a custom field
- Custom field caching is implemented to avoid N+1 queries
- Custom field validation is integrated into model save operations

### Multi-Tenancy

All models use the `HasTeam` trait for team scoping:

- Queries automatically scope to current team
- Cross-team data access is prevented
- Team context is required for all operations

### Soft Deletes

Tasks and Notes use soft deletes for data recovery:

- `deleted_at` timestamp marks deletion
- Default queries exclude soft deleted records
- Explicit queries can include deleted records
- Restore functionality clears `deleted_at`

### Activity Logging

Activity logging is automatic through observers:

- All CRUD operations trigger activity events
- Changes are captured in JSON format
- Activity feed is queryable and filterable
- Performance impact is minimized through async processing

### Notification Strategy

Notifications are queued for async processing:

- Task assignment notifications
- Reminder notifications
- Delegation notifications
- Milestone completion notifications
- Comment mention notifications

### Performance Optimizations

**Eager Loading**:
- Relationships are eager loaded in list queries
- Custom fields are loaded with options
- Assignees and categories are preloaded

**Caching**:
- Custom field definitions cached per tenant
- Category lists cached
- User lists cached for assignee selection

**Indexing**:
- Composite indexes on frequently queried columns
- Indexes on foreign keys and polymorphic relationships
- Full-text indexes for search functionality

**Query Optimization**:
- Pagination for large result sets
- Selective column loading
- Subquery optimization for custom field filters

### Security Considerations

**Authorization**:
- Policy-based authorization for all operations
- Team-based access control
- Note visibility enforcement
- Private note access restrictions

**Input Validation**:
- All user inputs validated
- File upload restrictions (MIME types, size limits)
- SQL injection prevention through Eloquent
- XSS prevention through output escaping

**Data Integrity**:
- Foreign key constraints
- Transaction wrapping for multi-step operations
- Validation before save
- Dependency constraint enforcement
