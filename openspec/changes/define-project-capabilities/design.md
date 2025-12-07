# Design Notes

## Project structure and lifecycle
- Projects carry status, phase, and template lineage so new projects can clone baseline tasks/milestones and maintain a status/phase history for reporting; phases should be ordered and enforce allowed transitions.
- Milestones, deliverables, and documentation live as related records to keep timelines, acceptance checkpoints, and knowledge assets visible from the project dashboard.
- Risks and issues track severity, ownership, and impact with links to affected tasks or milestones, enabling mitigation and resolution workflows tied back to the project.

## Planning and tracking instrumentation
- Timelines and dependencies should render through a shared scheduling model (tasks with start/due dates, predecessors/successors) so Gantt-style extensions can visualize the same data without duplicating logic.
- Resource allocation and budgeting require structured fields for planned vs actual hours/costs plus allocation by team member; time tracking entries roll up to projects and tasks for variance reporting.
- Progress tracking should aggregate from tasks/milestones (percent complete, statuses) to the project, keeping calculation rules consistent across dashboards, reports, and exports.

## Task breakdown and execution
- Work breakdown structures nest tasks under projects with optional templates to seed common task sets; dependencies use predecessor/successor links with lag/lead allowances to support critical path calculations.
- Task records store planned duration, percent complete, priority, assigned users, and comments; time logs and billing entries attach to tasks with billable flags and rates to support invoicing or budget burn tracking.
- Notifications should be driven by assignment changes, dependency readiness, milestone tie-ins, and status updates so owners get timely prompts on blockers and completions.
