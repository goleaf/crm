# Contacts

Status: Implemented

Coverage:
- Done: 12 subfeatures (full contact profile, multi-email/phone, address, reporting structure, social links, segmentation, lead source, account link)
- Partial: 3 (portal user capabilities, sync flags, contact segmentation automation)
- Missing: 0

What works now
- Contact CRUD with required name and company link plus job title/department, reporting structure, and assistant info (`app/Filament/Resources/PeopleResource.php`).
- Primary and alternate email fields with validation; office/mobile/home/fax numbers with phone regex checks.
- Physical address captured across street/city/state/postal/country.
- Social links (LinkedIn/Twitter/Facebook/GitHub) with URL validation and freeform segments via tags.
- Lead source field with suggestions; relation to accounts/companies via `company_id`.
- Portal flags and username fields captured; `is_portal_user` and `portal_username` stored on model.
- Custom fields supported through Relaticle custom fields builder injected into the form.
- Relations to cases, tasks, and notes via Filament relation managers.

Gaps / partials
- Portal user capability is limited to flags/username; no portal authentication or permissions surface.
- Sync toggles (`sync_enabled`, `sync_reference`) are stored but no sync job is implemented.
- Segmentation is manual via tags; no saved segments or dynamic list rules.

Source: docs/suitecrm-features.md (Contacts)
