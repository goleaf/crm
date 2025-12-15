# Project Tasks (via Projects Module)

## ADDED Requirements

#### Requirement 1: Tasks support a work breakdown structure and templates.
- Scenario: A PM creates a project task hierarchy with parent “Implement API” and children “Design schema” and “Build endpoints”; they save the structure and then create a reusable task template from it so future projects can clone the same breakdown without re-entry.

#### Requirement 2: Task scheduling captures duration estimates and predecessor/successor dependencies.
- Scenario: The PM sets “Build endpoints” to start after “Design schema” with a 2-day lag and estimates a 5-day duration; the task stores start/due dates, duration estimate, predecessor/successor links, and automatically recalculates dates when the predecessor shifts so schedules stay aligned.

#### Requirement 3: Task progress, priorities, and milestone tracking are explicit.
- Scenario: A task is marked High priority with Percent Complete at 60% and flagged as a milestone; updating progress moves the milestone marker on the project timeline and recalculates project percent complete based on task weights.

#### Requirement 4: Task assignments and notifications keep owners aligned.
- Scenario: A task is assigned to two analysts; when its predecessor completes, both assignees receive a notification that the task is unblocked, and any reassignment sends an updated notification while maintaining assignment history.

#### Requirement 5: Task comments capture collaboration history.
- Scenario: During execution, team members add threaded comments documenting decisions; comments store author and timestamp and remain attached to the task for auditability and future reviews.

#### Requirement 6: Time logging and billing track billable work per task.
- Scenario: An analyst logs 6 billable hours at $150/hr and 2 non-billable hours to a task; the system records time entries with billable flags and rates, rolls them up to the task and project totals, and marks the task as ready for billing reports.

#### Requirement 7: Critical path and reporting surface task health.
- Scenario: With dependencies defined, the scheduler identifies tasks on the critical path and highlights them in reports; the task reporting view shows status, percent complete, planned vs actual dates, and whether a task is critical so PMs can prioritize interventions.
