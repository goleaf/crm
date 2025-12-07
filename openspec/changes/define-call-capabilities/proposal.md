# Proposal: define-call-capabilities

## Change ID
- `define-call-capabilities`

## Summary
- Capture SuiteCRM-style call logging, scheduling, and lifecycle controls so calls are tracked with direction, timing, status, and outcomes.
- Describe participant and related-record associations so calls surface in account, contact, opportunity, and case histories.
- Spell out post-call documentation, reminders, notes, and follow-up tasks to close the loop after calls.
- Integrate VOIP providers for click-to-dial and auto-logging inbound/outbound calls with provider metadata.

## Capabilities
- `call-capture`: Record inbound/outbound calls with purpose, planned/actual timing, status, duration, and outcomes.
- `call-scheduling`: Plan calls with statuses, reminders, timezone-aware scheduling, and lifecycle updates for held or missed calls.
- `call-relationships`: Associate participants and related records, expose call history, and support notes and follow-up tasks.
- `call-telephony-integration`: Connect VOIP providers for click-to-dial and inbound event ingestion, syncing call IDs, recordings, and durations automatically.

## Notes
- OpenSpec CLI tooling is unavailable in this environment; requirements are documented manually.
