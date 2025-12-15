# Proposal: define-suitecrm-feature-catalog

## Change ID
- `define-suitecrm-feature-catalog`

## Summary
- Capture the full SuiteCRM capability catalog (as documented in `docs/suitecrm-features.md`) to baseline scope across modules before breaking down implementation specs.
- Organize the catalog by module categories (Core CRM, Sales & Revenue, Marketing, Communication/Collaboration, Projects/Resources, Knowledge & Documents, Automation, Reporting, Customization/Admin, Integration/API, Mobile/Portal, Data Management, UI/UX, System/Technical, Advanced features) with traceable subfeatures.

## Capabilities
- `core-crm-catalog`: Accounts, Contacts, Leads, Opportunities, Cases feature listings, including detailed acceptance for company information management.
- `sales-revenue-catalog`: Quotes, Products, Contracts, Forecasts, and Invoice extension capabilities.
- `marketing-targeting-catalog`: Campaigns, target lists, targets, and surveys feature coverage.
- `collaboration-scheduling-catalog`: Emails, Calls, Meetings, Calendar, Tasks, and Notes capabilities.
- `projects-resources-catalog`: Projects, project tasks, and employees feature sets.
- `knowledge-documents-catalog`: Documents, Knowledge Base, and Bugs feature listings.
- `automation-reporting-catalog`: Workflows, workflow actions/calculated fields, reports/analytics coverage.
- `customization-admin-catalog`: Studio, Module Builder, developer tools, admin panel, roles, and security groups features.
- `integration-mobile-portal-catalog`: API, external integrations, email platform integrations, mobile access, and customer portal.
- `data-ux-system-advanced-catalog`: Data management, search, UI/UX, system/technical, and advanced feature groupings (process management, customization, PDF, territory, advanced email).

## Notes
- The `openspec` CLI is not available in this environment; documentation and validation are manual.
