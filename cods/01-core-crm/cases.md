# Cases

Status: Implemented

Coverage:
- Done: 10 subfeatures (statuses, priorities, types, queue assignment, SLA timers, assignee/team routing, portal/KB/thread references, email message ids)
- Partial: 5 (escalation automation, email-to-case ingestion, portal customer view, knowledge base linkage UI, case queues UI depth)
- Missing: 0 at data-model level

What works now
- Case records with status (New/Assigned/Closed/Pending), priority (P1–P4), type, channel, and queue fields plus creation source (`app/Filament/Resources/SupportCaseResource.php`, `app/Models/SupportCase.php`).
- SLA tracking fields (`sla_due_at`, `first_response_at`, `resolved_at`) and overdue filter; escalated timestamp captured.
- Assignment to individual users and teams with filters and badges; supports company/contact linkage.
- Threading/portal placeholders (`thread_reference`, `customer_portal_url`) and knowledge base reference string for future deep links.
- Email-to-case placeholders via `email_message_id`; export support and soft-delete handling.
- Custom fields supported via Relaticle builder and relations to tasks/notes for follow-up.

Gaps / partials
- Escalation rules, auto-assignment, and SLA timers are not automated—fields are manual today.
- Email-to-case ingestion, threading, and queue routing are not wired; message id is stored for future correlation.
- Customer portal view is not built; portal URL is stored only.
- Knowledge base integration is a reference string without enforced relation/preview.

Source: docs/suitecrm-features.md (Cases)
