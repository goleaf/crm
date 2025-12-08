# Design: Time Management & HR Time Tracking

## Overview

The Time Management & HR Time Tracking system provides a comprehensive solution for recording work hours, managing employee absences, tracking billable time, and generating reports. The system integrates with existing project management, employee records, and invoicing modules while maintaining its own domain models for time entries and absence records.

The design follows a layered architecture with clear separation between time tracking, absence management, and reporting concerns. It leverages Laravel's Eloquent ORM for data persistence, Filament v4.3+ for the administrative interface, and provides API endpoints for mobile and third-party integrations.

## Architecture

### System Components

```
┌─────────────────────────────────────────────────────────────┐
│                     Presentation Layer                       │
│  ┌──────────────┐  ┌──────────────┐  ┌──────────────┐      │
│  │   Filament   │  │  Mobile API  │  │  Export API  │      │
│  │   Resources  │  │   Endpoints  │  │   Endpoints  │      │
│  └──────────────┘  └──────────────┘  └──────────────┘      │
└─────────────────────────────────────────────────────────────┘
                            │
┌─────────────────────────────────────────────────────────────┐
│                     Application Layer                        │
│  ┌──────────────┐  ┌──────────────┐  ┌──────────────┐      │
│  │ TimeEntry    │  │   Absence    │  │   Report     │      │
│  │  Service     │  │   Service    │  │  Generator   │      │
│  └──────────────┘  └──────────────┘  └──────────────┘      │
│  ┌──────────────┐  ┌──────────────┐  ┌──────────────┐      │
│  │  Validation  │  │   Approval   │  │   Billing    │      │
│  │   Service    │  │   Workflow   │  │  Calculator  │      │
│  └──────────────┘  └──────────────┘  └──────────────┘      │
└─────────────────────────────────────────────────────────────┘
                            │
┌─────────────────────────────────────────────────────────────┐
│                      Domain Layer                            │
│  ┌──────────────┐  ┌──────────────┐  ┌──────────────┐      │
│  │  TimeEntry   │  │   Absence    │  │ LeaveBalance │      │
│  │    Model     │  │    Model     │  │    Model     │      │
│  └──────────────┘  └──────────────┘  └──────────────┘      │
│  ┌──────────────┐  ┌──────────────┐  ┌──────────────┐      │
│  │ TimeCategory │  │  LeaveType   │  │ TimeApproval │      │
│  │    Model     │  │    Model     │  │    Model     │      │
│  └──────────────┘  └──────────────┘  └──────────────┘      │
└─────────────────────────────────────────────────────────────┘
                            │
┌─────────────────────────────────────────────────────────────┐
│                   Infrastructure Layer                       │
│  ┌──────────────┐  ┌──────────────┐  ┌──────────────┐      │
│  │   Database   │  │    Cache     │  │    Queue     │      │
│  │  (Eloquent)  │  │    (Redis)   │  │   (Jobs)     │      │
│  └──────────────┘  └──────────────┘  └──────────────┘      │
└─────────────────────────────────────────────────────────────┘
```

### Integration Points

1. **Employee Management**: Links to existing `Employee` model for user information and organizational hierarchy
2. **Project Management**: Integrates with `Project` and `Task` models for project-based time tracking
3. **Client Management**: Associates time entries with `Company` records for client billing
4. **Invoicing**: Provides billable hours data to invoice generation system
5. **Calendar**: Shares absence data with calendar views and scheduling systems
6. **Notifications**: Uses Laravel notification system for reminders and approvals

## Components and Interfaces

### Core Models

#### TimeEntry Model

```php
class TimeEntry extends Model
{
    use HasTeam, HasCreator, SoftDeletes;
    
    protected $fillable = [
        'team_id',
        'employee_id',
        'date',
        'start_time',
        'end_time',
        'duration_minutes',
        'description',
        'is_billable',
        'billing_rate',
        'billing_amount',
        'project_id',
        'task_id',
        'company_id',
        'time_category_id',
        'approval_status',
        'approved_by',
        'approved_at',
        'invoice_id',
        'notes',
    ];
    
    protected $casts = [
        'date' => 'date',
        'start_time' => 'datetime',
        'end_time' => 'datetime',
        'duration_minutes' => 'integer',
        'is_billable' => 'boolean',
        'billing_rate' => 'decimal:2',
        'billing_amount' => 'decimal:2',
        'approved_at' => 'datetime',
    ];
    
    // Relationships
    public function employee(): BelongsTo;
    public function project(): BelongsTo;
    public function task(): BelongsTo;
    public function company(): BelongsTo;
    public function category(): BelongsTo;
    public function approver(): BelongsTo;
    public function invoice(): BelongsTo;
    
    // Scopes
    public function scopeBillable(Builder $query): void;
    public function scopeUnbilled(Builder $query): void;
    public function scopePending(Builder $query): void;
    public function scopeApproved(Builder $query): void;
    public function scopeForDateRange(Builder $query, Carbon $start, Carbon $end): void;
    
    // Business Logic
    public function calculateDuration(): int;
    public function calculateBillingAmount(): float;
    public function canBeEdited(): bool;
    public function canBeDeleted(): bool;
    public function requiresApproval(): bool;
}
```

#### Absence Model

```php
class Absence extends Model
{
    use HasTeam, HasCreator, SoftDeletes;
    
    protected $fillable = [
        'team_id',
        'employee_id',
        'leave_type_id',
        'start_date',
        'end_date',
        'duration_days',
        'duration_hours',
        'status',
        'reason',
        'notes',
        'approved_by',
        'approved_at',
        'rejected_reason',
    ];
    
    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'duration_days' => 'decimal:2',
        'duration_hours' => 'decimal:2',
        'approved_at' => 'datetime',
    ];
    
    // Relationships
    public function employee(): BelongsTo;
    public function leaveType(): BelongsTo;
    public function approver(): BelongsTo;
    
    // Scopes
    public function scopePending(Builder $query): void;
    public function scopeApproved(Builder $query): void;
    public function scopeRejected(Builder $query): void;
    public function scopeForDateRange(Builder $query, Carbon $start, Carbon $end): void;
    public function scopeOverlapping(Builder $query, Carbon $start, Carbon $end): void;
    
    // Business Logic
    public function calculateDuration(): array;
    public function overlapsWithExisting(): bool;
    public function canBeApproved(): bool;
    public function canBeCancelled(): bool;
}
```

#### LeaveBalance Model

```php
class LeaveBalance extends Model
{
    use HasTeam;
    
    protected $fillable = [
        'team_id',
        'employee_id',
        'leave_type_id',
        'year',
        'allocated_days',
        'used_days',
        'pending_days',
        'available_days',
        'carried_over_days',
        'expires_at',
    ];
    
    protected $casts = [
        'year' => 'integer',
        'allocated_days' => 'decimal:2',
        'used_days' => 'decimal:2',
        'pending_days' => 'decimal:2',
        'available_days' => 'decimal:2',
        'carried_over_days' => 'decimal:2',
        'expires_at' => 'date',
    ];
    
    // Relationships
    public function employee(): BelongsTo;
    public function leaveType(): BelongsTo;
    
    // Business Logic
    public function recalculate(): void;
    public function deduct(float $days): void;
    public function restore(float $days): void;
    public function hasAvailableBalance(float $days): bool;
}
```

#### LeaveType Model

```php
class LeaveType extends Model
{
    use HasTeam, SoftDeletes;
    
    protected $fillable = [
        'team_id',
        'name',
        'code',
        'description',
        'color',
        'icon',
        'is_paid',
        'requires_approval',
        'max_days_per_year',
        'accrual_rate',
        'accrual_frequency',
        'allow_carryover',
        'max_carryover_days',
        'is_active',
        'sort_order',
    ];
    
    protected $casts = [
        'is_paid' => 'boolean',
        'requires_approval' => 'boolean',
        'max_days_per_year' => 'integer',
        'accrual_rate' => 'decimal:2',
        'allow_carryover' => 'boolean',
        'max_carryover_days' => 'integer',
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];
    
    // Relationships
    public function absences(): HasMany;
    public function balances(): HasMany;
}
```

#### TimeCategory Model

```php
class TimeCategory extends Model
{
    use HasTeam, SoftDeletes;
    
    protected $fillable = [
        'team_id',
        'name',
        'code',
        'description',
        'color',
        'icon',
        'is_billable_default',
        'default_billing_rate',
        'is_active',
        'sort_order',
    ];
    
    protected $casts = [
        'is_billable_default' => 'boolean',
        'default_billing_rate' => 'decimal:2',
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];
    
    // Relationships
    public function timeEntries(): HasMany;
}
```

### Service Classes

#### TimeEntryService

```php
class TimeEntryService
{
    public function __construct(
        private ValidationService $validator,
        private BillingCalculator $billingCalculator,
        private NotificationService $notifier
    ) {}
    
    public function createTimeEntry(array $data): TimeEntry;
    public function updateTimeEntry(TimeEntry $entry, array $data): TimeEntry;
    public function deleteTimeEntry(TimeEntry $entry): bool;
    public function submitForApproval(TimeEntry $entry): void;
    public function bulkCreate(array $entries): Collection;
    public function validateOverlap(Employee $employee, Carbon $date, ?Carbon $start, ?Carbon $end): bool;
    public function calculateTotalHours(Employee $employee, Carbon $date): float;
}
```

#### AbsenceService

```php
class AbsenceService
{
    public function __construct(
        private LeaveBalanceService $balanceService,
        private ValidationService $validator,
        private NotificationService $notifier
    ) {}
    
    public function createAbsence(array $data): Absence;
    public function updateAbsence(Absence $absence, array $data): Absence;
    public function cancelAbsence(Absence $absence, string $reason): void;
    public function approveAbsence(Absence $absence, User $approver): void;
    public function rejectAbsence(Absence $absence, User $approver, string $reason): void;
    public function checkOverlap(Employee $employee, Carbon $start, Carbon $end): Collection;
    public function calculateDuration(Carbon $start, Carbon $end, Employee $employee): array;
}
```

#### LeaveBalanceService

```php
class LeaveBalanceService
{
    public function getBalance(Employee $employee, LeaveType $leaveType, int $year): LeaveBalance;
    public function initializeBalances(Employee $employee, int $year): void;
    public function accrueLeave(Employee $employee, LeaveType $leaveType): void;
    public function deductLeave(Absence $absence): void;
    public function restoreLeave(Absence $absence): void;
    public function carryOverBalances(Employee $employee, int $fromYear, int $toYear): void;
    public function adjustBalance(LeaveBalance $balance, float $adjustment, string $reason): void;
}
```

#### ReportGenerator

```php
class ReportGenerator
{
    public function generateTimeReport(array $filters): Collection;
    public function generateBillableHoursReport(array $filters): Collection;
    public function generateAbsenceReport(array $filters): Collection;
    public function generateEmployeeUtilizationReport(array $filters): Collection;
    public function exportToCsv(Collection $data, array $columns): string;
    public function exportToExcel(Collection $data, array $columns): string;
    public function exportToPdf(Collection $data, array $columns): string;
}
```

#### BillingCalculator

```php
class BillingCalculator
{
    public function calculateBillingAmount(TimeEntry $entry): float;
    public function getBillingRate(TimeEntry $entry): float;
    public function aggregateBillableHours(Collection $entries): array;
    public function calculateProjectBilling(Project $project, ?Carbon $start = null, ?Carbon $end = null): array;
    public function calculateClientBilling(Company $client, ?Carbon $start = null, ?Carbon $end = null): array;
}
```

#### ValidationService

```php
class ValidationService
{
    public function validateTimeEntry(array $data): array;
    public function validateAbsence(array $data): array;
    public function checkTimeOverlap(Employee $employee, Carbon $date, ?Carbon $start, ?Carbon $end, ?int $excludeId = null): bool;
    public function checkAbsenceOverlap(Employee $employee, Carbon $start, Carbon $end, ?int $excludeId = null): bool;
    public function validateDuration(int $minutes): bool;
    public function validateDateRange(Carbon $start, Carbon $end): bool;
    public function checkLeaveBalance(Employee $employee, LeaveType $leaveType, float $days): bool;
}
```

## Data Models

### Database Schema

#### time_entries table

```sql
CREATE TABLE time_entries (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    team_id BIGINT UNSIGNED NOT NULL,
    employee_id BIGINT UNSIGNED NOT NULL,
    date DATE NOT NULL,
    start_time DATETIME NULL,
    end_time DATETIME NULL,
    duration_minutes INT NOT NULL,
    description TEXT NULL,
    is_billable BOOLEAN DEFAULT FALSE,
    billing_rate DECIMAL(10,2) NULL,
    billing_amount DECIMAL(10,2) NULL,
    project_id BIGINT UNSIGNED NULL,
    task_id BIGINT UNSIGNED NULL,
    company_id BIGINT UNSIGNED NULL,
    time_category_id BIGINT UNSIGNED NULL,
    approval_status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    approved_by BIGINT UNSIGNED NULL,
    approved_at DATETIME NULL,
    invoice_id BIGINT UNSIGNED NULL,
    notes TEXT NULL,
    creator_id BIGINT UNSIGNED NULL,
    editor_id BIGINT UNSIGNED NULL,
    deleted_by BIGINT UNSIGNED NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    deleted_at TIMESTAMP NULL,
    
    INDEX idx_employee_date (employee_id, date),
    INDEX idx_project (project_id),
    INDEX idx_company (company_id),
    INDEX idx_billable (is_billable),
    INDEX idx_approval_status (approval_status),
    INDEX idx_date_range (date),
    
    FOREIGN KEY (team_id) REFERENCES teams(id) ON DELETE CASCADE,
    FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE CASCADE,
    FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE SET NULL,
    FOREIGN KEY (task_id) REFERENCES tasks(id) ON DELETE SET NULL,
    FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE SET NULL,
    FOREIGN KEY (time_category_id) REFERENCES time_categories(id) ON DELETE SET NULL,
    FOREIGN KEY (approved_by) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (invoice_id) REFERENCES invoices(id) ON DELETE SET NULL
);
```

#### absences table

```sql
CREATE TABLE absences (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    team_id BIGINT UNSIGNED NOT NULL,
    employee_id BIGINT UNSIGNED NOT NULL,
    leave_type_id BIGINT UNSIGNED NOT NULL,
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    duration_days DECIMAL(5,2) NOT NULL,
    duration_hours DECIMAL(6,2) NOT NULL,
    status ENUM('pending', 'approved', 'rejected', 'cancelled') DEFAULT 'pending',
    reason TEXT NULL,
    notes TEXT NULL,
    approved_by BIGINT UNSIGNED NULL,
    approved_at DATETIME NULL,
    rejected_reason TEXT NULL,
    creator_id BIGINT UNSIGNED NULL,
    editor_id BIGINT UNSIGNED NULL,
    deleted_by BIGINT UNSIGNED NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    deleted_at TIMESTAMP NULL,
    
    INDEX idx_employee_dates (employee_id, start_date, end_date),
    INDEX idx_leave_type (leave_type_id),
    INDEX idx_status (status),
    INDEX idx_date_range (start_date, end_date),
    
    FOREIGN KEY (team_id) REFERENCES teams(id) ON DELETE CASCADE,
    FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE CASCADE,
    FOREIGN KEY (leave_type_id) REFERENCES leave_types(id) ON DELETE RESTRICT,
    FOREIGN KEY (approved_by) REFERENCES users(id) ON DELETE SET NULL
);
```

#### leave_balances table

```sql
CREATE TABLE leave_balances (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    team_id BIGINT UNSIGNED NOT NULL,
    employee_id BIGINT UNSIGNED NOT NULL,
    leave_type_id BIGINT UNSIGNED NOT NULL,
    year INT NOT NULL,
    allocated_days DECIMAL(5,2) NOT NULL DEFAULT 0,
    used_days DECIMAL(5,2) NOT NULL DEFAULT 0,
    pending_days DECIMAL(5,2) NOT NULL DEFAULT 0,
    available_days DECIMAL(5,2) NOT NULL DEFAULT 0,
    carried_over_days DECIMAL(5,2) NOT NULL DEFAULT 0,
    expires_at DATE NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    
    UNIQUE KEY unique_employee_leave_year (employee_id, leave_type_id, year),
    INDEX idx_employee_year (employee_id, year),
    INDEX idx_leave_type (leave_type_id),
    
    FOREIGN KEY (team_id) REFERENCES teams(id) ON DELETE CASCADE,
    FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE CASCADE,
    FOREIGN KEY (leave_type_id) REFERENCES leave_types(id) ON DELETE RESTRICT
);
```

#### leave_types table

```sql
CREATE TABLE leave_types (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    team_id BIGINT UNSIGNED NOT NULL,
    name VARCHAR(255) NOT NULL,
    code VARCHAR(50) NOT NULL,
    description TEXT NULL,
    color VARCHAR(20) NULL,
    icon VARCHAR(50) NULL,
    is_paid BOOLEAN DEFAULT TRUE,
    requires_approval BOOLEAN DEFAULT TRUE,
    max_days_per_year INT NULL,
    accrual_rate DECIMAL(5,2) NULL,
    accrual_frequency ENUM('monthly', 'quarterly', 'annually') NULL,
    allow_carryover BOOLEAN DEFAULT FALSE,
    max_carryover_days INT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    sort_order INT DEFAULT 0,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    deleted_at TIMESTAMP NULL,
    
    UNIQUE KEY unique_team_code (team_id, code),
    INDEX idx_active (is_active),
    
    FOREIGN KEY (team_id) REFERENCES teams(id) ON DELETE CASCADE
);
```

#### time_categories table

```sql
CREATE TABLE time_categories (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    team_id BIGINT UNSIGNED NOT NULL,
    name VARCHAR(255) NOT NULL,
    code VARCHAR(50) NOT NULL,
    description TEXT NULL,
    color VARCHAR(20) NULL,
    icon VARCHAR(50) NULL,
    is_billable_default BOOLEAN DEFAULT FALSE,
    default_billing_rate DECIMAL(10,2) NULL,
    is_active BOOLEAN DEFAULT TRUE,
    sort_order INT DEFAULT 0,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    deleted_at TIMESTAMP NULL,
    
    UNIQUE KEY unique_team_code (team_id, code),
    INDEX idx_active (is_active),
    
    FOREIGN KEY (team_id) REFERENCES teams(id) ON DELETE CASCADE
);
```

## Correctness Properties

*A property is a characteristic or behavior that should hold true across all valid executions of a system—essentially, a formal statement about what the system should do. Properties serve as the bridge between human-readable specifications and machine-verifiable correctness guarantees.*

### Property 1: Time Entry Duration Consistency

*For any* time entry with both start_time and end_time specified, the calculated duration_minutes must equal the difference between end_time and start_time in minutes.

**Validates: Requirements 1.1**

### Property 2: No Overlapping Time Entries

*For any* employee and date, when creating or updating a time entry with start_time and end_time, the system must reject entries that overlap with existing time entries for the same employee on the same date.

**Validates: Requirements 1.4**

### Property 3: Time Entry Data Completeness

*For any* created time entry, the record must contain date, duration_minutes, employee_id, and description fields with non-null values.

**Validates: Requirements 1.1**

### Property 4: Time Entry Association Validity

*For any* time entry, at least one of project_id, company_id, task_id, or time_category_id must be non-null.

**Validates: Requirements 1.2**

### Property 5: Billable Flag and Rate Storage

*For any* time entry where is_billable is true, the billing_rate field must be non-null and greater than zero.

**Validates: Requirements 1.3**

### Property 6: Duration Within Valid Range

*For any* time entry, the duration_minutes must be greater than 0 and less than or equal to 1440 (24 hours).

**Validates: Requirements 2.1**

### Property 7: Billable Time Requires Project or Client

*For any* time entry marked as billable (is_billable = true), the entry must have either a project_id or company_id (or both) associated with it.

**Validates: Requirements 2.2**

### Property 8: Employee Project Access Validation

*For any* time entry associated with a project, the employee must have access permissions to that project.

**Validates: Requirements 2.4**

### Property 9: Daily Hours Aggregation

*For any* employee and date, the sum of all time entry duration_minutes for that employee on that date must be correctly calculated and not exceed configured daily thresholds.

**Validates: Requirements 2.5**

### Property 10: Time Report Aggregation Accuracy

*For any* time report with specified filters, the sum of individual time entry durations in the result set must equal the total hours displayed in the report summary.

**Validates: Requirements 3.2**

### Property 11: Revenue Calculation in Reports

*For any* time report including billable hours, the total revenue must equal the sum of (duration_minutes / 60 * billing_rate) for all billable entries in the report.

**Validates: Requirements 3.5**

### Property 12: Billing Rate Determination

*For any* billable time entry, the billing_rate must be determined by the hierarchy: custom rate > project rate > employee default rate, and must be non-null.

**Validates: Requirements 4.1**

### Property 13: Billing Amount Calculation

*For any* billable time entry, the billing_amount must equal (duration_minutes / 60) * billing_rate, rounded to 2 decimal places.

**Validates: Requirements 4.2**

### Property 14: Billable Status Categorization

*For any* set of time entries, entries can be categorized into exactly three non-overlapping groups: billed (invoice_id is not null), unbilled (is_billable = true and invoice_id is null), and non-billable (is_billable = false).

**Validates: Requirements 4.3**

### Property 15: Invoice Linking

*For any* time entry that is invoiced, the invoice_id must be non-null and reference a valid invoice record.

**Validates: Requirements 4.4**

### Property 16: Unbilled Hours Grouping

*For any* billing report, unbilled hours grouped by client must sum to the same total as all unbilled time entries for that client.

**Validates: Requirements 4.5**

### Property 17: Absence Data Completeness

*For any* created absence record, the record must contain employee_id, start_date, end_date, leave_type_id, and duration_days with non-null values.

**Validates: Requirements 5.1**

### Property 18: Leave Type Support

*For any* absence, the associated leave_type must be one of the configured leave types for the team.

**Validates: Requirements 5.2**

### Property 19: Leave Balance Deduction on Approval

*For any* absence that transitions from pending to approved, the employee's leave balance for that leave type must decrease by exactly duration_days.

**Validates: Requirements 5.4**

### Property 20: Absence Duration Calculation

*For any* absence record, when calculating duration between start_date and end_date, the system must exclude weekends and holidays based on the employee's work schedule, and the result must match the stored duration_days.

**Validates: Requirements 5.5**

### Property 21: Leave Accrual Calculation

*For any* employee and leave type with accrual rules, when an accrual period completes, the allocated_days must increase by the accrual_rate amount.

**Validates: Requirements 6.2**

### Property 22: Leave Balance Consistency

*For any* leave balance record, the available_days must equal (allocated_days + carried_over_days - used_days - pending_days).

**Validates: Requirements 6.3**

### Property 23: Insufficient Balance Validation

*For any* absence request where duration_days exceeds the employee's available_days for that leave type, the system must reject the request or require special approval.

**Validates: Requirements 6.4**

### Property 24: Approval Status Transitions

*For any* time entry or absence, valid status transitions must follow the state machine: pending → approved, pending → rejected, and no other transitions are allowed (except for absences which can also transition to cancelled from any state).

**Validates: Requirements 8.1, 8.2, 8.3**

### Property 25: Rejection State and Resubmission

*For any* time entry that is rejected, the approval_status must be 'rejected' and the entry must be editable for resubmission.

**Validates: Requirements 8.3**

### Property 26: Locked Approved Entries

*For any* time entry with approval_status = 'approved', attempts to edit or delete the entry must be rejected unless the user has administrator privileges or the entry is explicitly unlocked.

**Validates: Requirements 8.4**

### Property 27: Task Time Rollup

*For any* task, when a time entry is created or updated with that task_id, the task's actual_hours must be recalculated as the sum of all time entry durations for that task divided by 60.

**Validates: Requirements 10.1**

### Property 28: Project Time Aggregation

*For any* project, the sum of duration_minutes for all time entries with that project_id must equal the project's total logged hours when converted to hours.

**Validates: Requirements 10.2**

### Property 29: Completed Task Validation

*For any* task marked as complete, attempts to create new time entries against that task must be rejected if the policy is configured to prevent time logging on completed tasks.

**Validates: Requirements 10.3**

### Property 30: Task-Project Referential Integrity

*For any* time entry associated with both a task and a project, the task must belong to the specified project.

**Validates: Requirements 10.5**

### Property 31: Export Data Completeness

*For any* exported time entry dataset, each record must include all relevant fields: employee, project, client, date, duration, billable status, and billing rate.

**Validates: Requirements 12.2**

### Property 32: Export Filter Consistency

*For any* time report export, the exported data must match exactly the filtered and date-ranged data visible in the report view.

**Validates: Requirements 12.3**

## Error Handling

### Validation Errors

- **Invalid Duration**: Return 422 with message "Duration must be between 1 minute and 24 hours"
- **Overlapping Time**: Return 422 with message "Time entry overlaps with existing entry from {start} to {end}"
- **Missing Required Association**: Return 422 with message "Billable time entries must be associated with a project or client"
- **Insufficient Leave Balance**: Return 422 with message "Insufficient leave balance. Available: {available} days, Requested: {requested} days"
- **Overlapping Absence**: Return 422 with message "Absence overlaps with existing absence from {start} to {end}"

### Authorization Errors

- **Unauthorized Edit**: Return 403 with message "Cannot edit approved time entries"
- **Unauthorized Approval**: Return 403 with message "You do not have permission to approve this entry"
- **Unauthorized Project Access**: Return 403 with message "You do not have access to this project"

### Business Logic Errors

- **Past Date Cutoff**: Return 422 with message "Cannot log time for dates older than {cutoff_date} without manager approval"
- **Completed Task**: Return 422 with message "Cannot log time against completed tasks"
- **Inactive Category**: Return 422 with message "Cannot use inactive time category"
- **Cancelled Absence**: Return 422 with message "Cannot modify cancelled absence"

### System Errors

- **Database Failure**: Return 500 with message "Failed to save time entry. Please try again"
- **Calculation Error**: Return 500 with message "Error calculating billing amount"
- **Export Failure**: Return 500 with message "Failed to generate report export"

## Testing Strategy

### Unit Testing

The system will use PHPUnit/Pest for unit testing with the following coverage:

1. **Model Tests**
   - Test TimeEntry duration calculation methods
   - Test Absence duration calculation with various date ranges
   - Test LeaveBalance recalculation logic
   - Test scope methods on all models
   - Test relationship definitions

2. **Service Tests**
   - Test TimeEntryService creation, update, and validation logic
   - Test AbsenceService approval workflow
   - Test LeaveBalanceService accrual and deduction
   - Test BillingCalculator rate determination and amount calculation
   - Test ValidationService overlap detection
   - Test ReportGenerator filtering and aggregation

3. **Calculator Tests**
   - Test billing amount calculation with various rates
   - Test duration calculation excluding weekends/holidays
   - Test leave balance calculations
   - Test report aggregation totals

### Property-Based Testing

The system will use a PHP property-based testing library (such as Eris or php-quickcheck) to verify correctness properties. Each property test will run a minimum of 100 iterations with randomly generated inputs.

**Property Test Configuration:**
- Minimum iterations: 100
- Seed: Random (logged for reproducibility)
- Shrinking: Enabled to find minimal failing cases

**Property Test Tagging:**
Each property-based test will include a comment tag in this format:
```php
// Feature: time-management-hr, Property 1: Time Entry Duration Consistency
```

### Integration Testing

1. **API Endpoint Tests**
   - Test time entry CRUD operations via API
   - Test absence submission and approval flow
   - Test report generation endpoints
   - Test export functionality

2. **Filament Resource Tests**
   - Test time entry creation through Filament forms
   - Test absence calendar widget
   - Test approval actions in list views
   - Test report filters and exports

3. **Workflow Tests**
   - Test complete time entry submission and approval cycle
   - Test absence request, approval, and balance deduction
   - Test time entry to invoice linking
   - Test leave accrual job execution

### Test Data Generators

Property-based tests will use generators for:

- **TimeEntry Generator**: Produces valid time entries with random dates, durations, employees, and associations
- **Absence Generator**: Creates absence records with valid date ranges and leave types
- **Employee Generator**: Generates employees with work schedules and leave balances
- **Date Range Generator**: Produces valid date ranges respecting business rules
- **Duration Generator**: Creates durations within valid ranges (1-1440 minutes)

### Edge Cases to Test

1. Time entries spanning midnight (start and end on different days)
2. Absences spanning year boundaries
3. Leave balance carryover calculations
4. Partial day absences (half days, quarter days)
5. Time entries on holidays and weekends
6. Concurrent time entry submissions for the same employee
7. Approval workflow with multiple approvers
8. Billing rate changes mid-project
9. Timezone handling for remote employees
10. Leap year date calculations

### Testing Tools

- **PHPUnit/Pest**: Primary testing framework
- **Property Testing Library**: Eris or php-quickcheck for property-based tests
- **Laravel Factories**: Generate test data
- **Database Transactions**: Isolate test data
- **Mockery**: Mock external dependencies
- **Carbon**: Date/time manipulation in tests
