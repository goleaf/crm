# Organization Management

## ADDED Requirements

### Organization records capture company details and contact information
- The system shall create and manage organization/company records with required name plus website, primary phone/email, billing/shipping addresses, industry, size, and ownership metadata so downstream modules share consistent company profiles.
#### Scenario: Create a company with full details
- Given a user enters name = "Acme Corp", website, main phone, billing/shipping addresses, industry, and employee count
- When they save the organization
- Then the company record stores all provided details and is available for linking from contacts and opportunities

### Organization hierarchy models parents and subsidiaries
- The system shall allow linking organizations in parent/child hierarchies and display rollups so account trees, roll-up views, and reporting understand corporate structures.
#### Scenario: Link a subsidiary to its parent
- Given "Acme Subsidiary" should roll up under parent "Acme Corp"
- When the user sets parent = Acme Corp on the subsidiary
- Then the subsidiary shows Acme Corp as parent, Acme Corp lists the subsidiary, and hierarchy-aware reports include the relationship

### Organizations support multiple contacts with defined roles
- The system shall associate many contacts to an organization with role/relationship metadata (e.g., Billing Contact, Technical Contact, Executive Sponsor) so teams can identify the right people per account.
#### Scenario: Assign contacts with roles to an organization
- Given contacts Jordan (Decision Maker) and Alex (Technical Contact)
- When both contacts are linked to "Acme Corp" with their respective roles
- Then the organization detail shows both contacts and roles, and each contact profile references Acme Corp

### Organization custom fields extend account data
- The system shall allow admins to add custom fields to organizations, surface them on forms/lists, and persist values alongside standard fields to capture domain-specific account data.
#### Scenario: Capture a custom renewal month
- Given a custom field "Renewal Month" exists for organizations
- When a user sets Renewal Month = "July" on Acme Corp
- Then the value is saved, visible on the organization view, and available for filtering/export

### Organization activity tracking records engagement
- The system shall maintain an organization-level activity timeline aggregating calls, meetings, tasks, emails, notes, imports, and workflow events tied to the organization or its contacts to provide a full engagement history.
#### Scenario: Review account activity
- Given a meeting and two tasks were logged against Acme Corp and its contacts
- When a user opens the organization timeline
- Then they see the meeting and both tasks with subjects, owners, and due dates ordered chronologically

### Organization notes preserve account context
- The system shall allow creating timestamped notes attached to an organization with author and rich text content so account teams can share context outside formal activities.
#### Scenario: Record an account strategy note
- Given an account manager documents a strategy update for Acme Corp
- When they save the note on the organization
- Then the note is stored with author/timestamp and is visible alongside other organization notes
