# Account Data Requirements

## ADDED Requirements

#### Requirement 1: The `accounts` table is the primary store for Company Information Management data.
- Scenario: Any account created through the CRM (Create form, Quick Create, import, web form, or API) writes the primary identity (name, legal entity, ticker, ownership), contact information (phones, email, website), location (billing/shipping addresses), business intelligence (industry, revenue, employee count, SIC/NAICS), relationship context (type, rating, assigned user, teams), operational metadata (security groups, workflow states), and descriptive notes into `accounts` with a unique ID, created/modified timestamps, and indexing on the ID to support fast lookups.

#### Requirement 2: Auxiliary tables maintain extensions, audits, emails, relationships, and security assignments.
- Scenario: When an admin adds a custom field via Studio or similar tooling, values persist in `accounts_cstm` so the field can appear in Panel 5 and export templates; when an audited field changes, `accounts_audit` logs the before/after values so the View Change Log panel reflects the edit history; when email addresses are entered or updated, the system writes to `email_addresses` so campaigns and address books honor the contact information; relationships across modules and hierarchies are recorded in `relationships`; security groups attaching to the account populate `securitygroups_records` to enforce team-based access.

#### Requirement 3: Saving an account populates related context records atomically.
- Scenario: Saving creates or updates `email_addresses` rows for primary and alternate emails, creates `securitygroups_records` entries for each assigned group, and links parent/child accounts via `relationships` so the Detail View subpanels and workflows always see the same relational graph regardless of the entry method.
