# Projects Module

## ADDED Requirements

#### Requirement 1: Projects can be created (or cloned from templates) with status and phase tracking.
- Scenario: A PM creates a new project from the “Customer Onboarding” template, sets Status to In Progress, Phase to Discovery, and adjusts the phase order; the project saves with inherited baseline tasks/milestones, records the template lineage, and tracks status/phase history for reporting.

#### Requirement 2: Milestones, deliverables, dependencies, and documentation are managed as first-class project artifacts.
- Scenario: The PM adds a milestone “Contract Signed” that depends on deliverable “Finalize SOW” and attaches the signed PDF; the system enforces the dependency, blocks milestone completion until the deliverable is marked done, and keeps the document linked from the milestone and project record.

#### Requirement 3: Project team management and resource allocation prevent overbooking.
- Scenario: The PM assigns a project manager, tech lead, and two analysts with weekly allocation percentages; when one analyst is already allocated 90% on another project, adding 50% allocation triggers a warning, and the project stores role assignments and planned hours for resource reporting.

#### Requirement 4: Budgeting, time tracking, and progress rollups compare plan vs actual.
- Scenario: A project is budgeted for 400 hours and $120,000; as team members log time against tasks, the project rollup shows actual hours/cost, remaining budget, and percent complete derived from task and milestone progress, enabling variance reporting.

#### Requirement 5: Timelines, dashboards, and Gantt-style views surface project health.
- Scenario: The PM opens the project dashboard to see a timeline with milestones, a summary of open tasks, status/phase, percent complete, budget burn, and issues/risks; when a Gantt extension is enabled, the same tasks and dependencies render in a Gantt chart without additional data entry.

#### Requirement 6: Project risks and issues are tracked with mitigation and ownership.
- Scenario: The PM logs a risk “Vendor delay” with probability/impact, mitigation steps, and an owner; if it materializes, the risk converts to an issue linked to affected tasks and milestones, tracking status, resolution, and dates so project reports reflect current risks and issues.
