# Tasks Module

## ADDED Requirements

### Task creation, assignment, and prioritization
- The system shall allow creating tasks with subject, description, owner/assignee, priority (High, Medium, Low), status (Not Started, In Progress, Completed), due date/time, and reminder settings from the main Tasks module and quick-create surfaces.
#### Scenario: Create a task with reminder
- Given a user opens Quick Create for Tasks
- When they enter a subject, assign it to a teammate, set priority High, status Not Started, due tomorrow at 3:00 PM, and add a 1-hour reminder
- Then the task is saved with those values, appears in the assignee's task list, and a reminder is scheduled for one hour before the due time

### Delegation and related record linkage
- Tasks shall support delegation by reassigning the task while keeping the requester linked, and shall allow linking to related records (account, opportunity, case) so the task appears in those timelines.
#### Scenario: Delegate a task linked to an account
- Given a user owns a task related to Account A
- When they delegate the task to another rep
- Then ownership transfers to that rep, the original requester remains visible in the task history, and the task still appears in Account A's related activities

### Subtasks, dependencies, and parent rollups
- The system shall allow creating subtasks and defining dependencies between tasks; parent tasks roll up completion status, and dependent tasks cannot be marked complete until prerequisites finish.
#### Scenario: Block completion until dependency finishes
- Given Task B depends on Task A
- When a user tries to mark Task B complete while Task A is still In Progress
- Then the system blocks completion, explains the dependency on Task A, and allows completion once Task A is marked Completed

### Checklists, comments, and time tracking
- Tasks shall include checklists (ordered items with completion flags), threaded comments, and time tracking entries (start/stop or manual duration) to capture collaboration and effort history.
#### Scenario: Track progress with checklist and time logs
- Given a task with three checklist items
- When the assignee checks off item 1, logs 45 minutes of work, and adds a comment
- Then the task reflects 1 of 3 checklist items completed, records the 45-minute time entry tied to the user, and displays the new comment in the thread

### Recurring tasks
- Users shall configure recurring tasks with frequency (daily/weekly/monthly), interval, start date, and end conditions (count or end date); edits may apply to one occurrence or the entire series with conflict checks.
#### Scenario: Create a weekly recurring task
- Given a user needs a standing follow-up
- When they create a task that repeats weekly on Fridays for 8 occurrences
- Then the system creates the series metadata, generates the instances on Fridays, and allows editing a single occurrence without altering the full series unless requested

### Task categories, filtering, and sorting
- Tasks shall support categories/tags and provide filtering/sorting by owner, team, priority, status, due date, category, related record, and keyword, with saved views for reuse.
#### Scenario: Filter and sort tasks by priority and due date
- Given a user has tasks across multiple teams and categories
- When they filter to team "Sales", category "Onboarding", status Not Started or In Progress, and sort by due date ascending then priority
- Then only matching tasks appear, ordered by nearest due dates with High before Medium before Low within the same due date
