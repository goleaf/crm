# Design Document

## Overview

The Tasks & Activities Enhancement builds upon the existing foundational models (Task, Note, Activity) to provide a comprehensive task management, note-taking, and activity tracking system with advanced features including timeline visualization, workload management, workflow automation, and smart suggestions. The design leverages Laravel's Eloquent ORM, Filament v4.3+ for the UI layer, and the existing custom fields system for flexible data storage. The architecture emphasizes data integrity, performance, and user experience through proper relationship management, validation, real-time updates, and intelligent automation.

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

**SavedFilter**
- Fields: `user_id`, `team_id`, `name`, `criteria`, `is_shared`, `shared_with`
- Relationships: `user()`, `team()`, `sharedUsers()`
- Methods: `applyCriteria(Builder $query)`, `share(array $userIds)`

**WorkflowDefinition**
- Fields: `team_id`, `name`, `trigger_type`, `trigger_conditions`, `actions`, `is_active`
- Relationships: `team()`, `executions()`
- Methods: `evaluateTrigger(Model $subject, string $event)`, `execute(Model $subject)`

**WorkflowExecution**
- Fields: `workflow_id`, `subject_type`, `subject_id`, `executed_at`, `status`, `created_tasks`, `error_message`
- Relationships: `workflow()`, `subject()`, `tasks()`

**EmployeeCapacity**
- Fields: `user_id`, `team_id`, `hours_per_day`, `hours_per_week`, `effective_from`, `effective_to`
- Relationships: `user()`, `team()`
- Methods: `getAvailableHours(Carbon $startDate, Carbon $endDate)`

**TaskPattern**
- Fields: `user_id`, `team_id`, `pattern_type`, `pattern_data`, `confidence_score`, `last_updated`
- Relationships: `user()`, `team()`
- Methods: `updatePattern(Task $task)`, `getSuggestions(Task $task)`

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

#### TimelineService
- **Responsibilities**: Generate timeline/Gantt chart data for task visualization
- **Methods**:
  - `getTimelineData(array $filters, string $scale)`: Generate timeline data for specified time scale
  - `calculateTaskPosition(Task $task, string $scale)`: Calculate task bar position and width
  - `getDependencyConnections(Collection $tasks)`: Generate dependency connector coordinates
  - `updateTaskDates(Task $task, Carbon $newStart, Carbon $newEnd)`: Update task dates from drag-and-drop
  - `validateDateChange(Task $task, Carbon $newStart, Carbon $newEnd)`: Validate date changes against dependencies

#### FilterService
- **Responsibilities**: Manage saved filters and complex filter combinations
- **Methods**:
  - `createSavedFilter(string $name, array $criteria, User $user)`: Create and store a saved filter
  - `applySavedFilter(SavedFilter $filter)`: Apply saved filter criteria to query
  - `shareSavedFilter(SavedFilter $filter, array $userIds)`: Share filter with team members
  - `combineCriteria(array $filters)`: Combine multiple filter criteria with AND logic
  - `getFilterResultCount(array $criteria)`: Calculate matching task count without executing full query

#### WorkloadService
- **Responsibilities**: Calculate and manage employee workload and capacity
- **Methods**:
  - `calculateEmployeeWorkload(User $employee, Carbon $startDate, Carbon $endDate)`: Calculate total assigned hours
  - `getWorkloadDistribution(User $employee, string $period)`: Get workload by time period (day/week/month)
  - `identifyOverloadedEmployees(Team $team, float $capacityThreshold)`: Find employees exceeding capacity
  - `getEmployeeAvailability(User $employee, Carbon $startDate, Carbon $endDate)`: Calculate available hours
  - `suggestReassignment(Task $task)`: Suggest employees with available capacity for task reassignment

#### WorkflowAutomationService
- **Responsibilities**: Handle workflow triggers and automated task creation
- **Methods**:
  - `evaluateTriggers(Model $subject, string $event)`: Check if any workflow triggers match the event
  - `executeWorkflow(Workflow $workflow, Model $subject)`: Execute workflow actions
  - `createTasksFromWorkflow(Workflow $workflow, Model $subject)`: Create tasks defined in workflow
  - `establishWorkflowDependencies(Collection $tasks, array $dependencyMap)`: Set up task dependencies from workflow
  - `trackWorkflowExecution(Workflow $workflow, Model $subject)`: Log workflow execution for audit trail

#### TaskExportService
- **Responsibilities**: Export tasks to various external formats
- **Methods**:
  - `exportToCSV(Collection $tasks)`: Generate CSV file with task data
  - `exportToExcel(Collection $tasks)`: Generate Excel file with formatted columns
  - `exportToICalendar(Collection $tasks)`: Generate ICS file with tasks as calendar events
  - `includeSubtaskHierarchy(Collection $tasks)`: Add subtask relationships to export
  - `applyExportFilters(array $filters)`: Filter tasks before export

#### SmartSuggestionService
- **Responsibilities**: Provide AI-powered task suggestions based on patterns
- **Methods**:
  - `suggestSimilarTasks(Task $task)`: Analyze patterns and suggest similar tasks
  - `suggestAssignees(Task $task)`: Recommend assignees based on historical assignments
  - `suggestDuration(Task $task)`: Estimate realistic duration from historical data
  - `suggestPriorityAdjustment(Task $task)`: Recommend priority changes for overdue tasks
  - `suggestDependencies(Task $task)`: Identify potential dependency relationships
  - `analyzeTaskPatterns(User $user)`: Build pattern model from user's task history

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

#### EvaluateWorkflowTriggersJob
- **Purpose**: Check and execute workflow triggers for events
- **Payload**: `Model $subject`, `string $event`
- **Process**: Evaluate trigger conditions, execute matching workflows, create tasks

#### CalculateWorkloadMetricsJob
- **Purpose**: Update employee workload calculations periodically
- **Payload**: `Team $team`
- **Process**: Calculate workload for all employees, identify overloaded employees, cache results

#### UpdateTaskPatternsJob
- **Purpose**: Analyze completed tasks and update pattern models
- **Payload**: `User $user`
- **Process**: Analyze task history, update pattern data, calculate confidence scores

#### GenerateSmartSuggestionsJob
- **Purpose**: Generate task suggestions based on patterns
- **Payload**: `User $user`
- **Process**: Analyze patterns, generate suggestions, cache for quick retrieval

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
- `tasks.start_date, tasks.end_date` (timeline queries)
- `tasks.project_id, tasks.status` (project task filtering)
- `task_user.task_id, task_user.user_id` (assignee lookups)
- `task_user.user_id, task_user.created_at` (workload calculations)
- `task_dependencies.task_id, task_dependencies.depends_on_task_id` (dependency checks)
- `task_reminders.remind_at, task_reminders.sent_at` (reminder processing)
- `task_time_entries.user_id, task_time_entries.started_at` (overlap validation and workload)
- `notes.team_id, notes.visibility, notes.deleted_at` (visibility filtering)
- `activities.subject_type, activities.subject_id, activities.created_at` (activity feeds)
- `activities.team_id, activities.event` (filtered activity queries)
- `saved_filters.user_id, saved_filters.team_id` (filter lookups)
- `saved_filters.is_shared` (shared filter queries)
- `workflow_definitions.team_id, workflow_definitions.is_active` (workflow evaluation)
- `workflow_definitions.trigger_type` (trigger matching)
- `workflow_executions.workflow_id, workflow_executions.executed_at` (execution history)
- `employee_capacity.user_id, employee_capacity.effective_from, employee_capacity.effective_to` (capacity lookups)
- `task_patterns.user_id, task_patterns.pattern_type` (pattern-based suggestions)

**Polymorphic Pivot Tables**:
- `noteables` (note_id, noteable_type, noteable_id)
- `taskables` (task_id, taskable_type, taskable_id)

**New Tables**:
- `saved_filters` (user_id, team_id, name, criteria, is_shared, shared_with, created_at, updated_at)
- `workflow_definitions` (team_id, name, trigger_type, trigger_conditions, actions, is_active, created_at, updated_at)
- `workflow_executions` (workflow_id, subject_type, subject_id, executed_at, status, created_tasks, error_message)
- `employee_capacity` (user_id, team_id, hours_per_day, hours_per_week, effective_from, effective_to)
- `task_patterns` (user_id, team_id, pattern_type, pattern_data, confidence_score, last_updated)

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

Property 33: Timeline data generation
*For any* set of tasks with start and end dates, generating timeline data should correctly calculate task bar positions, widths, and dependency connector coordinates for the specified time scale
**Validates: Requirements 26.1, 26.2, 26.4**

Property 34: Timeline date updates
*For any* task, dragging its timeline bar should update the task's start and end dates, and the update should be validated against dependency constraints
**Validates: Requirements 26.3**

Property 35: Saved filter management
*For any* filter criteria combination, saving the filter should store all criteria, applying the saved filter should restore and execute the criteria, and sharing should make the filter available to specified users
**Validates: Requirements 27.1, 27.2, 27.3, 27.4**

Property 36: Filter result counting
*For any* filter criteria, calculating the result count should return the exact number of matching tasks without executing the full query
**Validates: Requirements 27.5**

Property 37: Workload calculation
*For any* employee and time period, calculating workload should sum all assigned task hours, and the calculation should update in real-time when tasks are assigned or reassigned
**Validates: Requirements 28.1, 28.2, 28.5**

Property 38: Capacity threshold detection
*For any* team and capacity threshold, identifying overloaded employees should return all employees whose assigned hours exceed the threshold
**Validates: Requirements 28.3**

Property 39: Project timeline updates
*For any* project with linked tasks, when a task's dates change, the project's start and end dates should be recalculated as the minimum start date and maximum end date of all linked tasks
**Validates: Requirements 29.1, 29.2**

Property 40: Workflow trigger evaluation
*For any* workflow with trigger conditions and subject event, evaluating triggers should correctly match conditions and execute the workflow, creating all defined tasks with proper dependencies
**Validates: Requirements 30.1, 30.2, 30.3**

Property 41: Workflow task attribution
*For any* task created by a workflow, the task should store the originating workflow reference and display it when viewed
**Validates: Requirements 30.5**

Property 42: Bulk task updates
*For any* set of selected tasks and update operation (status, assignee, dates), the bulk update should apply changes to all selected tasks and send appropriate notifications
**Validates: Requirements 31.1, 31.2, 31.3, 31.4**

Property 43: Task export with filtering
*For any* export format (CSV, Excel, iCalendar) and filter criteria, exporting should generate a file containing only tasks matching the filter criteria with all specified fields
**Validates: Requirements 32.1, 32.2, 32.3, 32.4**

Property 44: Subtask hierarchy in exports
*For any* task export including subtasks, the export should preserve and represent the parent-child hierarchy structure
**Validates: Requirements 32.5**

Property 45: Pattern-based assignee suggestions
*For any* task, analyzing historical assignment patterns should suggest assignees who have been assigned to similar tasks in the past
**Validates: Requirements 33.2**

Property 46: Duration estimation from history
*For any* task, analyzing historical duration data for similar tasks should suggest a realistic duration estimate based on the average completion time of comparable tasks duration estimate
**Validates: Requirements 33.3**

Property 47: Dependency relationship suggestions
*For any* task, analyzing historical task patterns should suggest potential dependency relationships based on task sequences that have occurred together
**Validates: Requirements 33.5**

Property 48: User mention notification
*For any* task description or comment containing @mentions, the system should create mention records for all mentioned users, send notifications to those users, and allow users to query all tasks where they are mentioned
**Validates: Requirements 25.1, 25.2, 25.3, 25.5**

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

## Deployment & Migration Strategy

### Phased Rollout

**Phase 1: Foundation (Weeks 1-2)**
- Deploy enhanced Task and Note models
- Deploy core services (Reminder, Recurrence, Delegation)
- Deploy observers and jobs
- Enable for internal testing team only

**Phase 2: Core Features (Weeks 3-4)**
- Deploy Filament resources and relation managers
- Deploy notification system
- Deploy activity logging enhancements
- Enable for pilot user group (20% of users)

**Phase 3: Advanced Features (Weeks 5-6)**
- Deploy timeline/Gantt view
- Deploy workload management
- Deploy workflow automation
- Enable for 50% of users

**Phase 4: Smart Features (Week 7)**
- Deploy smart suggestions
- Deploy bulk operations
- Deploy export functionality
- Enable for all users

### Data Migration

**Existing Tasks**:
- No migration needed (backward compatible)
- Custom fields already in place
- Relationships preserved

**Existing Notes**:
- No migration needed (backward compatible)
- Visibility defaults to "internal"
- Categories default to "General"

**New Tables**:
- All new tables created via migrations
- No data backfill required
- Indexes added without downtime

### Rollback Plan

**Database Rollback**:
- All migrations reversible
- Rollback scripts tested in staging
- Data preserved during rollback

**Feature Flags**:
- Each major feature behind feature flag
- Can disable features without code deployment
- Gradual rollout with instant rollback capability

**Monitoring**:
- Error rate monitoring per feature
- Performance monitoring for new queries
- User feedback collection
- Automatic rollback triggers if error rate > 5%

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
