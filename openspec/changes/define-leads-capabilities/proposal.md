# Proposal: define-leads-capabilities

## Change ID
- `define-leads-capabilities`

## Summary
- Capture end-to-end lead management: intake from forms/imports, qualification with scoring and grading, activity tracking, nurturing, and conversion into contacts, accounts, and opportunities.
- Define routing strategies (manual, round-robin, territory), duplicate handling, and assignment logging to ensure fair distribution and auditability.
- Specify web-to-lead intake, deduplication, and lifecycle transitions (status, nurture status, conversion) with history and export support.

## Capabilities
- `lead-intake-and-tracking`: Accept leads from web forms, imports, and manual entry with source attribution, deduplication, and an auditable timeline of activities and status changes.
- `lead-qualification-and-conversion`: Support scoring, grading, qualification checkpoints, and conversion flows to Contacts/Accounts/Opportunities while preserving provenance and preventing duplicate creation.
- `lead-routing-and-nurture`: Provide assignment strategies (manual, round-robin, territory), nurturing program tracking, workflow actions, and import/export pipelines.

## Notes
- `openspec` CLI tooling is unavailable here; specifications are drafted manually.
