# Meetings

## ADDED Requirements

### Meeting scheduling captures time, location, time zone, and duration
- The system shall allow creating/editing a meeting with subject, start datetime, duration (auto-calculated end), time zone, and location type (onsite address/room vs virtual link) so scheduling, location tracking, and availability checks remain accurate.
#### Scenario: Schedule a hybrid client meeting
- Given a user creates a meeting for next Tuesday at 10:00 AM America/Los_Angeles with a 60-minute duration
- When they set the location to onsite with address "123 Market St" and attach a Zoom link for remote attendees
- Then the meeting stores the start, end=11:00 AM, time zone, physical address, and video link, and it appears on the calendar in the correct local time for all invitees

### Attendee management with internal and external invitees
- The system shall support adding internal users/teams and external contacts/email-only invitees as attendees, sending invitations, and tracking per-attendee role (required/optional) and response status (Accepted/Tentative/Declined/No Response).
#### Scenario: Invite account team and client stakeholders
- Given a meeting tied to an account
- When the organizer adds two internal reps as required, one optional manager, and two external client emails
- Then invitations are sent to all participants (internal calendar entries plus external email/ICS), responses update each attendee's status, and the organizer can continue editing without losing tracked responses

### Meeting reminders and status lifecycle with outcomes
- The system shall allow configurable reminders (e.g., 24 hours and 15 minutes before) by channel and track status transitions (Scheduled -> In Progress -> Held/Not Held/Cancelled/Rescheduled) with recorded outcomes and actual duration.
#### Scenario: Remind attendees and record the result
- Given a meeting scheduled for tomorrow with reminders at 24 hours and 15 minutes
- When the reminder times are reached and the organizer later marks the meeting Held with outcome "Decision pending follow-up"
- Then reminders dispatch via the chosen channels, the status history records the move from Scheduled to Held, the actual duration is captured, and the outcome is visible on the meeting record and related timelines

### Recurring meetings with per-occurrence changes
- The system shall create recurring meetings with rules (daily/weekly/monthly and end conditions) and allow rescheduling or cancelling individual occurrences while preserving the rest of the series.
#### Scenario: Adjust a weekly recurring series
- Given a weekly meeting every Wednesday at 2:00 PM for eight weeks
- When the organizer reschedules only the third occurrence to Friday and cancels the sixth
- Then the calendar shows the third occurrence on Friday, omits the sixth, and keeps the remaining Wednesdays intact with updated invites and reminders for the changed instances

### Calendar integration and room booking
- Meetings shall appear on the unified calendar views with drag-and-drop rescheduling, honor invitee free/busy data, and handle room booking by reserving available rooms and preventing double-booking.
#### Scenario: Reserve a conference room while scheduling
- Given the organizer adds a conference room resource to a meeting at 3:00 PM
- When another meeting already holds that room at the same time
- Then the system blocks the save or suggests alternative slots; if the organizer picks a free room, it reserves it, writes the meeting to all attendee calendars, and moves the reservation if the event is dragged to a new time

### Video conference integration
- The system shall generate and attach video conference details (Zoom or Teams) when a virtual meeting is selected, storing join/host URLs and dial-in info and updating/removing them on reschedule or cancellation.
#### Scenario: Create a Teams call for an external review
- Given the organizer selects "Virtual (Teams)" while creating a meeting
- When the meeting is saved
- Then a Teams link and dial-in details are generated, attached to the meeting and invitations, and automatically updated if the meeting time changes or removed if the meeting is cancelled

### Agenda, notes, minutes, and outcomes capture
- Meetings shall support pre-meeting agendas and post-meeting minutes/notes linked to the record, capturing decisions/outcomes and action items and making them visible to attendees and related CRM records.
#### Scenario: Capture minutes after the call
- Given a meeting with an agenda of three topics
- When the organizer uploads minutes summarizing decisions, records an outcome, and creates follow-up tasks/notes tied to the related opportunity
- Then the meeting shows the agenda and minutes, the outcome is stored, follow-up tasks/notes are linked to both the meeting and the opportunity, and attendees can view the minutes from their activity timeline
