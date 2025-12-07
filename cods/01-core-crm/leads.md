# Leads

Status: Implemented

Coverage:
- Done: 11 subfeatures (capture, status/source/grade/score, assignment fields, nurture fields, conversion targets, duplicate markers, web-form payload, export)
- Partial: 6 (round-robin/territory automation, nurture workflows, duplicate detection engine, imports, lead activity tracking UI)
- Missing: 0 not represented in the data model

What works now
- Lead capture with status, source, grade, score, assignment strategy, territory, nurture status/program, and next-touch fields (`app/Filament/Resources/LeadResource.php`, `app/Models/Lead.php`).
- Conversion targets captured via `converted_company_id`, `converted_contact_id`, and `converted_opportunity_id` plus timestamps and users.
- Duplicate detection markers (`duplicate_of_id`, `duplicate_score`) and web-to-lead payload storage (`web_form_key`, `web_form_payload`).
- Activity tracking fields (`last_activity_at`) and creation source auditing (`creation_source` defaulted to WEB).
- Export support via `LeadExporter` in the table toolbar; soft deletes and filters for status/source/grade/assignment/nurture/territory.
- Relations to tasks and notes for nurturing and qualification follow-up.

Gaps / partials
- Round-robin and territory-based assignment strategies exist as enum values but no scheduler/automation is wired up.
- Nurture workflows are data-only; no cadence engine or email/call scheduling.
- Duplicate detection relies on `duplicate_of_id` flags; no automated matching/merge UI.
- Imports, web-to-lead form endpoints, and conversion flows into contact/account/opportunity records are not yet implemented.
- Lead activity timeline UI is not exposed; `last_activity_at` is present for future tracking.

Source: docs/suitecrm-features.md (Leads)
