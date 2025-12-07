# Accounts

Status: Implemented

Coverage:
- Done: 12 subfeatures (core profile, hierarchies, addresses, owners/assignees, attachments, activity timeline, document uploads, validation)
- Partial: 3 subfeatures (custom fields, multi-currency depth, relationship mapping breadth)
- Missing: 0 identified from the SuiteCRM list

What works now
- Company/Account CRUD with controlled dropdowns for account type and industry plus revenue/employee validation (`app/Filament/Resources/CompanyResource.php`, `app/Models/Company.php`).
- Parent/child hierarchy with cycle prevention and hierarchy navigation (`app/Models/Company.php`).
- Billing vs. shipping addresses with copy-to-shipping toggle, required billing street/city/country, and optional shipping override (`CompanyResource` form).
- Ownership/assignment and account team roles with enforced owner presence and access levels (`app/Models/Company.php`, `app/Models/AccountTeamMember.php`, `database/migrations/2024_08_24_133803_create_companies_table.php`).
- Website/phone/email/social links with URL and phone validation; media attachments for logo and files (`CompanyResource` form, `registerMediaCollections`).
- Activity timeline covering create/update, notes, tasks, opportunities, and attachments (`app/Models/Company.php#L238`).
- Account-level duplicate detection scaffolding via `DuplicateDetectionService` and team-aware slugging (`Company` model observers).

Gaps / partials
- Custom fields rely on the Relaticle custom fields engine and a freeform `custom_fields` payload; no Studio-style layout editor yet.
- Multi-currency is limited to a per-record `currency_code` with no FX conversion tables or rate history.
- Relationship mapping is limited to parent/child, owner/assignee, team members, and linked people/opportunities/tasks; no generic relationship matrix or visual relationship map.

Source: docs/suitecrm-features.md (Accounts)
