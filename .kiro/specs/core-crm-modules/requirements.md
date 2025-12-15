# Requirements: Core CRM Modules

## Introduction

The Core CRM Modules cover Accounts, Contacts, Leads, Opportunities, and Cases. Together they provide end-to-end customer data management, sales lifecycle control, and service case handling with duplicate detection, assignment automation, and auditability.

## Glossary

- **Account**: Organization/company record.
- **Contact**: Person record linked to an account.
- **Lead**: Unqualified prospect captured via forms/imports.
- **Opportunity**: Potential revenue deal tracked through stages.
- **Case**: Support ticket with SLA and escalation rules.
- **Conversion**: Lead promotion into Account/Contact/Opportunity.
- **SLA**: Service Level Agreement time targets for Cases.

## Requirements

### Requirement 1: Account Management
**User Story:** As a sales rep, I manage organization records to maintain a single source of truth.
**Acceptance Criteria:**
1. Validate and persist account data: names, types, billing/shipping addresses, industry, revenue, employee counts, ownership, website/social links, multi-currency.
2. Support parent-child hierarchies with roll-up visibility.
3. Track assignment/ownership, activity history, and attachments.
4. Allow custom fields and relationship mapping to Contacts/Opportunities/Cases/Tasks/Notes.
5. Enable duplicate detection on name/domain/phone during create/import and show similarity scores.

### Requirement 2: Contact Management
**User Story:** As a relationship manager, I track people linked to organizations.
**Acceptance Criteria:**
1. Store full contact data: multiple emails/phones, addresses, job/department, reporting structure, birthdays, assistants, social links.
2. Link contacts to one or more accounts and maintain bidirectional visibility.
3. Capture lead source and portal access flags; enable synchronization/import/export.
4. Allow segmentation and custom fields for targeting.
5. Preserve activity history and attachments for each contact.

### Requirement 3: Lead Lifecycle
**User Story:** As a marketing ops owner, I want captured leads to be scored, routed, nurtured, and converted correctly.
**Acceptance Criteria:**
1. Support capture (web-to-lead, import), source tracking, status, scoring/grading, and qualification steps.
2. Route leads using round-robin and territory rules with audit logs.
3. Provide nurturing workflows and activity tracking prior to conversion.
4. Detect duplicates by name/domain/phone with fuzzy matching.
5. Convert leads into Contacts, Accounts, and Opportunities atomically with conversion history.

### Requirement 4: Opportunity Management
**User Story:** As a sales manager, I need predictable pipeline tracking.
**Acceptance Criteria:**
1. Manage sales stages, probability, expected close, amount, weighted revenue, competitors, win/loss reasons, next steps.
2. Support multi-user collaboration and team ownership.
3. Link to Quotes and feed forecasting dashboards with roll-ups and charts.
4. Provide stage progression rules and pipelines filtered by team/owner/date.
5. Surface dashboards and expected revenue metrics by stage and forecast category.

### Requirement 5: Case Management
**User Story:** As a support lead, I need SLA-driven ticket handling.
**Acceptance Criteria:**
1. Track status, priority, type, SLA timers, escalation rules, resolution notes, and response times.
2. Support assignment/queue routing and team-based handling.
3. Integrate with knowledge base, email-to-case, portal submission, and case threading.
4. Maintain portal visibility and customer communication history.
5. Provide audit trails and breach indicators with notifications.

### Requirement 6: Activity Timeline
**User Story:** As any user, I need a consolidated timeline on the account hub.
**Acceptance Criteria:**
1. Display notes, tasks, opportunities, and cases in reverse chronological order.
2. Inline actions (create/edit) must immediately reflect in the timeline.
3. Respect permissions and team scoping when showing related records.
4. Support filters by type/date/user and quick search.
5. Show attachments and links to related objects within each activity item.
