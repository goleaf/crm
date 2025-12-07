# Process Management

Status: Partial

Coverage:
- Done: 5 subfeatures (process definitions with versioning/template support, execution tracking, approvals/escalations, analytics placeholders, audit logs)
- Partial: 8 (automation engine, event-driven triggers, SLA enforcement, optimization/monitoring dashboards, templates UX, process documentation)
- Missing: 0 additional schema pieces

Details
- Process definitions capture steps, business rules, triggers, SLA config, escalation rules, metadata, documentation, and templates (`app/Models/ProcessDefinition.php`).
- Executions, steps, approvals, escalations, audit logs, and analytics models persist runtime data (`ProcessExecution.php`, `ProcessExecutionStep.php`, `ProcessApproval.php`, `ProcessEscalation.php`, `ProcessAuditLog.php`, `ProcessAnalytic.php`).
- Versioning and template references exist on definitions; status enums track lifecycle.

Gaps
- No process designer UI, deployment flow, or monitoring dashboards.
- No engine to execute steps, enforce SLAs, send approvals, or evaluate business rules.
- No process optimization metrics beyond raw analytics table.

Source: docs/suitecrm-features.md (Process Management)
