# Design Notes

## Deal Data Model and Values
- Deals (opportunities) store core fields for name, company/contact links, primary owner, currency-coded amount, expected close date, probability, and forecast category. Amount and probability drive weighted pipeline totals and forecast rollups; expected close supports SLA/aging calculations.
- Stage options carry ordering, color, and default probability; when a stage has a default probability, changing to that stage seeds the probability while still allowing overrides. Closed-won/closed-lost flags on stages control outcome handling and automation hooks.

## Pipeline and Stage Customization
- Stage sets are configurable per workspace with name, color, order, default probability, and “closed” semantics; reordering stages updates the board column ordering and default sort. A Kanban/board view uses the stage custom field option IDs as column identifiers and stores card order in a rank column for smooth drag/drop.
- Movement between stages triggers validations (e.g., disallow moving back from closed states unless reopened), logs stage-change events with actor/timestamp, and optionally enforces required fields (amount, close date) before leaving certain stages.

## Ownership and Assignment
- Each deal has a primary owner, optional secondary assignees/collaborators, and team memberships; assignment changes are logged with strategy (manual/round-robin/territory) where applicable, actor, and timestamp. Permissions derive from owner/team while collaborators receive update notifications and activity feed visibility.
- Assignment metadata is separated from the deal record to preserve history and support reporting on reassignment frequency, time-in-owner, and SLA compliance.

## Conversion, Outcomes, and Activity History
- Converting a deal to an order (or invoice when orders are absent) creates the downstream record with linked company/contact, carries over amount, currency, items, and expected close as the order expected fulfillment date, and stores `converted_from_deal_id` on the order plus `converted_order_id` on the deal.
- Closing a deal to Won/Lost requires capturing outcome reason, competitor notes, and next steps; outcomes update status/stage, set closed_at/by, and write timeline entries. The activity timeline aggregates stage changes, assignments, conversions, win/loss notes, tasks/meetings/emails, and value/probability edits to deliver an audit-ready history.
