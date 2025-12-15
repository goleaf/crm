# Task Management Requirements

## ADDED Requirements

#### Requirement 1: Create and categorize tasks with assignment, priority, status, and related-record links.
- Scenario: A user creates a task from a company record, sets the owner, adds two assignees, picks priority “High”, leaves status at “Not Started”, applies the “Onboarding” category, and links the task to the company and a related opportunity; the task saves with only the allowed status (Not Started/In Progress/Completed) and priority (High/Medium/Low) options, shows owner/assignee chips and category tags, and surfaces in the linked records’ timelines.

#### Requirement 2: Manage due dates with reminders and recurring schedules.
- Scenario: When a task is given a due date/timezone and reminder offsets (e.g., 1 day and 1 hour before), reminders are queued for the owner and assignees; changing the due date re-queues reminders, completing or deleting the task clears pending reminders, and creating a weekly recurrence generates future occurrences tied to the series so that updating the series updates future instances while preserving completed history.

#### Requirement 3: Support dependencies, subtasks, and blocked states.
- Scenario: A project task is broken into subtasks and set to depend on another task finishing first; the parent shows progress from subtask and checklist completion, attempts to start or complete the dependent task while prerequisites are open surface a blocked state and prevent completion, and once predecessors are marked Completed the blocked task can advance normally.

#### Requirement 4: Provide checklists and comments for task collaboration.
- Scenario: An assignee adds a checklist with several items and posts a comment; checklist completion updates a visible progress indicator (and contributes to parent rollups), comments capture author and timestamp in chronological order, and both are visible in the task detail and related-record timeline so collaborators can track what remains.

#### Requirement 5: Track time and delegation handoffs.
- Scenario: An assignee starts a timer on the task and later logs an additional manual duration; the system records per-user time entries, totals time spent on the task, and keeps entries attributed to the author; the owner delegates the task to another user who accepts and becomes the primary assignee while the delegator remains recorded in history and prior time entries stay intact.

#### Requirement 6: Filter and sort tasks for oversight.
- Scenario: In the task list, a user filters for High-priority tasks due this week that are not Completed, assigned to them, and categorized as “Onboarding”, optionally scoping to a related company; the view returns only matching tasks, supports a “blocked only” toggle for dependency-bound items, and allows sorting by due date, priority, or status to manage workload.
