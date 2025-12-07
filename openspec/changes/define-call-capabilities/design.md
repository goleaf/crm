# Design Notes

## Data & Lifecycle
- Call records carry team/creator context, purpose/subject, direction (Inbound/Outbound), planned start datetime with timezone, actual start/end, and derived duration when held.
- Status options are constrained to Planned, Held, Not Held, and Cancelled; transitions write history, require reasons for Not Held/Cancelled, and enforce actual timing for Held.
- Outcome fields store disposition/summary text so reporting and downstream workflows can react to results without parsing notes.

## Scheduling & Reminders
- Scheduling uses the planned datetime/timezone to calculate reminder offsets; reminders target the owner and participants and are re-queued when calls are rescheduled.
- Cancelling or marking Not Held should clear pending reminders while preserving the planned time for audit; held calls snapshot the actual timestamps and duration for analytics.

## Participants & Relationships
- Participants include internal users and external contacts/leads; tracking roles (organizer/attendee) keeps notifications and history accurate.
- Calls link to a primary related record (account, contact/person, opportunity, or case) so each module can surface the shared call history timeline.
- History views should pull participant names, direction, status, scheduled/held times, duration, and outcomes for quick review.

## Notes & Follow-ups
- Calls support attached notes using the existing notes module for richer context.
- Follow-up tasks reuse the tasks module and store a back-reference to the originating call and related record, keeping next steps traceable to the conversation that produced them.

## Telephony integration
- VOIP integration should accept inbound webhooks and outbound event callbacks to sync call IDs, direction, caller/callee identifiers, start/end/duration, and recording links back onto the call record.
- Click-to-dial actions originate calls through the configured provider, pre-fill subject/related record on the planned call, and transition status/duration when provider callbacks report completion.
