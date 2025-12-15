# SuiteCRM Feature Catalog

## ADDED Requirements

### Catalog location and structure
- The SuiteCRM capability catalog shall live at `docs/suitecrm-features.md` and organize features into 15 numbered categories with nested bullet lists that mirror SuiteCRM modules and capability groupings.
#### Scenario: Locate the catalog
- Given a stakeholder needs the SuiteCRM capability baseline
- When they open `docs/suitecrm-features.md`
- Then they find sections numbered 1–15 with category headers and bullet lists for each module or capability area.

### Core CRM and sales coverage
- The catalog shall enumerate Core CRM Modules (Accounts with detailed company information acceptance criteria, Contacts, Leads, Opportunities, Cases) and Sales/Revenue capabilities (Quotes, Products, Contracts, Forecasts, Invoice extensions) with their respective subfeature bullets exactly as listed in the source document.
#### Scenario: Review core and sales modules
- Given a reader scans sections 1 and 2
- When they review the Accounts, Contacts, Leads, Opportunities, Cases, Quotes, Products, Contracts, Forecasts, and Invoices bullets
- Then they see the documented subfeatures, including the detailed acceptance criteria for Company information management under Accounts.

### Marketing, communication, and activity scheduling coverage
- The catalog shall document Marketing and Campaign Management (Campaigns, extended campaign features, Target Lists, Targets, Surveys) and Communication/Collaboration (Emails, Calls, Meetings, Calendar, Tasks, Notes), with the Calls subsection listing: Call logging and tracking; Call scheduling; Inbound/outbound classification; Call duration tracking; Call status (Planned, Held, Not Held, Cancelled); Call purpose documentation; Call outcome recording; Call reminders; Call participants; Related record association; Call history; Call notes; Call follow-up tasks; Integration with VOIP systems; Click-to-dial capabilities.
#### Scenario: Verify marketing and call coverage
- Given a user opens sections 3 and 4
- When they inspect Campaigns, Target Lists, Targets, Surveys, Emails, and Calls
- Then they see the full marketing bullets and the Calls list including VOIP integration and click-to-dial alongside logging, scheduling, statuses, reminders, participants, history, notes, and follow-ups.

### Project, knowledge, workflow, reporting, customization, integration, data, UX, system, and advanced coverage
- The catalog shall capture sections for Projects/Project Tasks/Employees; Knowledge and Document Management (Documents, Knowledge Base, Bugs); Workflow and Automation (Workflows, Workflow Actions, Workflow Calculated Fields); Reporting and Analytics; Customization and Administration (Studio, Module Builder, Developer Tools, Admin Panel, Role Management, Security Suite); Integration and API (API access, external integrations, email platform integrations); Mobile and Portal; Data Management (import/export, data management features, search); User Interface and Experience (themes, dashboards/navigation/views/UX); System and Technical (administration, performance, logging, security, backup/recovery); and Advanced Features (process management, advanced customization, PDF management, territory management, advanced email features).
#### Scenario: Confirm remaining catalog sections
- Given a reviewer continues through sections 5–15
- When they check each category after Communication/Collaboration
- Then they find the listed topics with their bullet subfeatures present as the baseline reference.
