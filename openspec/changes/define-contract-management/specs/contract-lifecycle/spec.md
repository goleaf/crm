# Contract Lifecycle Requirements

## ADDED Requirements

#### Requirement 1: Contract statuses flow through an approval-controlled lifecycle before activation.
- Scenario: New contracts start in Draft, move to Pending Approval when submitted, and only become Active after an approver (or required approvers) approves; rejection returns the contract to Draft with comments, and status transitions are logged.
- Scenario: Editing an Active contract that materially changes value, dates, or SLA requires re-approval, automatically moving status back to Pending Approval until approved.

#### Requirement 2: Renewal and expiration tracking drives proactive notifications and status updates.
- Scenario: A scheduler checks Renewal Date and End Date daily, marks contracts as Expiring within a configured window (e.g., 30/60/90 days), and sends notifications to the owner/team with links to renew or terminate.
- Scenario: When a contract reaches Renewal Date with auto-renew enabled, the system extends the End Date by the renewal term, updates Renewal Date for the next cycle, sets Status to Active, and records the renewal event; when auto-renew is off, the status shifts to Expiring/Expired and no extension occurs.

#### Requirement 3: Amendments and SLA compliance are tracked as part of the lifecycle.
- Scenario: Adding an amendment creates a child record referencing the parent contract, capturing amendment effective date, summary, updated terms/value/SLA, and resulting status (e.g., Active-Amended), while keeping prior versions intact for audit.
- Scenario: SLA monitoring reads the stored SLA thresholds, logs breaches when response/resolution times exceed commitments, and can notify owners or trigger escalation actions tied back to the active contract.
