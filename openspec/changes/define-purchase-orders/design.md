# Design Notes

## Data & Entities
- Purchase orders store vendor linkages, PO number/sequence, currency, payment/shipping terms, ship-to/bill-to addresses, expected dates, statuses, and line items with quantities, unit costs, taxes/fees, and GL/category references; attachments capture PO documents and vendor quotes.
- Vendor profiles retain payment/shipping terms, tax identifiers, contacts, compliance docs, performance/rating data, and preferred currency so issuing a PO can reuse defaults while keeping auditability of vendor history.

## Lifecycle & Controls
- Purchase order states progress Draft → Pending Approval → Approved → Issued → Partially Received → Received → Closed/Cancelled; edits to issued/approved POs can trigger re-approval and always write audit entries.
- Approvals reuse the existing process/approval machinery with amount/category/vendor-based rules; issuance and major revisions are blocked until required approvals complete.
- Receipts generate receipt records per PO line, storing received/returned quantities, receiver, timestamps, and attachments (packing slips), and update line-level and PO-level fulfillment states.

## Costing & Accounting
- POs maintain subtotal, tax, freight/fees, committed_total, received_cost, and outstanding_commitment; receipts post accruals and variances while unit cost or charge changes after approval can trigger re-approval and revision logs.
- Cost updates and status changes emit activity/timeline events to keep procurement, finance, and audit stakeholders aligned.

## Sales & Fulfillment Alignment
- PO headers and lines can reference sales orders or opportunity lines to support drop-ship or back-to-back sourcing; fulfillment and receipt updates can surface on linked sales records to reflect supply coverage and ETAs.
- Key transitions (creation, approval, issuance, receipt, closure/cancel) broadcast events for synchronization with inventory, billing/AP, and sales order systems.
