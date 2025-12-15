# Employee Records

## ADDED Requirements

#### Requirement 1: Employee records capture core employment data with validation.
- Scenario: HR creates a new employee record entering name, employee ID, job title, department, manager, start date, and employment status (choices: Onboarding, Active, Leave, Terminated); saving enforces required fields, ensures start date is not in the future when status is Active, and logs the creator and timestamp.
- Scenario: Updating employment status to Terminated requires an effective date and reason, stores the change history, and prevents future-dated start dates or conflicting statuses.

#### Requirement 2: Contact details store work and personal channels.
- Scenario: An HR user enters work email/phone and optional personal email/mobile for an employee; the system validates email/phone formats, flags the work email as the primary contact, and allows marking personal contact info as private while still storing it for emergency use.
- Scenario: Directory and record views pull contact details from the stored values, showing primary work contacts while respecting privacy flags for personal channels.

#### Requirement 3: Department assignment and reporting structure stay in sync.
- Scenario: Assigning a manager and department on the employee record establishes reporting relationships; when the manager is changed, all direct reports automatically reflect the new reporting path without manual edits on each subordinate record.
- Scenario: Job title and role changes propagate to the directory and any approval/workflow rules that depend on role membership, keeping organizational data consistent.

#### Requirement 4: Emergency contacts and portal access are maintained on the record.
- Scenario: HR adds one or more emergency contacts with name, relationship, phone, and email; saving enforces at least one method of contact per emergency contact and allows marking the primary emergency contact.
- Scenario: Enabling portal access on an employee record triggers credential provisioning (or portal invite), records the portal username, and stores the date and actor who granted portal access; disabling access revokes login without deleting the employee record.

#### Requirement 5: Employee documents are stored with metadata and access controls.
- Scenario: A user uploads documents (offer letter, NDA, ID) to the employee record selecting a document type and effective/expiration dates; the system stores uploader, version, and portal visibility, allowing authorized users (and optionally the employee via portal) to download current documents while retaining prior versions.
