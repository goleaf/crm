# Proposal: define-project-capabilities

## Change ID
- `define-project-capabilities`

## Summary
- Capture SuiteCRM-style project management so projects can be created from templates, phased through statuses, and tracked across milestones, dependencies, deliverables, risks, and issues with dashboards and reporting.
- Define planning and tracking behaviors including timelines, resource allocation, budgeting, time tracking, and progress measurement, with support for Gantt-style visualizations via extensions.
- Specify how project tasks break down work: assignments, dependencies, durations, percent complete, notifications, billing/time logging, and critical path signals tied back to projects.

## Capabilities
- `project-lifecycle`: Project creation from templates with statuses, phases, milestones, dependencies, deliverables, documentation, risks/issues, and dashboards for oversight.
- `project-planning-tracking`: Timelines, resource allocation, budgeting, time tracking, progress tracking, and reporting with Gantt-style views when extensions are present.
- `project-task-execution`: Task breakdown structure with dependencies, durations, percent complete, assignments, time/billing logs, notifications, and critical path indicators.

## Notes
- The `openspec` CLI is unavailable in this environment; requirements are documented manually.
