# Implementation Plan

- [x] 1. Set up testing infrastructure and property-based testing framework ✅ **COMPLETE**
  - ✅ Install and configure Pest with property-based testing helpers
  - ✅ Create test data generators for tasks, notes, activities, and related entities
  - ✅ Set up test database with proper seeding
  - ✅ Create base test case classes for property-based tests
  - ✅ All 38 unit tests passing (721 assertions)
  - ✅ Complete documentation and examples
  - _Requirements: All (testing foundation)_
  - _See: TEST_REPORT.md for detailed results_

- [-] 2. Enhance Task model with missing functionality
- [x] 2.1 Implement task reminder scheduling methods
  - Add methods to schedule, cancel, and query reminders
  - Integrate with TaskReminderService
  - _Requirements: 3.2, 3.3, 3.5_

- [ ] 2.2 Write property test for reminder management
  - **Property 4: Custom field validation**
  - **Validates: Requirements 3.1, 4.1, 4.2**

- [x] 2.3 Implement task recurrence pattern methods
  - Add methods to create and manage recurrence patterns
  - Integrate with TaskRecurrenceService
  - _Requirements: 6.1, 6.2, 6.3_

- [ ] 2.4 Write property test for recurrence pattern storage and generation
  - **Property 9: Recurrence pattern storage and generation**
  - **Validates: Requirements 6.1, 6.2, 6.3**

- [x] 2.5 Implement task delegation methods
  - Add methods to delegate, accept, and decline delegations
  - Integrate with TaskDelegationService
  - _Requirements: 18.1, 18.2, 18.3, 18.4, 18.5_

- [ ] 2.6 Write property test for task delegation workflow
  - **Property 25: Task delegation workflow**
  - **Validates: Requirements 18.1, 18.2, 18.4**

- [x] 2.7 Enhance task completion percentage calculation
  - Improve calculatePercentComplete to handle edge cases
  - Add automatic parent update on subtask changes
  - _Requirements: 21.1, 21.2, 21.3, 21.4_

- [ ] 2.8 Write property test for completion percentage calculation
  - **Property 28: Task completion percentage calculation**
  - **Validates: Requirements 21.1, 21.2, 21.3**

- [ ] 2.9 Write property test for completion sets percentage to 100
  - **Property 29: Task completion sets percentage to 100**
  - **Validates: Requirements 21.4**

- [ ] 2.10 Implement milestone task functionality
  - Add milestone filtering and status tracking
  - _Requirements: 23.1, 23.3, 23.5_

- [ ] 2.11 Write property test for milestone task management
  - **Property 31: Milestone task management**
  - **Validates: Requirements 23.1, 23.3, 23.5**

- [ ] 3. Enhance Note model with missing functionality
- [ ] 3.1 Implement note history tracking
  - Create NoteHistory model if not exists
  - Add methods to create and query history records
  - _Requirements: 11.1, 11.2, 11.3, 11.4, 11.5_

- [ ] 3.2 Write property test for note history tracking
  - **Property 15: Note history tracking**
  - **Validates: Requirements 11.1, 11.2, 11.3**

- [ ] 3.3 Write property test for note history preservation
  - **Property 16: Note history preservation**
  - **Validates: Requirements 11.4, 11.5**

- [ ] 3.4 Implement note visibility access control
  - Add scope methods for visibility filtering
  - Implement access control checks
  - _Requirements: 9.1, 9.2, 9.3, 9.4, 9.5_

- [ ] 3.5 Write property test for note visibility access control
  - **Property 13: Note visibility access control**
  - **Validates: Requirements 9.1, 9.2, 9.3, 9.4, 9.5**

- [ ] 3.6 Implement note category management
  - Add category default logic
  - Add category filtering methods
  - _Requirements: 10.1, 10.2, 10.4_

- [ ] 3.7 Write property test for note category management
  - **Property 14: Note category management**
  - **Validates: Requirements 10.1, 10.2, 10.4**

- [ ] 4. Create service classes for business logic
- [ ] 4.1 Implement TaskReminderService
  - Create service with methods for scheduling, sending, and canceling reminders
  - Implement reminder notification logic
  - _Requirements: 3.2, 3.3, 3.5_

- [ ] 4.2 Write unit tests for TaskReminderService
  - Test scheduling logic
  - Test reminder sending
  - Test cancellation logic
  - _Requirements: 3.2, 3.3, 3.5_

- [ ] 4.3 Implement TaskRecurrenceService
  - Create service with methods for pattern calculation and instance generation
  - Implement next occurrence date calculation
  - _Requirements: 6.1, 6.2, 6.3_

- [ ] 4.4 Write unit tests for TaskRecurrenceService
  - Test pattern validation
  - Test next instance generation
  - Test date calculation
  - _Requirements: 6.1, 6.2, 6.3_

- [ ] 4.5 Implement TaskDelegationService
  - Create service with methods for delegation workflow
  - Implement delegation notifications
  - _Requirements: 18.1, 18.2, 18.3, 18.4, 18.5_

- [ ] 4.6 Write unit tests for TaskDelegationService
  - Test delegation creation
  - Test acceptance/decline logic
  - Test notification sending
  - _Requirements: 18.1, 18.2, 18.3, 18.4, 18.5_

- [ ] 4.7 Implement ActivityLogService
  - Create service with methods for event recording and querying
  - Implement activity filtering and search
  - _Requirements: 12.1, 12.2, 12.3, 12.4, 12.5, 13.1, 13.2, 13.3, 13.4, 13.5_

- [ ] 4.8 Write unit tests for ActivityLogService
  - Test event recording
  - Test filtering logic
  - Test search functionality
  - _Requirements: 12.1, 12.2, 12.3, 12.4, 12.5, 13.1, 13.2, 13.3, 13.4, 13.5_

- [ ] 5. Enhance observers for automatic event handling
- [ ] 5.1 Enhance TaskObserver
  - Add reminder cancellation on task completion
  - Add parent completion update on subtask changes
  - _Requirements: 3.5, 21.3_

- [ ] 5.2 Write unit tests for TaskObserver
  - Test reminder cancellation
  - Test parent updates
  - _Requirements: 3.5, 21.3_

- [ ] 5.3 Enhance NoteObserver
  - Add history record creation on updates
  - _Requirements: 11.1_

- [ ] 5.4 Write unit tests for NoteObserver
  - Test history creation
  - _Requirements: 11.1_

- [ ] 6. Create background jobs for async processing
- [ ] 6.1 Implement SendTaskReminderJob
  - Create job to send scheduled reminders
  - Implement retry logic and error handling
  - _Requirements: 3.3_

- [ ] 6.2 Write unit tests for SendTaskReminderJob
  - Test reminder sending
  - Test error handling
  - Test retry logic
  - _Requirements: 3.3_

- [ ] 6.3 Implement GenerateRecurringTaskJob
  - Create job to generate next recurring task instances
  - Implement recurrence pattern logic
  - _Requirements: 6.2, 6.3_

- [ ] 6.4 Write unit tests for GenerateRecurringTaskJob
  - Test instance generation
  - Test pattern handling
  - Test edge cases
  - _Requirements: 6.2, 6.3_

- [ ] 6.5 Implement ProcessTaskDelegationJob
  - Create job to handle delegation notifications
  - Implement delegation state management
  - _Requirements: 18.2, 18.3, 18.5_

- [ ] 6.6 Write unit tests for ProcessTaskDelegationJob
  - Test notification sending
  - Test state updates
  - Test error handling
  - _Requirements: 18.2, 18.3, 18.5_

- [ ] 7. Implement property-based tests for core properties
- [ ] 7.1 Write property test for task creation with all fields
  - **Property 1: Task creation with all fields**
  - **Validates: Requirements 1.1, 1.2, 1.3, 1.4, 1.5**

- [ ] 7.2 Write property test for assignee relationship management
  - **Property 2: Assignee relationship management**
  - **Validates: Requirements 2.1, 2.4**

- [ ] 7.3 Write property test for assignee task visibility
  - **Property 3: Assignee task visibility**
  - **Validates: Requirements 2.3**

- [ ] 7.4 Write property test for task filtering by custom fields
  - **Property 5: Task filtering by custom fields**
  - **Validates: Requirements 4.3, 4.4**

- [ ] 7.5 Write property test for task sorting by priority
  - **Property 6: Task sorting by priority**
  - **Validates: Requirements 4.5**

- [ ] 7.6 Write property test for category relationship management
  - **Property 7: Category relationship management**
  - **Validates: Requirements 5.1, 5.2, 5.5**

- [ ] 7.7 Write property test for category filtering
  - **Property 8: Category filtering**
  - **Validates: Requirements 5.3**

- [ ] 8. Implement property-based tests for note properties
- [ ] 8.1 Write property test for note creation with all fields
  - **Property 10: Note creation with all fields**
  - **Validates: Requirements 7.1, 7.4, 7.5**

- [ ] 8.2 Write property test for polymorphic note attachment
  - **Property 11: Polymorphic note attachment**
  - **Validates: Requirements 7.2, 7.3**

- [ ] 8.3 Write property test for note attachment lifecycle
  - **Property 12: Note attachment lifecycle**
  - **Validates: Requirements 8.1, 8.2, 8.3, 8.4**

- [ ] 9. Implement property-based tests for activity properties
- [ ] 9.1 Write property test for activity event logging
  - **Property 17: Activity event logging**
  - **Validates: Requirements 12.1, 12.3**

- [ ] 9.2 Write property test for activity feed ordering and display
  - **Property 18: Activity feed ordering and display**
  - **Validates: Requirements 12.2, 12.4, 12.5**

- [ ] 9.3 Write property test for activity filtering
  - **Property 19: Activity filtering**
  - **Validates: Requirements 13.1, 13.2, 13.3, 13.4, 13.5**

- [ ] 10. Implement property-based tests for task dependency properties
- [ ] 10.1 Write property test for task dependency blocking
  - **Property 20: Task dependency blocking**
  - **Validates: Requirements 14.2, 14.3**

- [ ] 10.2 Write property test for task dependency unblocking
  - **Property 21: Task dependency unblocking**
  - **Validates: Requirements 14.4**

- [ ] 10.3 Write property test for dependency date constraint validation
  - **Property 30: Dependency date constraint validation**
  - **Validates: Requirements 22.1, 22.2**

- [ ] 11. Implement property-based tests for task component properties
- [ ] 11.1 Write property test for checklist item management
  - **Property 22: Checklist item management**
  - **Validates: Requirements 15.1, 15.2, 15.3, 15.5**

- [ ] 11.2 Write property test for task comment management
  - **Property 23: Task comment management**
  - **Validates: Requirements 16.1, 16.2**

- [ ] 11.3 Implement user mention functionality
  - Add mention detection in task descriptions and comments
  - Create TaskMention model and relationships
  - Implement mention autocomplete
  - _Requirements: 25.1, 25.2, 25.4, 25.5_

- [ ] 11.4 Write property test for user mention notification
  - **Property 48: User mention notification**
  - **Validates: Requirements 25.1, 25.2, 25.3, 25.5**

- [ ] 11.5 Write property test for time entry management and calculation
  - **Property 24: Time entry management and calculation**
  - **Validates: Requirements 17.1, 17.2, 17.3, 17.5**

- [ ] 11.6 Add mention notification to CommentMentionNotification
  - Enhance existing notification to handle @mentions
  - Add mention highlighting in UI
  - _Requirements: 25.3, 25.4_

- [ ] 12. Implement property-based tests for advanced task properties
- [ ] 12.1 Write property test for task template instantiation
  - **Property 26: Task template instantiation**
  - **Validates: Requirements 19.1, 19.2, 19.5**

- [ ] 12.2 Write property test for polymorphic task linking
  - **Property 27: Polymorphic task linking**
  - **Validates: Requirements 20.1, 20.2, 20.3, 20.5**

- [ ] 12.3 Write property test for soft delete and restore
  - **Property 32: Soft delete and restore**
  - **Validates: Requirements 24.1, 24.2, 24.3, 24.4, 24.5**

- [ ] 13. Checkpoint - Ensure all tests pass
  - Ensure all tests pass, ask the user if questions arise.

- [ ] 14. Enhance Filament resources for improved UI
- [ ] 14.1 Enhance TaskResource with new features
  - Add reminder management actions
  - Add delegation actions
  - Add milestone filtering
  - Add recurrence pattern display
  - _Requirements: 3.2, 18.1, 23.3, 6.1_

- [ ] 14.2 Enhance NoteResource with new features
  - Add visibility filtering
  - Add category management
  - Add history viewing
  - _Requirements: 9.4, 10.2, 11.2_

- [ ] 14.3 Create ActivityFeedWidget
  - Create widget to display activity feed
  - Implement filtering and search
  - _Requirements: 12.2, 13.1, 13.2, 13.3, 13.4_

- [ ] 15. Create relation managers for task components
- [ ] 15.1 Create TaskChecklistRelationManager
  - Implement checklist item CRUD
  - Add reordering functionality
  - _Requirements: 15.1, 15.2, 15.3, 15.4_

- [ ] 15.2 Create TaskCommentsRelationManager
  - Implement comment CRUD
  - Add mention detection
  - _Requirements: 16.1, 16.2, 16.3_

- [ ] 15.3 Create TaskTimeEntriesRelationManager
  - Implement time entry CRUD
  - Add billing calculation display
  - _Requirements: 17.1, 17.2, 17.3, 17.4, 17.5_

- [ ] 15.4 Create TaskRemindersRelationManager
  - Implement reminder CRUD
  - Add reminder status display
  - _Requirements: 3.2, 3.3_

- [ ] 15.5 Create TaskDelegationsRelationManager
  - Implement delegation history display
  - Add accept/decline actions
  - _Requirements: 18.1, 18.4_

- [ ] 16. Create Filament actions for task operations
- [ ] 16.1 Create DelegateTaskAction
  - Implement delegation form
  - Add user selection
  - _Requirements: 18.1, 18.2, 18.3_

- [ ] 16.2 Create CreateReminderAction
  - Implement reminder form
  - Add date/time selection
  - _Requirements: 3.2_

- [ ] 16.3 Create SetRecurrenceAction
  - Implement recurrence pattern form
  - Add pattern validation
  - _Requirements: 6.1_

- [ ] 16.4 Create MarkAsMilestoneAction
  - Implement milestone toggle
  - Add confirmation
  - _Requirements: 23.1_

- [ ] 17. Implement notification classes
- [ ] 17.1 Create TaskAssignedNotification
  - Implement notification for task assignment
  - Add action buttons
  - _Requirements: 2.2_

- [ ] 17.2 Create TaskReminderNotification
  - Implement notification for task reminders
  - Add snooze action
  - _Requirements: 3.3_

- [ ] 17.3 Create TaskDelegatedNotification
  - Implement notification for task delegation
  - Add accept/decline actions
  - _Requirements: 18.3_

- [ ] 17.4 Create MilestoneCompletedNotification
  - Implement notification for milestone completion
  - Add view action
  - _Requirements: 23.4_

- [ ] 17.5 Create CommentMentionNotification
  - Implement notification for comment mentions
  - Add view action
  - _Requirements: 16.3_

- [ ] 18. Create scheduled commands for background processing
- [ ] 18.1 Create ProcessTaskRemindersCommand
  - Implement command to process pending reminders
  - Schedule to run every minute
  - _Requirements: 3.3_

- [ ] 18.2 Create GenerateRecurringTasksCommand
  - Implement command to generate recurring task instances
  - Schedule to run daily
  - _Requirements: 6.2_

- [ ] 18.3 Create CleanupOrphanedFilesCommand
  - Implement command to clean up orphaned file attachments
  - Schedule to run weekly
  - _Requirements: 8.4_

- [ ] 19. Add database indexes for performance
- [ ] 19.1 Create migration for task indexes
  - Add indexes on team_id, deleted_at, parent_id
  - Add indexes on pivot tables
  - _Requirements: All (performance)_

- [ ] 19.2 Create migration for note indexes
  - Add indexes on team_id, visibility, deleted_at
  - Add indexes on polymorphic relationships
  - _Requirements: All (performance)_

- [ ] 19.3 Create migration for activity indexes
  - Add indexes on subject_type, subject_id, created_at
  - Add indexes on team_id, event
  - _Requirements: All (performance)_

- [ ] 20. Create API endpoints (if required)
- [ ] 20.1 Create TaskController for API
  - Implement RESTful endpoints
  - Add authentication and authorization
  - _Requirements: 1.1, 1.2, 1.3, 1.4, 1.5_

- [ ] 20.2 Create NoteController for API
  - Implement RESTful endpoints
  - Add authentication and authorization
  - _Requirements: 7.1, 7.2, 7.3, 7.4, 7.5_

- [ ] 20.3 Create ActivityController for API
  - Implement read-only endpoints
  - Add filtering and search
  - _Requirements: 12.2, 13.1, 13.2, 13.3, 13.4, 13.5_

- [ ] 21. Add translations for all UI text
- [ ] 21.1 Add task-related translations
  - Add labels, actions, messages to lang files
  - Support English and Ukrainian
  - _Requirements: All (i18n)_

- [ ] 21.2 Add note-related translations
  - Add labels, actions, messages to lang files
  - Support English and Ukrainian
  - _Requirements: All (i18n)_

- [ ] 21.3 Add activity-related translations
  - Add labels, actions, messages to lang files
  - Support English and Ukrainian
  - _Requirements: All (i18n)_

- [ ] 22. Implement Timeline/Gantt Chart View (Requirement 26)
- [ ] 22.1 Create TimelineService
  - Implement timeline data generation methods
  - Add task position and width calculation
  - Add dependency connector coordinate generation
  - _Requirements: 26.1, 26.2, 26.4_

- [ ] 22.2 Write unit tests for TimelineService
  - Test timeline data generation
  - Test position calculations
  - Test dependency connectors
  - _Requirements: 26.1, 26.2, 26.4_

- [ ] 22.3 Write property test for timeline data generation
  - **Property 33: Timeline data generation**
  - **Validates: Requirements 26.1, 26.2, 26.4**

- [ ] 22.4 Create TimelinePage in Filament
  - Implement timeline visualization component
  - Add drag-and-drop date updates
  - Add zoom controls (day/week/month/quarter)
  - _Requirements: 26.1, 26.3, 26.4_

- [ ] 22.5 Write property test for timeline date updates
  - **Property 34: Timeline date updates**
  - **Validates: Requirements 26.3**

- [ ] 22.6 Add timeline filtering integration
  - Connect timeline view to existing filters
  - Update timeline on filter changes
  - _Requirements: 26.5_

- [ ] 23. Implement Advanced Filtering System (Requirement 27)
- [ ] 23.1 Create SavedFilter model and migration
  - Define model with relationships
  - Create database migration
  - _Requirements: 27.2, 27.3_

- [ ] 23.2 Create FilterService
  - Implement saved filter CRUD methods
  - Add filter combination logic
  - Add filter sharing functionality
  - _Requirements: 27.1, 27.2, 27.3, 27.4_

- [ ] 23.3 Write unit tests for FilterService
  - Test filter creation and application
  - Test criteria combination
  - Test filter sharing
  - _Requirements: 27.1, 27.2, 27.3, 27.4_

- [ ] 23.4 Write property test for saved filter management
  - **Property 35: Saved filter management**
  - **Validates: Requirements 27.1, 27.2, 27.3, 27.4**

- [ ] 23.5 Write property test for filter result counting
  - **Property 36: Filter result counting**
  - **Validates: Requirements 27.5**

- [ ] 23.6 Enhance TaskResource with saved filters
  - Add saved filter UI components
  - Add filter save/load actions
  - Add filter sharing actions
  - _Requirements: 27.2, 27.3, 27.4_

- [ ] 23.7 Add filter result count display
  - Show matching task count
  - Display active filter criteria
  - _Requirements: 27.5_

- [ ] 24. Implement Employee Workload Management (Requirement 28)
- [ ] 24.1 Create EmployeeCapacity model and migration
  - Define model with relationships
  - Create database migration
  - _Requirements: 28.4_

- [ ] 24.2 Create WorkloadService
  - Implement workload calculation methods
  - Add capacity threshold detection
  - Add availability calculation
  - _Requirements: 28.1, 28.2, 28.3, 28.4, 28.5_

- [ ] 24.3 Write unit tests for WorkloadService
  - Test workload calculations
  - Test capacity threshold detection
  - Test availability calculations
  - _Requirements: 28.1, 28.2, 28.3, 28.4_

- [ ] 24.4 Write property test for workload calculation
  - **Property 37: Workload calculation**
  - **Validates: Requirements 28.1, 28.2, 28.5**

- [ ] 24.5 Write property test for capacity threshold detection
  - **Property 38: Capacity threshold detection**
  - **Validates: Requirements 28.3**

- [ ] 24.6 Create CalculateWorkloadMetricsJob
  - Implement periodic workload calculation
  - Add caching for performance
  - _Requirements: 28.1, 28.2_

- [ ] 24.7 Create WorkloadDashboardWidget
  - Display employee workload visualization
  - Add overload alerts
  - Add time period selection
  - _Requirements: 28.1, 28.2, 28.3_

- [ ] 24.8 Add workload-based task reassignment suggestions
  - Integrate with task assignment UI
  - Show employee availability
  - _Requirements: 28.5_

- [ ] 25. Enhance Project Integration (Requirement 29)
- [ ] 25.1 Add project timeline update logic to Task model
  - Implement automatic project date recalculation
  - Add project milestone tracking
  - _Requirements: 29.1, 29.2, 29.5_

- [ ] 25.2 Write property test for project timeline updates
  - **Property 39: Project timeline updates**
  - **Validates: Requirements 29.1, 29.2**

- [ ] 25.3 Enhance ProjectResource with task management
  - Add project task list with filtering
  - Add project milestone display
  - _Requirements: 29.3, 29.4, 29.5_

- [ ] 25.4 Create TaskObserver hook for project updates
  - Trigger project recalculation on task date changes
  - Update milestone completion status
  - _Requirements: 29.2, 29.5_

- [ ] 26. Implement Workflow Automation (Requirement 30)
- [ ] 26.1 Create WorkflowDefinition and WorkflowExecution models
  - Define models with relationships
  - Create database migrations
  - _Requirements: 30.1, 30.2_

- [ ] 26.2 Create WorkflowAutomationService
  - Implement trigger evaluation logic
  - Add workflow execution methods
  - Add task creation from workflow
  - _Requirements: 30.1, 30.2, 30.3, 30.4_

- [ ] 26.3 Write unit tests for WorkflowAutomationService
  - Test trigger evaluation
  - Test workflow execution
  - Test task creation
  - _Requirements: 30.1, 30.2, 30.3_

- [ ] 26.4 Write property test for workflow trigger evaluation
  - **Property 40: Workflow trigger evaluation**
  - **Validates: Requirements 30.1, 30.2, 30.3**

- [ ] 26.5 Write property test for workflow task attribution
  - **Property 41: Workflow task attribution**
  - **Validates: Requirements 30.5**

- [ ] 26.6 Create EvaluateWorkflowTriggersJob
  - Implement async workflow evaluation
  - Add error handling and logging
  - _Requirements: 30.1, 30.4_

- [ ] 26.7 Create WorkflowResource in Filament
  - Implement workflow CRUD
  - Add trigger condition builder
  - Add action definition UI
  - _Requirements: 30.1, 30.2, 30.3_

- [ ] 26.8 Add workflow execution tracking
  - Display workflow execution history
  - Show created tasks
  - _Requirements: 30.4, 30.5_

- [ ] 27. Implement Bulk Operations (Requirement 31)
- [ ] 27.1 Add bulk action support to TaskResource
  - Implement bulk status update
  - Implement bulk assignee update
  - Implement bulk date update
  - Implement bulk delete
  - _Requirements: 31.1, 31.2, 31.3, 31.4, 31.5_

- [ ] 27.2 Write property test for bulk task updates
  - **Property 42: Bulk task updates**
  - **Validates: Requirements 31.1, 31.2, 31.3, 31.4**

- [ ] 27.3 Add bulk operation notifications
  - Send notifications for bulk assignments
  - Send confirmation for bulk deletes
  - _Requirements: 31.2, 31.5_

- [ ] 27.4 Add bulk operation progress tracking
  - Show progress bar for large operations
  - Add cancellation support
  - _Requirements: 31.1_

- [ ] 28. Implement Task Export (Requirement 32)
- [ ] 28.1 Create TaskExportService
  - Implement CSV export
  - Implement Excel export
  - Implement iCalendar export
  - _Requirements: 32.1, 32.2, 32.3_

- [ ] 28.2 Write unit tests for TaskExportService
  - Test CSV generation
  - Test Excel generation
  - Test iCalendar generation
  - _Requirements: 32.1, 32.2, 32.3_

- [ ] 28.3 Write property test for task export with filtering
  - **Property 43: Task export with filtering**
  - **Validates: Requirements 32.1, 32.2, 32.3, 32.4**

- [ ] 28.4 Write property test for subtask hierarchy in exports
  - **Property 44: Subtask hierarchy in exports**
  - **Validates: Requirements 32.5**

- [ ] 28.5 Add export actions to TaskResource
  - Add export button with format selection
  - Apply current filters to export
  - _Requirements: 32.4_

- [ ] 28.6 Add subtask hierarchy support to exports
  - Include parent-child relationships
  - Format hierarchy appropriately per format
  - _Requirements: 32.5_

- [ ] 29. Implement Smart Suggestions (Requirement 33)
- [ ] 29.1 Create TaskPattern model and migration
  - Define model with relationships
  - Create database migration
  - _Requirements: 33.1, 33.2, 33.3, 33.4, 33.5_

- [ ] 29.2 Create SmartSuggestionService
  - Implement pattern analysis methods
  - Add assignee suggestion logic
  - Add duration estimation logic
  - Add dependency suggestion logic
  - _Requirements: 33.1, 33.2, 33.3, 33.4, 33.5_

- [ ] 29.3 Write unit tests for SmartSuggestionService
  - Test pattern analysis
  - Test assignee suggestions
  - Test duration estimation
  - Test dependency suggestions
  - _Requirements: 33.2, 33.3, 33.5_

- [ ] 29.4 Write property test for pattern-based assignee suggestions
  - **Property 45: Pattern-based assignee suggestions**
  - **Validates: Requirements 33.2**

- [ ] 29.5 Write property test for duration estimation from history
  - **Property 46: Duration estimation from history**
  - **Validates: Requirements 33.3**

- [ ] 29.6 Write property test for dependency relationship suggestions
  - **Property 47: Dependency relationship suggestions**
  - **Validates: Requirements 33.5**

- [ ] 29.7 Create UpdateTaskPatternsJob
  - Implement pattern learning from completed tasks
  - Schedule to run daily
  - _Requirements: 33.1_

- [ ] 29.8 Create GenerateSmartSuggestionsJob
  - Implement suggestion generation
  - Cache suggestions for performance
  - _Requirements: 33.1, 33.2, 33.3, 33.4, 33.5_

- [ ] 29.9 Integrate suggestions into TaskResource
  - Show assignee suggestions in assignment UI
  - Show duration suggestions in date fields
  - Show dependency suggestions in dependency UI
  - _Requirements: 33.2, 33.3, 33.5_

- [ ] 29.10 Create SmartSuggestionsWidget
  - Display task suggestions on dashboard
  - Add suggestion acceptance actions
  - _Requirements: 33.1, 33.4_

- [ ] 30. Add database indexes for new features
- [ ] 30.1 Create migration for timeline indexes
  - Add indexes on start_date, end_date
  - Add indexes on project_id
  - _Requirements: 26.1, 26.4_

- [ ] 30.2 Create migration for workload indexes
  - Add indexes on task_user for workload queries
  - Add indexes on employee_capacity
  - _Requirements: 28.1, 28.2_

- [ ] 30.3 Create migration for workflow indexes
  - Add indexes on workflow_definitions
  - Add indexes on workflow_executions
  - _Requirements: 30.1, 30.4_

- [ ] 30.4 Create migration for pattern indexes
  - Add indexes on task_patterns
  - _Requirements: 33.1_

- [ ] 31. Final checkpoint - Ensure all tests pass
  - Ensure all tests pass, ask the user if questions arise.

- [ ] 32. Documentation and cleanup
- [ ] 32.1 Update API documentation
  - Document all endpoints
  - Add examples
  - _Requirements: All (documentation)_

- [ ] 32.2 Update user documentation
  - Document new features
  - Add screenshots
  - _Requirements: All (documentation)_

- [ ] 32.3 Code cleanup and refactoring
  - Remove unused code
  - Improve code organization
  - _Requirements: All (code quality)_
