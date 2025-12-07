# Tasks

Status: Implemented

Coverage:
- Done: 14 subfeatures (creation/assignment, priorities/statuses, due/reminders, relationships, subtasks, dependencies, checklists, comments, time tracking, delegation hooks, recurrence, categories, filters)
- Partial: 2 (task hierarchy UI beyond list view, automation for dependencies)
- Missing: 0 functional areas from the SuiteCRM list

What works now
- Task CRUD with multi-assignee support, sortable ordering, and soft deletes (`app/Filament/Resources/TaskResource.php`, `app/Models/Task.php`).
- Status and priority handled via custom fields; filtering and grouping by these fields in the table.
- Subtasks via parent/child relation; dependencies tracked through `task_dependencies` pivot with filters for blocked tasks.
- Categories (many-to-many), checklist items, comments, time entries, reminders, recurrence, and delegations (`app/Models/TaskCategory.php`, `TaskChecklistItem.php`, `TaskComment.php`, `TaskTimeEntry.php`, `TaskReminder.php`, `TaskRecurrence.php`, `TaskDelegation.php`).
- Task relationships to companies/leads/people/opportunities/cases via morphs on taskable entities.
- Filters for assignees, categories, creation source, blocked tasks, and trash; notifications on assignment updates.

Gaps / partials
- No dedicated hierarchy/board/kanban view; dependencies and subtasks are managed via lists only.
- Dependency enforcement and recurrence notifications rely on manual workflows—no automated rescheduling or critical path logic.

Source: docs/suitecrm-features.md (Tasks)
