# Implementation Plan: Time Management & HR Time Tracking

- [ ] 1. Set up database schema and migrations
  - Create migrations for time_entries, absences, leave_balances, leave_types, and time_categories tables
  - Add indexes for performance optimization (employee_date, project, company, billable, approval_status, date_range)
  - Set up foreign key constraints with appropriate cascade rules
  - Add soft deletes support to relevant tables
  - _Requirements: 1.1, 1.2, 1.3, 1.4, 1.5, 5.1, 5.2, 5.3, 5.4, 5.5, 6.1, 6.2, 6.3, 6.4, 6.5_

- [ ] 2. Create core domain models
  - [ ] 2.1 Implement TimeEntry model with relationships and scopes
    - Define fillable fields, casts, and relationships (employee, project, task, company, category, approver, invoice)
    - Implement scopes: billable, unbilled, pending, approved, forDateRange
    - Add business logic methods: calculateDuration, calculateBillingAmount, canBeEdited, canBeDeleted, requiresApproval
    - Apply HasTeam, HasCreator, SoftDeletes traits
    - _Requirements: 1.1, 1.2, 1.3, 1.4, 1.5, 2.1, 2.2, 2.3, 2.4, 2.5_

  - [ ] 2.2 Write property test for TimeEntry duration consistency
    - **Property 1: Time Entry Duration Consistency**
    - **Validates: Requirements 1.1**

  - [ ] 2.3 Write property test for TimeEntry data completeness
    - **Property 3: Time Entry Data Completeness**
    - **Validates: Requirements 1.1**

  - [ ] 2.4 Write property test for TimeEntry association validity
    - **Property 4: Time Entry Association Validity**
    - **Validates: Requirements 1.2**

  - [ ] 2.5 Write property test for billable flag and rate storage
    - **Property 5: Billable Flag and Rate Storage**
    - **Validates: Requirements 1.3**

  - [ ] 2.6 Write property test for duration valid range
    - **Property 6: Duration Within Valid Range**
    - **Validates: Requirements 2.1**

  - [ ] 2.7 Write property test for billable time requires project or client
    - **Property 7: Billable Time Requires Project or Client**
    - **Validates: Requirements 2.2**

  - [ ] 2.8 Implement Absence model with relationships and scopes
    - Define fillable fields, casts, and relationships (employee, leaveType, approver)
    - Implement scopes: pending, approved, rejected, forDateRange, overlapping
    - Add business logic methods: calculateDuration, overlapsWithExisting, canBeApproved, canBeCancelled
    - Apply HasTeam, HasCreator, SoftDeletes traits
    - _Requirements: 5.1, 5.2, 5.3, 5.4, 5.5_

  - [ ] 2.9 Write property test for absence data completeness
    - **Property 17: Absence Data Completeness**
    - **Validates: Requirements 5.1**

  - [ ] 2.10 Write property test for absence duration calculation
    - **Property 20: Absence Duration Calculation**
    - **Validates: Requirements 5.5**

  - [ ] 2.11 Implement LeaveBalance model with business logic
    - Define fillable fields, casts, and relationships (employee, leaveType)
    - Add business logic methods: recalculate, deduct, restore, hasAvailableBalance
    - Apply HasTeam trait
    - _Requirements: 6.1, 6.2, 6.3, 6.4, 6.5_

  - [ ] 2.12 Write property test for leave balance consistency
    - **Property 22: Leave Balance Consistency**
    - **Validates: Requirements 6.3**

  - [ ] 2.13 Implement LeaveType and TimeCategory models
    - Create LeaveType model with fillable fields, casts, and relationships
    - Create TimeCategory model with fillable fields, casts, and relationships
    - Apply HasTeam and SoftDeletes traits to both
    - _Requirements: 5.2, 1.2_

  - [ ] 2.14 Write property test for leave type support
    - **Property 18: Leave Type Support**
    - **Validates: Requirements 5.2**

- [ ] 3. Implement validation service
  - [ ] 3.1 Create ValidationService class
    - Implement validateTimeEntry method with all business rules
    - Implement validateAbsence method with date range and overlap checks
    - Implement checkTimeOverlap method for detecting conflicting time entries
    - Implement checkAbsenceOverlap method for detecting conflicting absences
    - Implement validateDuration, validateDateRange, checkLeaveBalance methods
    - _Requirements: 1.4, 2.1, 2.2, 2.3, 2.4, 2.5, 5.5, 6.4_

  - [ ] 3.2 Write property test for no overlapping time entries
    - **Property 2: No Overlapping Time Entries**
    - **Validates: Requirements 1.4**

  - [ ] 3.3 Write property test for employee project access validation
    - **Property 8: Employee Project Access Validation**
    - **Validates: Requirements 2.4**

  - [ ] 3.4 Write property test for daily hours aggregation
    - **Property 9: Daily Hours Aggregation**
    - **Validates: Requirements 2.5**

  - [ ] 3.5 Write property test for insufficient balance validation
    - **Property 23: Insufficient Balance Validation**
    - **Validates: Requirements 6.4**

- [ ] 4. Implement billing calculator service
  - [ ] 4.1 Create BillingCalculator class
    - Implement calculateBillingAmount method with rate hierarchy logic
    - Implement getBillingRate method (custom > project > employee default)
    - Implement aggregateBillableHours method for collections
    - Implement calculateProjectBilling and calculateClientBilling methods
    - _Requirements: 4.1, 4.2, 4.3, 4.4, 4.5_

  - [ ] 4.2 Write property test for billing rate determination
    - **Property 12: Billing Rate Determination**
    - **Validates: Requirements 4.1**

  - [ ] 4.3 Write property test for billing amount calculation
    - **Property 13: Billing Amount Calculation**
    - **Validates: Requirements 4.2**

  - [ ] 4.4 Write property test for billable status categorization
    - **Property 14: Billable Status Categorization**
    - **Validates: Requirements 4.3**

  - [ ] 4.5 Write property test for invoice linking
    - **Property 15: Invoice Linking**
    - **Validates: Requirements 4.4**

  - [ ] 4.6 Write property test for unbilled hours grouping
    - **Property 16: Unbilled Hours Grouping**
    - **Validates: Requirements 4.5**

- [ ] 5. Implement time entry service
  - [ ] 5.1 Create TimeEntryService class
    - Implement createTimeEntry method with validation and billing calculation
    - Implement updateTimeEntry method with overlap checking
    - Implement deleteTimeEntry method with authorization checks
    - Implement submitForApproval method with workflow routing
    - Implement bulkCreate method for batch operations
    - Implement validateOverlap and calculateTotalHours helper methods
    - Inject ValidationService, BillingCalculator, and NotificationService dependencies
    - _Requirements: 1.1, 1.2, 1.3, 1.4, 1.5, 2.1, 2.2, 2.3, 2.4, 2.5, 8.1_

- [ ] 6. Implement absence service
  - [ ] 6.1 Create AbsenceService class
    - Implement createAbsence method with overlap checking and balance validation
    - Implement updateAbsence method with recalculation of duration
    - Implement cancelAbsence method with balance restoration
    - Implement approveAbsence method with balance deduction
    - Implement rejectAbsence method with notification
    - Implement checkOverlap and calculateDuration helper methods
    - Inject LeaveBalanceService, ValidationService, and NotificationService dependencies
    - _Requirements: 5.1, 5.2, 5.3, 5.4, 5.5_

  - [ ] 6.2 Write property test for leave balance deduction on approval
    - **Property 19: Leave Balance Deduction on Approval**
    - **Validates: Requirements 5.4**

  - [ ] 6.3 Write property test for approval status transitions
    - **Property 24: Approval Status Transitions**
    - **Validates: Requirements 8.1, 8.2, 8.3**

  - [ ] 6.4 Write property test for rejection state and resubmission
    - **Property 25: Rejection State and Resubmission**
    - **Validates: Requirements 8.3**

- [ ] 7. Implement leave balance service
  - [ ] 7.1 Create LeaveBalanceService class
    - Implement getBalance method with lazy initialization
    - Implement initializeBalances method for new employees
    - Implement accrueLeave method with accrual rate calculation
    - Implement deductLeave and restoreLeave methods
    - Implement carryOverBalances method for year-end processing
    - Implement adjustBalance method with audit logging
    - _Requirements: 6.1, 6.2, 6.3, 6.4, 6.5_

  - [ ] 7.2 Write property test for leave accrual calculation
    - **Property 21: Leave Accrual Calculation**
    - **Validates: Requirements 6.2**

- [ ] 8. Checkpoint - Ensure all tests pass
  - Ensure all tests pass, ask the user if questions arise.

- [ ] 9. Implement report generator service
  - [ ] 9.1 Create ReportGenerator class
    - Implement generateTimeReport method with filtering and grouping
    - Implement generateBillableHoursReport method with revenue calculations
    - Implement generateAbsenceReport method with leave type breakdown
    - Implement generateEmployeeUtilizationReport method
    - Implement exportToCsv, exportToExcel, exportToPdf methods
    - _Requirements: 3.1, 3.2, 3.3, 3.4, 3.5, 12.1, 12.2, 12.3, 12.4, 12.5_

  - [ ] 9.2 Write property test for time report aggregation accuracy
    - **Property 10: Time Report Aggregation Accuracy**
    - **Validates: Requirements 3.2**

  - [ ] 9.3 Write property test for revenue calculation in reports
    - **Property 11: Revenue Calculation in Reports**
    - **Validates: Requirements 3.5**

  - [ ] 9.4 Write property test for export data completeness
    - **Property 31: Export Data Completeness**
    - **Validates: Requirements 12.2**

  - [ ] 9.5 Write property test for export filter consistency
    - **Property 32: Export Filter Consistency**
    - **Validates: Requirements 12.3**

- [ ] 10. Create Filament resources for time entries
  - [ ] 10.1 Create TimeEntryResource with list, create, edit pages
    - Define table columns: date, employee, project, client, duration, billable status, approval status
    - Add filters: date range, employee, project, client, billable, approval status
    - Implement actions: edit, delete, submit for approval, approve, reject
    - Add bulk actions: bulk approve, bulk delete, export
    - Configure authorization policies
    - _Requirements: 1.1, 1.2, 1.3, 1.4, 1.5, 8.1, 8.2, 8.3, 8.4, 8.5_

  - [ ] 10.2 Create TimeEntryForm schema
    - Add fields: date, start_time, end_time, duration_minutes, description
    - Add relationship selects: employee, project, task, company, time_category
    - Add billable toggle with conditional billing_rate field
    - Implement live validation for overlaps and duration
    - Add notes field
    - _Requirements: 1.1, 1.2, 1.3, 2.1, 2.2_

  - [ ] 10.3 Write property test for locked approved entries
    - **Property 26: Locked Approved Entries**
    - **Validates: Requirements 8.4**

- [ ] 11. Create Filament resources for absences
  - [ ] 11.1 Create AbsenceResource with list, create, edit pages
    - Define table columns: employee, leave_type, start_date, end_date, duration, status
    - Add filters: date range, employee, leave_type, status
    - Implement actions: edit, cancel, approve, reject
    - Add bulk actions: bulk approve, bulk reject
    - Configure authorization policies
    - _Requirements: 5.1, 5.2, 5.3, 5.4, 5.5_

  - [ ] 11.2 Create AbsenceForm schema
    - Add fields: employee, leave_type, start_date, end_date, reason, notes
    - Implement live duration calculation excluding weekends/holidays
    - Add leave balance display with warning for insufficient balance
    - Implement overlap validation
    - _Requirements: 5.1, 5.2, 5.5, 6.4_

- [ ] 12. Create Filament resources for leave management
  - [ ] 12.1 Create LeaveTypeResource with CRUD operations
    - Define table columns: name, code, is_paid, requires_approval, max_days_per_year, is_active
    - Add form fields: name, code, description, color, icon, accrual settings, carryover settings
    - Implement soft deletes with restore action
    - Configure team scoping
    - _Requirements: 5.2, 6.1, 6.2_

  - [ ] 12.2 Create LeaveBalanceResource with view and adjustment capabilities
    - Define table columns: employee, leave_type, year, allocated, used, pending, available
    - Add filters: employee, leave_type, year
    - Implement manual adjustment action with audit logging
    - Add balance initialization action for new employees
    - _Requirements: 6.1, 6.3, 6.5_

  - [ ] 12.3 Create TimeCategoryResource with CRUD operations
    - Define table columns: name, code, is_billable_default, default_billing_rate, is_active
    - Add form fields: name, code, description, color, icon, billing settings
    - Implement soft deletes with restore action
    - Configure team scoping
    - _Requirements: 1.2_

- [ ] 13. Implement availability calendar widget
  - [ ] 13.1 Create AvailabilityCalendarWidget
    - Implement monthly/weekly grid view with employee rows
    - Display absence indicators with color coding by leave type
    - Add filters: team, department, project, individual employees
    - Implement tooltips showing absence details on hover
    - Highlight weekends, holidays, and company closures
    - _Requirements: 7.1, 7.2, 7.3, 7.4, 7.5_

- [ ] 14. Implement time tracking dashboard widgets
  - [ ] 14.1 Create TimeTrackingStatsWidget
    - Display total hours logged (today, this week, this month)
    - Show billable vs non-billable hours breakdown
    - Display pending approval count
    - Add quick action buttons: log time, view reports
    - _Requirements: 3.2, 4.3_

  - [ ] 14.2 Create LeaveBalanceWidget
    - Display employee's leave balances for all leave types
    - Show available, used, and pending days
    - Add visual progress bars
    - Implement quick action: request absence
    - _Requirements: 6.3_

- [ ] 15. Implement approval workflow
  - [ ] 15.1 Create approval notification system
    - Implement notification for pending time entry approvals
    - Implement notification for pending absence approvals
    - Implement notification for rejection with reason
    - Add daily digest for managers with pending approvals
    - _Requirements: 8.1, 8.2, 8.3, 11.2, 11.5_

  - [ ] 15.2 Create approval actions and bulk operations
    - Implement approve action with locking mechanism
    - Implement reject action with reason input
    - Implement bulk approve action
    - Implement bulk reject action
    - Add approval history tracking
    - _Requirements: 8.1, 8.2, 8.3, 8.4_

- [ ] 16. Implement project and task integration
  - [ ] 16.1 Add time tracking to project views
    - Display total logged hours on project detail page
    - Show estimated vs actual hours with variance
    - Add time entries relation manager to projects
    - Implement project time report action
    - _Requirements: 10.2, 10.4_

  - [ ] 16.2 Implement task time rollup
    - Create observer to update task actual_hours on time entry changes
    - Implement remaining_hours calculation
    - Add validation to prevent time logging on completed tasks (configurable)
    - Add time entries relation manager to tasks
    - _Requirements: 10.1, 10.3_

  - [ ] 16.3 Write property test for task time rollup
    - **Property 27: Task Time Rollup**
    - **Validates: Requirements 10.1**

  - [ ] 16.4 Write property test for project time aggregation
    - **Property 28: Project Time Aggregation**
    - **Validates: Requirements 10.2**

  - [ ] 16.5 Write property test for completed task validation
    - **Property 29: Completed Task Validation**
    - **Validates: Requirements 10.3**

  - [ ] 16.6 Write property test for task-project referential integrity
    - **Property 30: Task-Project Referential Integrity**
    - **Validates: Requirements 10.5**

- [ ] 17. Implement notification and reminder system
  - [ ] 17.1 Create time entry reminder job
    - Implement scheduled job to check for missing time entries
    - Send reminders to employees who haven't logged time for current week
    - Make reminder schedule configurable per team
    - _Requirements: 11.1_

  - [ ] 17.2 Create leave balance notification job
    - Implement job to check leave balances against thresholds
    - Send notifications when balance falls below configured threshold
    - Make threshold configurable per leave type
    - _Requirements: 11.4_

  - [ ] 17.3 Implement real-time notifications
    - Send notification when absence request is submitted
    - Send notification when time entry is rejected
    - Send notification when absence is approved/rejected
    - _Requirements: 11.2, 11.3_

- [ ] 18. Implement mobile API endpoints
  - [ ] 18.1 Create mobile time entry API
    - Implement POST /api/mobile/time-entries endpoint
    - Implement GET /api/mobile/time-entries endpoint with pagination
    - Implement PUT /api/mobile/time-entries/{id} endpoint
    - Implement DELETE /api/mobile/time-entries/{id} endpoint
    - Add offline sync support with conflict resolution
    - _Requirements: 9.1, 9.2, 9.4_

  - [ ] 18.2 Create mobile timer API
    - Implement POST /api/mobile/timer/start endpoint
    - Implement POST /api/mobile/timer/stop endpoint
    - Implement GET /api/mobile/timer/current endpoint
    - Store timer state in cache for quick access
    - _Requirements: 9.3_

  - [ ] 18.3 Create mobile absence API
    - Implement POST /api/mobile/absences endpoint
    - Implement GET /api/mobile/absences endpoint
    - Implement GET /api/mobile/leave-balances endpoint
    - _Requirements: 9.1, 9.2_

- [ ] 19. Implement report generation and export
  - [ ] 19.1 Create time report page
    - Implement filters: date range, employee, project, client, billable status, category
    - Add grouping options: employee, project, client, date, category
    - Display summary totals: total hours, billable hours, non-billable hours, revenue
    - Implement export actions: CSV, Excel, PDF
    - _Requirements: 3.1, 3.2, 3.3, 3.4, 3.5, 12.1, 12.2, 12.3_

  - [ ] 19.2 Create billable hours report page
    - Implement filters: date range, client, project, billed status
    - Group unbilled hours by client with calculated amounts
    - Display billed vs unbilled breakdown
    - Add action to create invoice from unbilled hours
    - Implement export actions
    - _Requirements: 4.3, 4.4, 4.5, 12.1, 12.2, 12.3_

  - [ ] 19.3 Create absence report page
    - Implement filters: date range, employee, leave type, status
    - Display absence summary by employee and leave type
    - Show leave balance utilization percentages
    - Implement export actions
    - _Requirements: 7.1, 12.1, 12.2, 12.3_

  - [ ] 19.4 Create employee utilization report page
    - Calculate utilization percentage (logged hours / available hours)
    - Display billable vs non-billable time breakdown
    - Show project time distribution
    - Implement export actions
    - _Requirements: 3.2, 12.1, 12.2, 12.3_

  - [ ] 19.5 Implement scheduled report exports
    - Create job for automated report generation
    - Implement email delivery of reports
    - Add API endpoint for external system integration
    - Make schedule configurable per report type
    - _Requirements: 12.5_

- [ ] 20. Implement leave accrual automation
  - [ ] 20.1 Create leave accrual job
    - Implement scheduled job to process accruals based on frequency (monthly, quarterly, annually)
    - Calculate accrual amounts based on accrual_rate and employee tenure
    - Update leave balances for all eligible employees
    - Log accrual transactions for audit trail
    - _Requirements: 6.2_

  - [ ] 20.2 Create year-end carryover job
    - Implement job to process leave balance carryovers
    - Apply max_carryover_days limits per leave type
    - Handle expiration dates for carried over balances
    - Generate carryover report for HR review
    - _Requirements: 6.2_

- [ ] 21. Add translations for all UI text
  - Add translation keys to lang/en/app.php for all labels, actions, navigation
  - Add translation keys to lang/en/enums.php for approval statuses
  - Ensure all Filament resources use __() for labels and descriptions
  - Add translations for validation messages and notifications
  - _Requirements: All_

- [ ] 22. Checkpoint - Ensure all tests pass
  - Ensure all tests pass, ask the user if questions arise.

- [ ] 23. Create seeders for development data
  - [ ] 23.1 Create LeaveTypeSeeder
    - Seed common leave types: vacation, sick leave, personal time, unpaid leave
    - Set appropriate accrual rates and carryover rules
    - _Requirements: 5.2_

  - [ ] 23.2 Create TimeCategorySeeder
    - Seed common time categories: meetings, training, administrative, development
    - Set default billing rates and billable flags
    - _Requirements: 1.2_

  - [ ] 23.3 Create sample time entries and absences
    - Generate realistic time entries for test employees
    - Create sample absence requests in various states
    - Initialize leave balances for test employees
    - _Requirements: All_

- [ ] 24. Write documentation
  - [ ] 24.1 Create user guide for time tracking
    - Document how to log time entries
    - Explain billable vs non-billable time
    - Describe approval workflow
    - _Requirements: 1.1, 1.2, 1.3, 8.1, 8.2, 8.3, 8.4_

  - [ ] 24.2 Create user guide for absence management
    - Document how to request absences
    - Explain leave types and balances
    - Describe approval process
    - _Requirements: 5.1, 5.2, 5.3, 5.4, 6.3_

  - [ ] 24.3 Create administrator guide
    - Document leave type configuration
    - Explain accrual rules and carryover settings
    - Describe report generation and exports
    - Document API endpoints for integrations
    - _Requirements: 6.1, 6.2, 12.1, 12.2, 12.3, 12.4, 12.5_

- [ ] 25. Final checkpoint - Ensure all tests pass
  - Ensure all tests pass, ask the user if questions arise.
