# Employee Operations

## ADDED Requirements

#### Requirement 1: Skills and certifications are tracked with validity windows.
- Scenario: An administrator adds a certification to an employee with issuer, credential ID, issue date, and expiration date; the system saves verification status, flags upcoming expirations (e.g., 30/60/90 days), and surfaces current/expired badges on the employee profile and directory.
- Scenario: When a certification expires, the record remains for history but is marked expired, and managers receive a notification so renewals can be planned.

#### Requirement 2: Performance reviews and goals maintain historical tracking.
- Scenario: A manager records a quarterly review for an employee including rating, summary, reviewed goals, and review period; saving locks the review content, timestamps the reviewer, and links to the employee so multiple reviews build a performance history timeline.
- Scenario: Employees and managers can set goals with due dates and status (Not Started, In Progress, Completed); completing a goal during a review updates the goal status and ties it to the review for audit.

#### Requirement 3: Payroll integration syncs employment data changes.
- Scenario: When an employee is hired (status moves to Active) or terminated, the system generates a payload containing employee identifiers, department, job title, start/end dates, and employment status to send to the payroll system, logging delivery status without storing payroll amounts locally.
- Scenario: Changes to department or job title trigger a payroll sync update and record a history entry showing what changed, when, and who initiated the sync to keep payroll aligned with the CRM's employee data.

#### Requirement 4: Time-off balances and requests are managed with approvals.
- Scenario: The system tracks time-off balances (per policy type) with accrual rules; when an employee requests PTO via portal specifying dates and type, the request routes to their manager for approval, checks available balance, and blocks submission if insufficient.
- Scenario: Approving a request deducts from the balance, records the approver and timestamp, updates the employee calendar, and exports the approved leave for payroll/attendance reporting; declined or cancelled requests leave balances unchanged but maintain history.
