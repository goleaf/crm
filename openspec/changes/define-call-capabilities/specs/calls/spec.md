# Call Management

## ADDED Requirements

### Call record logging with direction and timing
- The system shall create call records that capture purpose/subject, inbound vs outbound classification, planned start datetime, and actual start/end timestamps to derive duration whenever a call is held.
#### Scenario: Log an inbound held call with duration
- Given a user records an inbound call and starts tracking at 09:05
- When they mark the call as Held at 09:23 with actual start and end times
- Then the call saves with direction Inbound, status Held, purpose, start 09:05, end 09:23, and duration of 18 minutes for reporting and history.

### Call scheduling with reminders
- The system shall allow scheduling future calls by setting a planned date/time (with timezone) and optional reminder offset so reminders queue for owners and participants.
#### Scenario: Schedule a future call with a reminder
- Given a user schedules a call for tomorrow at 14:00 America/New_York with a reminder 30 minutes before
- When they save the call
- Then the call is stored in status Planned with the scheduled datetime, timezone, and reminder, and a reminder notification is queued for all designated recipients.

### Call status lifecycle (Planned, Held, Not Held, Cancelled)
- Call records shall enforce status options Planned, Held, Not Held, and Cancelled, updating status history and requiring rationale when Not Held or Cancelled is selected.
#### Scenario: Mark a call as not held
- Given a planned call could not connect
- When the owner updates status to Not Held and enters a reason
- Then the status changes to Not Held, the reason and timestamp are stored, and duration stays empty.

### Call purpose and outcome documentation
- Calls shall store a purpose/reason for the call and capture a post-call outcome summary, including disposition codes or free text, when the call is completed or missed.
#### Scenario: Document purpose and outcome after a held call
- Given a user planned a discovery call with purpose "Qualify budget"
- When they complete it as Held and enter an outcome of "Interested" with notes
- Then the call retains the purpose and stores the outcome text/disposition for reporting and downstream follow-up.

### Participants and related record association
- Each call shall support multiple participants (internal users and contacts or leads) and link to a primary related record (account, contact/person, opportunity, or case) so all linked records display the call.
#### Scenario: Associate participants and an account
- Given a call involves two teammates and a contact from Acme Corp
- When the user adds both teammates and the contact as participants and sets Acme Corp as the related account
- Then the call shows all participants, ties to the account, and appears in the account and contact call history.

### Call notes and follow-up tasks
- Users shall attach notes to a call and spawn follow-up tasks tied to the call and its related record when outcomes require next steps.
#### Scenario: Capture notes and create a follow-up task
- Given a held call generated new actions
- When the owner records call notes and creates a follow-up task due next week linked to the same account
- Then the notes remain attached to the call, and the task is created with a link back to the call and the related account.

### Call history visibility
- Related records shall present a chronological call history showing direction, status, duration, participants, and outcomes for all linked calls.
#### Scenario: View call history on a contact
- Given a contact has multiple calls across statuses and directions
- When a user opens the contact's call history
- Then they see each call with its status (Planned, Held, Not Held, Cancelled), direction, participants, scheduled or held times, duration if held, and recorded outcomes.

### VOIP integration for inbound and outbound calls
- The system shall integrate with configured VOIP providers to originate outbound calls and ingest inbound call events, automatically logging direction, caller/callee identifiers, start/end/duration, and provider call IDs or recording links while associating calls to matching records.
#### Scenario: Auto-log an inbound VOIP call
- Given a VOIP provider webhook is configured and a known contact dials the tracked number
- When the call is answered and ends after 12 minutes
- Then the system records an inbound call with start/end timestamps, 12-minute duration, provider call ID, recording link when provided, and associates it to the matching contact and related account.

### Click-to-dial from CRM records
- The system shall offer click-to-dial actions on phone numbers, initiating the call via the configured VOIP provider, creating an outbound call record with the selected related record, and updating status and duration when the call completes.
#### Scenario: Click-to-dial a contact from their record
- Given a user views a contact with a phone number and the VOIP provider is connected
- When they click the call icon, confirm the subject, and place the call
- Then an outbound call is initiated through the provider, a Planned call record is created linked to the contact and account, and upon call completion the record updates to Held with actual start/end and duration.
