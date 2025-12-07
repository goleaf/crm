# Design Notes

## Data Surfaces
- Contract records consolidate type, parties, start/end/renewal dates, value (with currency), auto-renew options, terms/conditions, and SLA commitments; selecting a contract template pre-populates these fields and stores the template reference for auditability.
- Renewal date and auto-renew metadata live with the contract so schedulers can read them without joining document tables; status changes remain driven by lifecycle services to keep validation consistent across creation/edit, imports, or API calls.

## Lifecycle & Controls
- Approval workflow gates transitions from Draft to Active and is pluggable for single or multi-approver chains; approvals write audit events and drive status updates consumed by UI buttons and notifications.
- A scheduler monitors end and renewal dates to set Expiring/Expired statuses and dispatch notifications; the same service extends terms for auto-renewed contracts or spawns a renewal record when auto-renew is disabled.
- Amendments are stored as child records referencing the parent contract and snapshotting terms/value/SLA deltas so history, reports, and compliance checks remain traceable.

## Documents & Relationships
- A contract template library stores reusable clauses, default SLA values, auto-renew settings, and document shells; creation flows resolve the template to pre-fill fields and attach the rendered template to the contract.
- Executed contracts and supporting files are stored as versioned documents linked to the contract, capturing upload metadata (uploader, version, execution date) for audit and retrieval.
- Relationship tracking links contracts to accounts, opportunities, cases, and peer contracts; subpanels and list views consume these relations so downstream modules (billing, support) can enforce the correct entitlements.
