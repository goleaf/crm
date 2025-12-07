# Tasks & Activities

## ADDED Requirements

### Task lifecycle capture with assignment, scheduling, and completion
- The system shall allow creating tasks with subject, description, owner/assignee, due date/time, priority, status (e.g., Not Started, In Progress, Completed), reminder settings, and shall track status transitions and completion timestamps.
#### Scenario: Create and complete a prioritized task with reminder
- Given a user creates a task assigned to a teammate with priority High, due tomorrow at 5:00 PM, status Not Started, and a reminder 1 hour before
- When the teammate updates status to In Progress and later marks it Completed
- Then the task retains the assignment, due date/time, priority, reminder schedule, and records the status changes with the completion timestamp.

### Task categorization and recurring series
- The system shall support tagging tasks with categories/tags and configuring recurring tasks (daily/weekly/monthly) with interval, start date, and end conditions, ensuring generated occurrences inherit the category.
#### Scenario: Configure a recurring categorized task
- Given a user needs a weekly onboarding follow-up categorized as "Onboarding"
- When they create a task that repeats every Monday for 6 weeks with that category
- Then the system stores the recurrence pattern, generates each Monday occurrence carrying the category, and allows updates per occurrence while retaining the series metadata.

### Note capture with rich text, attachments, and entity coverage
- Users shall add notes with rich text formatting and file attachments to any CRM entity (such as accounts, contacts, deals, cases, or tasks), maintaining links to the originating record.
#### Scenario: Attach a rich note to an account
- Given a user views an account record
- When they add a note with formatted text, upload a file, and save
- Then the note links to the account, stores the rich text content and attachment, and appears in the account's notes list.

### Note visibility, categorization, and history
- Notes shall support private vs public visibility, optional categories/tags, and maintain author/timestamp history including edits so viewers can audit changes.
#### Scenario: Save a private categorized note with edit history
- Given a user creates a private note on a deal and tags it "Legal"
- When they later edit the note content
- Then the note remains private to permitted roles, retains the "Legal" category, and records the author and timestamps for creation and edit in the note history.

### Activity timeline coverage and ordering
- Activity feeds on CRM records shall display a complete chronological history of calls, emails, meetings, notes, and task events with clear timestamps and type labels.
#### Scenario: View a record's activity feed chronologically
- Given a contact has calls, emails, meetings, notes, and task updates
- When a user opens the contact's activity feed
- Then all activities appear in chronological order with timestamps and type labels showing the full history.

### Activity filtering and search
- Activity feeds shall provide filters (by activity type, date range, user, or related entity) and keyword search across subjects and note bodies to help locate entries quickly.
#### Scenario: Filter and search activities
- Given a user needs recent communications for a deal
- When they filter the activity feed to calls and emails from the last 30 days and search for "pricing"
- Then only matching calls/emails within that date range appear and entries containing "pricing" are highlighted in results.

### Automated activity logging
- The system shall automatically log activities from integrated sources (e.g., sent/received emails, held calls, completed meetings) and associate them with related CRM records to reduce manual entry.
#### Scenario: Auto-log a completed meeting
- Given a calendar integration tracks meeting outcomes
- When a scheduled meeting is marked Held with participants linked to an account
- Then the system auto-creates a meeting activity on the account's feed with time, attendees, and status without manual data entry.
