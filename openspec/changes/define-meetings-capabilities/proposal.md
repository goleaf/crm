# Proposal: define-meetings-capabilities

## Change ID
- `define-meetings-capabilities`

## Summary
- Translate the SuiteCRM Meetings capability list into explicit OpenSpec requirements that cover scheduling, location tracking, attendee management, reminders/statuses/duration, recurrence, collaboration artifacts (agenda, notes, minutes, outcomes), and integrations (calendar, rooms, video).
- Outline how meeting records connect to existing calendar surfaces, resource booking, and conferencing providers while keeping internal and external invitees aligned.

## Capabilities
- `meeting-scheduling-and-attendance`: Meeting creation/editing with time zone, duration, location, and attendee/invitee handling for internal and external participants plus reminders and statuses.
- `meeting-recurrence-and-calendar-integration`: Recurring series, per-occurrence changes, calendar visibility, and room booking with free/busy conflict handling.
- `meeting-collaboration-and-records`: Agenda, minutes, notes, outcomes, and related record linkage, including video conference details.

## Notes
- OpenSpec CLI tooling is not available in this environment; validation steps are documented for manual execution.
