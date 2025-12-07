# Company information management (details)

Status: Implemented

Coverage:
- Done: 8 acceptance points (required fields, billing vs. shipping, controlled dropdowns, numeric validation, parent hierarchy, URL/phone validation, attachments, timeline)
- Partial: 1 (timeline is model-level; UI timeline surface still minimal)
- Missing: 0

Evidence
- Required fields: `name`, `account_type`, `industry`, `account_owner_id`, and billing address street/city/country are required in `app/Filament/Resources/CompanyResource.php`.
- Billing vs. shipping: separate address fields with a copy-to-shipping toggle; stored independently in `companies` table (`database/migrations/2024_08_24_133803_create_companies_table.php`).
- Controlled dropdowns: enums for account type/industry/ownership plus currency code select (`CompanyResource` form).
- Numeric validation: revenue and employee count numeric/integer with min constraints; currency code stored as `currency_code` on the model.
- Parent relationships: parent company selector with cycle prevention logic in `app/Models/Company.php`.
- URL/phone/email validation: website and social links use URL validation; phone/email fields use validators in the form schema.
- Attachments: media collections for logo and attachments with uploader on the form; stored via Spatie Media Library.
- Timeline: `Company::getActivityTimeline()` surfaces create/update plus notes/tasks/opportunities; UI surface still needs rendering.

Source: docs/suitecrm-features.md (Company information management (details))
