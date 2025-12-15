# Design Notes

## Campaign Data & States
- Campaign records must store channel/type (email, telesales, radio, print, other) and status (Draft, Planned, Scheduled, In Progress, Completed, Cancelled) so workflows can gate which actions are available (e.g., only Scheduled/In Progress can send).
- Budget, expected revenue, and actual cost fields feed an ROI calculation (`(expected or actual revenue - actual cost) / actual cost`) that updates as costs land; ROI snapshots should be stored per campaign revision to keep historical accuracy.
- Status transitions should be captured with timestamps and actors for reporting and to prevent accidental re-sends of completed campaigns.

## Targeting & Content
- Campaigns attach one or more target lists (default, test, suppression) to drive recipient resolution for email sends and call lists for telesales; suppression lists must be honored during scheduling and test sends alike.
- Email templates need an HTML-first editor with preview, merge-field support, and storage of inline images/assets; non-email campaigns can omit templates but still track notes and call scripts.
- The campaign wizard should enforce prerequisite steps (type/status, budget, target lists, template selection) before scheduling to reduce misconfigured launches.

## Scheduling & Delivery
- Scheduling requires timezone capture and conversion so that a user’s selected send time executes correctly for the intended audience; queued jobs should store the canonical UTC time alongside the chosen timezone.
- Test sends bypass the main schedule but use the same rendering pipeline and target list resolution (test list only) to validate content, links, and personalization before the full send.
- Non-email campaigns do not invoke the email sending engine but still use schedules to plan start/end dates and drive status changes for reporting.
