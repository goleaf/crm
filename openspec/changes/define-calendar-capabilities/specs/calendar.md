# Calendar Module

## ADDED Requirements

### Unified calendar views across activities
- The system shall present a unified calendar that aggregates meetings, calls, tasks, and other activities into day, week, month, and year layouts with timezone-aware rendering and all-day vs timed event distinctions.
#### Scenario: Switch between day and month views
- Given a user opens the calendar with meetings, calls, and tasks scheduled this week
- When they toggle from Month view to Day view for Wednesday
- Then the calendar refreshes to show the same activities in the day grid with correct start/end times, all-day banners, and the user's timezone applied

### Quick scheduling from calendar slots
- The calendar shall allow creating activities directly from a selected slot, pre-filling start/end, date, calendar/owner, and activity type (meeting, call, task), and saving to the appropriate module without leaving the view.
#### Scenario: Quick create a meeting from week view
- Given a user is in week view and clicks the Thursday 2:00 PM slot
- When they choose "New Meeting," enter subject and attendees, and save
- Then the meeting is created at Thursday 2:00 PM on their calendar, appears immediately in week and month views, and is available in the Meetings module

### Drag-and-drop rescheduling with validation
- Users shall drag or resize events to new times or dates; the system shall validate permissions and conflicts, persist the new start/end, and log the reschedule for audit or notifications.
#### Scenario: Move a call to the next day
- Given a user with edit rights sees a call on Tuesday at 10:00 AM
- When they drag the call to Wednesday at 11:00 AM
- Then the call's start/end update to Wednesday 11:00 AM, conflicts are checked, attendees are notified when rules require, and the change is recorded in the event history

### Color-coded calendars and events
- Calendars and activity types shall have assignable colors with a legend so merged personal/shared/team calendars remain visually distinct; colors shall carry into exports and print layouts.
#### Scenario: View color legend across team calendars
- Given a user is viewing their personal calendar merged with a team calendar
- When they open the color legend
- Then each calendar shows its assigned color, events render with those colors, and the same colors appear in exported or printed outputs

### Shared and team calendars with permissions
- Users shall create shared and team calendars, invite individuals or groups, and assign permissions (busy-only, read-only, edit) that govern visibility and editing; team calendars aggregate member events respecting each owner's permissions.
#### Scenario: Share a team calendar read-only
- Given a manager owns the "Team A" calendar
- When they share it with a partner as read-only
- Then the partner can view all Team A events and details allowed by permissions, cannot drag/drop or delete them, and private events appear only as busy blocks

### Calendar filters and search
- The calendar shall support filters (owner/team/calendar, activity type/status, time range) and keyword search across titles, locations, and attendees to narrow what is rendered.
#### Scenario: Filter to one teammate and search for "demo"
- Given multiple personal and team calendars are merged
- When the user filters to calendar "Team A," selects activity type "Meeting," and searches for "demo"
- Then only matching demo meetings from Team A render in the current view, and other events are hidden until filters are cleared

### External sync with Google and Outlook
- The system shall support two-way sync with Google and Outlook calendars using OAuth, pushing new/updated/deleted events from the CRM and ingesting remote changes with conflict handling and status tracking.
#### Scenario: Sync a meeting to Google
- Given a user has connected their Google Calendar
- When they create a meeting in the CRM with time, attendees, and location
- Then the meeting appears on their Google Calendar with the same details, updates made in Google flow back to the CRM within the sync window, and conflicts are logged with a resolution note

### iCal export and subscription
- Users shall export or subscribe to calendars via secure iCal URLs or .ics downloads for specific views or filters (personal, team, shared, busy-only), and inbound .ics imports shall create events when permitted.
#### Scenario: Subscribe to a team calendar via iCal
- Given a user has access to the Team A calendar
- When they copy the provided iCal URL and add it to their external client
- Then the external calendar displays Team A events per the user's permissions and receives updates when new events are added or changed in the CRM

### Print-friendly calendar output
- The calendar shall offer print-ready layouts for the current view (day/week/month) that honor filters, permissions, and color legends so schedules can be distributed offline.
#### Scenario: Print a filtered week calendar
- Given a user filters to the sales team calendar for next week
- When they click Print
- Then the system generates a print preview showing only permitted events for that week with their colors and key details, hiding private event content as required
