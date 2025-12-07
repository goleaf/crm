# Design Notes

## Task Lifecycle and Recurrence
- Tasks reuse the existing task entity structure (assignment, priority, status, due date, reminders) with audit trails for status/ownership changes to support completion tracking and feed entries.
- Categories/tags live on a pivot table so multiple labels can be attached without expanding the core task schema; recurrence metadata stores frequency, interval, start/end, and category inheritance for generated instances.
- Reminder scheduling should emit notifications relative to each occurrence's due datetime and respect user notification preferences.

## Notes Capture and Privacy
- Notes use rich text storage with attachment references pointing to the document storage service used by files; notes maintain polymorphic links to any CRM entity to avoid duplicating note models.
- Visibility flags (private vs public) and categories/tags are stored on the note; access checks align with the parent entity's permissions plus note-level visibility to ensure private notes stay constrained.
- Note versioning stores author/timestamps for create and edits, keeping the previous versions accessible for history views when required by auditing rules.

## Activity Feeds and Logging
- Activity feeds aggregate normalized activity records (calls, emails, meetings, notes, tasks) keyed by related entity IDs with ordered timestamps for chronological rendering and pagination.
- Filtering/search should run against indexed fields (type, user/owner, dates, related entity IDs) and full-text indexes for subjects/bodies to keep feed queries performant.
- Automated logging ingests events from integrations (email, telephony, calendar) through webhooks/ETL pipelines that map external identifiers to CRM entities before persisting activity entries.
