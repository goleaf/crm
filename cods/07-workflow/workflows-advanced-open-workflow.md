# Workflows (Advanced Open Workflow)

Status: Partial

Coverage:
- Done: 4 subfeatures (workflow definitions with triggers/steps schema, SLA/escalation config storage, executions with audit logs, approvals)
- Partial: 6 (condition builders, scheduled/time-based triggers, UI for creation/editing, workflow testing, logs UI, repeated run controls)
- Missing: automated action execution layer

What exists
- Data model for process definitions including steps, business rules, event triggers, SLA config, escalation rules, and versioning (`app/Models/ProcessDefinition.php`, `database/migrations/2025_12_06_070000_create_process_definitions_table.php`).
- Execution records with step tracking, audit logs, escalations, and analytics (`app/Models/ProcessExecution.php`, `ProcessExecutionStep.php`, `ProcessAuditLog.php`, `ProcessEscalation.php`, `ProcessAnalytic.php`).
- Approval records tied to executions (`app/Models/ProcessApproval.php`) and status enums for process lifecycle.

Gaps
- No UI for building workflows or defining conditions; steps/rules are JSON payloads only.
- No scheduler/trigger engine to run workflows on create/edit/time-based events.
- No workflow testing harness or execution log viewer beyond raw tables.

Source: docs/suitecrm-features.md (Workflows (Advanced Open Workflow))
