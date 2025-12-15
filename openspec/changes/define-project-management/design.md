# Design Notes

## Real-time updates and data handling
- Use subscription/broadcast channels for project detail changes (metadata, team members, tags, status) so dashboards and lists refresh without manual reloads.
- Apply optimistic updates with versioning or ETag checks to prevent overwrites; reconcile conflicts by surfacing the newer state and preserving user edits where possible.
- Batch UI updates (e.g., debounce text inputs, coalesce tag changes) to keep interaction smooth while keeping the backend authoritative for ordering and validation.

## Lifecycle, hierarchy, and dashboards
- Support parent/child project relationships and status transitions with audit trails so lineage and history remain visible after archive.
- Keep dashboards powered by shared aggregates (status, schedule, budget burn, assignments, key milestones/tasks) so list, detail, and reporting views stay consistent without duplicating calculations; reuse the same aggregate layer for exports and compliance audits.

## Access controls and ownership
- Projects should carry explicit owner/manager roles and map team roles to permission sets (view, edit, manage membership, delete/restore) enforced across UI, API, and realtime channels.
- Sensitive actions (role changes, delete/restore) should be audited and constrained to authorized roles, with scoped visibility for external/guest collaborators.

## Data model and customization
- Store core fields (name, status, timeline dates, priority, tags, summary) alongside an extensible custom field model so admins can add fields without code changes; expose new fields in forms, detail views, filters, and exports.
- Keep tags normalized and indexed for search/filter performance; support reusable tag sets or categories so teams maintain consistent taxonomy.

## Deletion and recovery
- Prefer soft deletion with retention windows and a recoverable “trash” view; ensure restoring projects re-links dependent records (tasks, tags, team assignments) and prevents purge while children remain protected.
- Permanent deletion should enforce checks for dependent data and be restricted to elevated roles to avoid accidental data loss.

## Integrations and rollups
- Link project assignments to employee profiles to surface workload/availability and keep performance reporting consistent across tasks, projects, and timesheets.
- Ensure time/expense rollups (billable/non-billable) and milestone/task progress feed the same project aggregates used by dashboards and reports, preventing drift between operational views and exports.
- Incorporate working hours/holidays/time zones in scheduling logic so dates, reminders, and alerts respect availability, and tie skill/job-type metadata into assignment UIs for better staffing decisions and utilization reporting.
- Keep workflow/automation configurable (templates, auto-assign, approval steps) with audited overrides; ensure timeline/Gantt interactions respect dependencies/validations and propagate notifications; make reporting/exports reuse the audit log layer for compliance.
- Model approval chains with SLA/escalation rules and role-based approvers; centralize audit logging for approvals and exports; support export policy controls (redaction, column selection, timezone handling) and keep chat/message channels linked to project/task records with audit visibility.
- Manage export templates by audience/project type with enforced redaction/format/locale defaults and versioning (mappings, reasons, publisher); surface SLA status/escalations on dashboards so pending approvals are visible and auditable, and honor business hours/holiday calendars in SLA calculations/reminders.

## Sorting and prioritization performance
- Provide indexed fields for priority, due/start dates, manager, status, and tag references to keep list sorting and filtering fast.
- Allow saved/smart views to reuse common sort/filter combinations so users can jump to prioritized work without recomposing queries.
