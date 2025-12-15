# Design Notes

## Data model and core fields
- Meetings store subject, description, start/end datetime with time zone, planned duration, location type (onsite address vs virtual link), status (Scheduled, In Progress, Held, Not Held, Cancelled, Rescheduled), and outcome plus optional agenda/minutes attachments so lifecycle and tracking remain consistent.
- Attendees are modeled via a join that supports internal users/teams and external contacts/email-only invitees, storing roles (required/optional), response status, reminder preferences, and delivery channel so reminders and invitations can be targeted per person.
- Meetings link to related CRM records (account, opportunity, contact/person, case, lead) to surface context on timelines and allow follow-up tasks/notes to attach to both the meeting and the related record.

## Scheduling, recurrence, and reminders
- Recurrence rules (daily/weekly/monthly with end conditions) are stored alongside the series master; single-occurrence exceptions persist as overrides so rescheduling or cancelling one instance does not mutate the entire series.
- Reminders are per-attendee with default offsets (e.g., 24h/15m) and channel options (email, in-app, push). Status transitions (Scheduled -> In Progress -> Held/Not Held/Cancelled) record timestamps and actual duration when the meeting is started/stopped.
- Invitations include ICS payloads for external invitees and native calendar entries for internal users; responses update attendee status and trigger recalculated reminders and free/busy updates.

## Calendar, rooms, and conferencing
- Meeting records materialize on the unified calendar views and support drag-to-reschedule, which updates start/end times, reminder schedules, invites, and recurrence exceptions.
- Room booking uses resource calendars to check availability and hold reservations; conflicts block saves or prompt alternative slots before writing the meeting and room assignment.
- Virtual meetings call out to providers (Zoom/Teams) to create/update/delete join info, storing host/join URLs and dial-ins; the system updates links when times move and includes conferencing details in invites, agenda exports, and minutes.
