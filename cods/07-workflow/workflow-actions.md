# Workflow Actions

Status: Partial

Coverage:
- Done: 3 subfeatures (action/step tracking, approvals/escalations per step, audit logs with input/output payloads)
- Partial: 6 (action types for record creation/modification, email/task/call scheduling actions, status/assignment updates, relationship creation, record locking)
- Missing: 0 additional data structures

Details
- Execution steps capture config, input/output data, assignment, due dates, and error messages with audit logs and escalation/approval links (`app/Models/ProcessExecutionStep.php`, `ProcessAuditLog.php`, `ProcessEscalation.php`, `ProcessApproval.php`).
- Step status lifecycle tracked via enums; approvals/escalations are stored per step.

Gaps
- No action engine to perform record updates/notifications/tasks/calls/meetings.
- No UI to configure actions or run them on triggers; step execution is manual/placeholder.
- No record locking or validation hooks wired to workflow outcomes.

Source: docs/suitecrm-features.md (Workflow Actions)
