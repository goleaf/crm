# SuiteCRM Feature Coverage

Total subpoints: 65
Implemented: 9
Partial: 24
Not implemented: 32

Sections:
- 01-core-crm: 6 entries
- 02-sales: 5 entries
- 03-marketing: 5 entries
- 04-communication: 7 entries
- 05-projects: 3 entries
- 06-knowledge: 3 entries
- 07-workflow: 3 entries
- 08-reporting: 4 entries
- 09-customization: 6 entries
- 10-integration: 3 entries
- 11-mobile-portal: 2 entries
- 12-data-management: 3 entries
- 13-ui-ux: 5 entries
- 14-system: 5 entries
- 15-advanced: 5 entries

Status key
- Implemented: capability exists in code and is surfaced via models/resources.
- Partial: data structures or some UX exist but major behaviors/automations are missing.
- Not implemented: no meaningful schema or UX support yet.

Highlights
- Core CRM: Accounts/Company, Contacts (People), Leads, Cases, Notes, and Tasks are implemented with Filament resources; Opportunities are partially covered.
- Sales: Invoices are implemented with numbering, line items, payments, reminders, and recurrence; Quotes/Products/Contracts/Forecasts are absent.
- Knowledge: Knowledge Base is implemented with articles, versions, approvals, comments, ratings, tags, FAQs, and templates.
- Workflow/Process: Process definition/execution/approval schemas exist but no automation engine.
- Data: Exports are available across major resources; imports and dedupe tooling are not yet built.
