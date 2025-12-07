# Design Notes

## Lead Intake & Sources
- Leads arrive via manual entry, CSV/XLS imports, and web-to-lead forms; each record stores creation source (web, import, manual, API) plus lead source (website, event, referral, partner) for attribution.
- Web forms generate a keyed payload to replay submissions, enforce required fields, and capture consent (opt-in timestamp, IP) for compliance and deduplication.
- Imports map columns to lead fields, support preview/dedupe by email + phone/name, and reuse existing leads when a match is found instead of creating a duplicate.

## Qualification, Scoring, and Grading
- Scoring rules attach a numeric score and last-updated timestamp; grading captures analyst judgment (A/B/C/D or similar) to complement automated scoring.
- Qualification records the reviewer, timestamp, notes, and resulting status transition (e.g., Working → Qualified/Unqualified) to keep an auditable path to conversion.
- Nurture tracking stores program name, nurture status, and next touch date so workflows can schedule the next action and surface stalled leads.

## Routing, Assignment, and Territories
- Assignment strategies include manual selection, round-robin pools, and territory-based routing that picks an owner from the territory roster or falls back to manual when no eligible assignee exists.
- Territory resolution uses lead geography (state/country/zip) and optional custom territory tags; assignments log strategy, actor, and timestamp for audit.
- Round-robin respects user availability (active flag, capacity), rotates fairly, and records the last-assigned index per pool to avoid hotspots.

## Conversion and Activity History
- Conversions can create or link an Account, Contact, and optionally Opportunity, transferring ownership/teams and retaining the lead’s activity timeline.
- Conversion history stores who converted, when, and the resulting entity ids, and it blocks duplicate conversions while allowing edits to the converted targets.
- Activity tracking captures tasks, notes, timeline events (status/assignment/nurture changes), imports, web submissions, and dedupe actions for reporting.

## Data Quality and Duplicates
- Duplicate detection runs on email and phone + name combos, flags suspected duplicates with a match score, and allows merging or marking as non-duplicate.
- Lead records keep a `duplicate_of` link for merged pairs so history and assignments remain traceable; dedupe events are written to the activity log.
- Exports include lead ids, source, assignment details, nurture fields, score/grade, and conversion status to enable reconciliation with marketing systems.

## Tooling
- `openspec` CLI validation is unavailable in this environment; specs are written and reviewed manually.
