# Requirements: Project & Resource Management

## Introduction

Defines project delivery, task execution, and employee resource tracking to manage timelines, budgets, and staffing.

## Glossary

- **Critical Path**: Longest chain of dependent tasks determining project duration.
- **Template**: Predefined project/task structure for reuse.
- **Time Log**: Recorded work entry with duration and billing metadata.

## Requirements

### Requirement 1: Project Management
**User Story:** As a project manager, I plan and track projects with milestones and risks.
**Acceptance Criteria:**
1. Create projects with statuses, templates, phases, milestones, timelines, and deliverables; manage documentation and dashboards.
2. Assign teams/resources, allocate budgets, track progress, issues, and risks; support reporting and Gantt (extension).
3. Support dependencies, project templates, and cloning; maintain audit history.

### Requirement 2: Project Tasks
**User Story:** As a team lead, I break work into tasks with dependencies and billing.
**Acceptance Criteria:**
1. Create hierarchical tasks with priorities, durations, start/end dates, predecessors/successors, milestones, % complete.
2. Assign users, capture comments, notifications, and time logging; support billing and templates.
3. Enforce dependencies and critical path; provide task reporting and notifications for changes.

### Requirement 3: Resource Management
**User Story:** As a resource manager, I balance workload and track employee information.
**Acceptance Criteria:**
1. Maintain employee directory with contact info, department, role, manager, status, start date, emergency contacts, portal access.
2. Track skills/certifications, performance notes, documents, payroll integration hooks, time-off tracking.
3. Monitor resource allocation and flag over-allocation; integrate with project/task assignments.

### Requirement 4: Time Tracking and Budgeting
**User Story:** As a finance partner, I monitor project costs.
**Acceptance Criteria:**
1. Allow time logging per task with date/duration/user/billable flags; prevent overlaps/duplicates.
2. Roll up time and billing to project budgets; show budget vs actuals and overruns.
3. Support export/reporting of time logs and budget summaries.
