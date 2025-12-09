# Project Management

## ADDED Requirements

#### Requirement 1: Project lifecycle and hierarchy support control from setup through archive.
- Projects shall support quick setup, parent/child hierarchy, status transitions, and archiving with historical record retention so teams can manage the full lifecycle from initiation to completion and long-term storage.
#### Scenario: Set up and archive a project with lineage
- Given a user quickly creates “Website Revamp” under the “Digital” parent program, sets status to “In Progress”, and later marks it Completed
- When the project is archived after completion
- Then the lineage (parent link), status history, and key data remain visible in archive views and reports, and the project can be unarchived if needed

#### Requirement 2: Project information captures timelines, budgets, and objectives.
- Projects shall store name, summary/objectives, status, priority, start/end dates, budget and resource allocation fields, tags, and owner references for oversight and reporting.
#### Scenario: Create a project with complete metadata
- Given a user creates “Website Revamp” with status “In Progress”, priority “High”, start date May 1, target end date Aug 15, budget $120,000, resource allocation notes, tags “marketing” and “Q3”, and a short objective
- When they save the project
- Then the record stores all fields, displays the timeline, budget, and tags on the detail page, and becomes available for filters, dashboards, and exports

#### Requirement 3: Centralized dashboards surface project health.
- A project dashboard shall present live overview metrics (status, priority, timeline, budget burn, assignments, milestones/tasks summary, risks/issues where applicable) and link to historical activity for monitoring.
#### Scenario: Review project health from the dashboard
- Given Casey opens “Website Revamp”
- When the dashboard loads
- Then they see the current status, percent of schedule elapsed, budget vs spend, key milestones/tasks, team roster, and recent activity in one view, with drill-down links to details and history

#### Requirement 4: Team, role, and manager assignments support multi-role membership.
- The system shall assign a project manager/owner plus multiple team members, allow multiple roles per user (e.g., Engineer + Reviewer), and capture allocation/notes per assignment.
#### Scenario: Assign multi-role project membership
- Given Casey is Project Manager, Jordan is Designer + Reviewer, and Priya is Engineer
- When the user assigns those roles and adds allocation notes
- Then Casey is shown as manager, Jordan shows both roles, Priya shows Engineer, allocation notes persist, and the team list displays in the dashboard and notifications

#### Requirement 5: Real-time updates keep project data synchronized with validation.
- Project detail pages and lists shall receive live updates for metadata, tags, roles, and status, using optimistic UI with validation and conflict checks to prevent silent overwrites and ensure data integrity.
#### Scenario: Live update with validation and conflict handling
- Given Alex and Casey both have the project page open
- When Casey updates status to “At Risk”, edits the budget, and adds Priya to the team
- Then Alex sees those changes instantly without refresh; if Alex tries to save an outdated budget, the UI prompts to reconcile with the latest validated state instead of overwriting

#### Requirement 6: Organized sorting and saved views prioritize work.
- Users shall sort projects by priority, status, manager, timeline, or budget, apply secondary sorts, and save reusable views (including tag filters) to keep critical projects on top.
#### Scenario: Save a prioritized view
- Given a user wants the most critical projects due soon
- When they sort by priority (High first), then by target end date, filter to status “At Risk”, and save the view as “Risky & Due Soon”
- Then the list returns matching projects in that order, the saved view is reusable, and new data respects the sorting/filtering automatically

#### Requirement 7: Role-based access and security controls govern project actions.
- Permissions shall cover view, edit, team management, budget/resource edits, archive/delete/restore, and access to audit history, with manager-level overrides where configured.
#### Scenario: Enforce permissions on sensitive actions
- Given Taylor has view-only access and Casey has manage access to “Website Revamp”
- When Taylor attempts to change the budget or team
- Then the action is blocked with a permission message, while Casey can perform the change, which is recorded in the audit trail with user and timestamp

#### Requirement 8: Customizable project fields and layouts support varied methodologies.
- Admins shall add project custom fields (text, number, date, picklist, relationships) per project type/category, include them on forms/detail/list/filter/export views, and bundle defaults into templates for quick setup.
#### Scenario: Add and apply a custom project field
- Given an admin creates a picklist custom field “Delivery Model” with options Managed and Self-Service for digital projects
- When the field is added to the digital project layout and set to “Managed” on “Website Revamp”
- Then the value saves with the project, appears on detail and list views, is filterable/exportable, and is pre-populated when using the digital project template

#### Requirement 9: Safe deletion, archiving, and recovery protect project data.
- Projects shall support soft deletion to trash with retention windows, archiving for long-term storage, dependency checks before purge, and restore paths that relink team, tags, and custom fields.
#### Scenario: Trash and recover a project safely
- Given “Legacy Migration” should be removed from active work
- When a project manager moves it to trash
- Then it is hidden from active lists, remains recoverable with assignments/tags/custom fields intact, and can be restored within retention or permanently deleted by an authorized admin after dependency checks

#### Requirement 10: Tagging and categorization enable fast retrieval.
- Projects shall support multiple tags with optional tag groups/hierarchies and color-coding so users can search, filter, group, and save tag-based views.
#### Scenario: Filter projects by tags for quick access
- Given a user tags “Website Revamp” with “marketing”, “Q3”, and “customer-facing”
- When they filter by those tags or load a saved “Marketing Q3” view
- Then the project appears immediately in the results, tag chips display on list/detail views, and tags remain consistent across dashboard and exports

#### Requirement 11: Quick project setup accelerates initialization.
- Users shall create projects rapidly via quick-create forms and templates that prefill status, hierarchy placement, default team roles, and common fields so setup takes minimal steps.
#### Scenario: Quick-create from a template
- Given a “Digital Launch” template defines default status “Planned”, parent program “Digital”, and starter roles (PM, Designer)
- When a user quick-creates “Website Revamp” from that template
- Then the project is created with those defaults applied, shows in the correct hierarchy, and is ready for further edits without manual field entry

#### Requirement 12: Employee-linked assignments surface workload and accountability.
- Project team assignments shall link directly to employee profiles, support multiple roles per person, and expose workload indicators to avoid over-allocation while keeping accountability clear.
#### Scenario: Add employees with workload context
- Given Jordan’s profile shows current workload
- When Jordan is added as Designer + Reviewer on “Website Revamp”
- Then the assignment links to Jordan’s profile, displays their roles, shows workload context (e.g., hours allocated), and flags if the new allocation would exceed defined thresholds

#### Requirement 13: Automatic validation preserves data integrity during realtime edits.
- Project edits (including budgets, dates, status, tags, team) shall run server-side validation with integrity checks, returning conflicts or rule violations without dropping realtime updates.
#### Scenario: Prevent invalid overlapping dates
- Given Alex updates “Website Revamp” end date to April 1 while the start date is May 1
- When they attempt to save
- Then the system blocks the change with a validation message, keeps the current valid dates visible, and other live updates still flow to the page

#### Requirement 14: Dashboards and reports provide project insights and audit history.
- Users shall access dashboards and exportable reports showing performance metrics (timeline adherence, budget/resource utilization, task/milestone progress) and audited activity logs of project actions.
#### Scenario: Review progress and activity for compliance
- Given Casey needs to report on “Website Revamp”
- When they open the dashboard and export a report
- Then they see timeline progress, budget vs spend, task/milestone completion, and an activity log of key changes (status, budget, team edits) with users/timestamps for audit

#### Requirement 15: Project collaboration integrates comments and chat.
- Projects shall support threaded comments with @mentions and file attachments, and link to contextual chat channels so discussions stay tied to project records.
#### Scenario: Collaborate on a project update
- Given a designer posts a comment with a mockup attachment and @mentions the PM
- When the PM views the project
- Then they see the thread and attachment in context, receive a notification, and can continue the conversation or jump to the linked chat channel without losing the project context

#### Requirement 16: Time and timesheet tracking roll up to projects.
- Time entries logged against project tasks or the project directly shall support billable/non-billable flags, approvals, and roll up to project summaries for hours/budget reporting.
#### Scenario: Log and approve time to a project
- Given Priya logs 6 hours (billable) on a project task and submits her timesheet
- When the manager approves the entry
- Then the project shows the 6 billable hours in its rollup, the timesheet status updates to approved, and the hours appear in exports for payroll/billing

#### Requirement 17: Milestone visibility keeps schedules on track.
- Projects shall define milestones with dates, owners, and percent-complete tracking, surface them on dashboards/timelines, and alert on approaching or overdue milestones.
#### Scenario: Monitor an approaching milestone
- Given a milestone “Design Complete” is due in 5 days
- When Casey views the project dashboard
- Then they see the milestone with owner and percent complete, a warning for the approaching due date, and a link to related tasks to address risks

#### Requirement 18: Work schedules and time zones inform project planning.
- Project planning shall respect team working hours, holidays, and time zones for scheduling dates, alerts, and workload displays to avoid conflicts and reflect true availability.
#### Scenario: Schedule with time zone awareness
- Given a distributed team with defined working hours and holidays
- When a PM sets target dates and reminders for “Website Revamp”
- Then schedules avoid non-working days, reminders respect each member’s time zone, and the dashboard highlights availability conflicts before finalizing dates

#### Requirement 19: Skills and job-type data guide resource allocation.
- Project assignments shall reference employee skill inventories and job types to match roles to competencies, highlight gaps, and support reporting on skill-based utilization.
#### Scenario: Assign by skill fit
- Given a task needs a Frontend Developer with React experience
- When the PM selects a resource for the project
- Then the system surfaces employees with matching skills/job type, flags gaps, and records the selected skills context for reporting and future allocation decisions

#### Requirement 20: Workflow and automation streamline project setup and updates.
- Projects shall support templates, auto-assignment rules, and workflow steps (including approvals) that prefill data, assign roles, and trigger notifications without manual re-entry, while keeping human overrides audited.
#### Scenario: Auto-assign and approve a project change
- Given a project type “Client Implementation” has rules to auto-assign PM and Reviewer and requires approval for budget changes
- When a new project is created from that type and later the budget is increased
- Then PM/Reviewer are assigned automatically, the budget change requests approval, notifications are sent to approvers, and the approved change is logged with user and timestamp

#### Requirement 21: Timeline and Gantt-style views visualize schedules and dependencies.
- Projects shall render tasks/milestones on timeline/Gantt views with dependencies, critical path indicators, and drag/drop date adjustments that respect validation and notify affected owners.
#### Scenario: Adjust a dependent timeline
- Given tasks have finish-to-start dependencies
- When Casey drags a task later on the Gantt
- Then successors shift according to dependencies, critical path updates, validations prevent invalid overlaps, and assigned owners receive notifications of the schedule change

#### Requirement 22: Timesheet approvals and billable controls align with projects.
- Time entries tied to projects shall support draft/submitted/approved states, billable vs non-billable flags, role-based approval chains, and export for payroll/billing with audit trails.
#### Scenario: Approve billable time for billing export
- Given Priya submits 6 billable hours on “Website Revamp”
- When a project approver reviews and approves the entry
- Then the timesheet status updates to approved, the billable hours roll up to the project, and the entry is included in the next payroll/billing export with audit info

#### Requirement 23: Reporting and exports provide performance and audit visibility.
- Users shall generate project performance reports (timeline adherence, budget/utilization, task/milestone completion, at-risk flags) and export data with activity/audit logs for compliance.
#### Scenario: Export a compliance-ready project report
- Given Casey needs a quarterly report for “Website Revamp”
- When they run a performance report with activity logs
- Then the export includes schedule/budget performance, completion metrics, risk/at-risk indicators, and an audit trail of key changes with users/timestamps

#### Requirement 24: Approval chains and escalations govern sensitive changes.
- Projects shall support multi-step approval chains (e.g., budget changes, time/timesheet approvals, scope changes) with role-based approvers, SLAs, escalation on overdue approvals, and full audit trails.
#### Scenario: Budget change with multi-step approval
- Given “Website Revamp” requires Finance then Director approval for budget increases
- When a PM requests a $20k increase
- Then Finance receives the request, approves it, the Director receives the escalated approval, and upon approval the budget updates with timestamps, approvers, and SLA compliance recorded in the audit log

#### Requirement 25: Exports support multiple formats with policy-aware redaction.
- Project data (including tasks, milestones, time entries, activity logs) shall export to CSV, XLSX, and PDF with configurable date/timezone handling, column selection, and policy-aware redaction for sensitive fields.
#### Scenario: Export redacted project data
- Given Casey needs to share a status pack externally
- When they export to PDF with redaction enabled for sensitive fields (e.g., rates, PII) and set timezone to UTC
- Then the PDF omits redacted columns, applies the selected timezone formatting, includes required metadata (filters, generated-at), and is logged in the audit trail

#### Requirement 26: Chatter-style communication stays contextual to projects and tasks.
- Projects and tasks shall have linked chat channels with message history, @mentions, file attachments, and the ability to spawn tasks from messages while keeping conversations tied to the originating record.
#### Scenario: Discuss and create a task from a channel
- Given a project channel is active for “Website Revamp”
- When Jordan shares a file, @mentions Casey about a blocker, and converts that message into a follow-up task
- Then the channel retains the file and threaded messages, the new task is linked to the project and channel context, and notifications go to mentioned users without losing record linkage

#### Requirement 27: Approval SLAs and escalations are configurable by change type.
- Approval workflows shall allow per-change-type SLA definitions (e.g., budget, scope, time/timesheet), escalation rules (next-level approver, manager), and reminders before breach, with visibility in dashboards.
#### Scenario: SLA-driven escalation on a scope change
- Given a scope change requires approval within 2 business days with escalation to the director if overdue
- When an approver does not act within the SLA
- Then the request escalates to the director, reminders are sent, and the dashboard shows the breached SLA and current approver chain

#### Requirement 28: Export templates control fields, redaction, and formats.
- Admins shall define export templates (per project type or audience) specifying columns, redaction rules (e.g., PII, rates), formats (CSV/XLSX/PDF), and timezone/locale so exports stay compliant and consistent.
#### Scenario: Use a sanitized client-facing export template
- Given a “Client Status” export template hides rates/PII, outputs PDF, and sets timezone to UTC
- When a PM exports “Website Revamp” using that template
- Then the PDF contains only allowed columns, applies redaction, uses UTC formatting, and logs the template used in the audit trail

#### Requirement 29: Default SLAs honor business hours and calendars.
- Approval SLAs shall support defaults per change type and project category, applying business hours and holiday calendars so timers and reminders respect working time and clearly show remaining time to breach.
#### Scenario: SLA countdown within business hours
- Given scope-change approvals use an 8-business-hour SLA with the US holiday calendar
- When a request is submitted Friday at 4 p.m.
- Then the SLA timer pauses outside business hours/holidays, the dashboard shows remaining business hours to breach, and reminders schedule accordingly

#### Requirement 30: Export templates are versioned with field mappings and reasons.
- Export templates shall be versioned with explicit field mappings (including derived fields), redaction rules, approver/publisher, and change reasons to keep compliance evidence for each template revision.
#### Scenario: Publish a revised export template
- Given Casey updates the “Client Status” template to remove a field and add a derived “At Risk?” column
- When they publish the new version
- Then the version records mappings, redactions, change reason, publisher, and timestamp; exports reference the version used in the audit log
