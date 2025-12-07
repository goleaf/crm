# Proposal: define-purchase-orders

## Change ID
- `define-purchase-orders`

## Summary
- Capture purchase order management so teams can create POs, manage suppliers, run approvals, receive goods, track costs, and connect procurement to sales demand.

## Capabilities
- `purchase-order-core`: Create and manage purchase orders with numbering, statuses, vendor selection, line items, delivery terms, and lifecycle controls through issue/close/cancel.
- `vendor-management`: Maintain supplier/vendor records with contacts, payment/shipping terms, performance data, and reuse them when issuing purchase orders.
- `purchase-order-approvals`: Route purchase orders through approval workflows with audit trails before issuing or material changes.
- `receipts-and-fulfillment`: Record receipts (including partials/returns) against purchase orders and update fulfillment status per line.
- `purchase-order-costing`: Track committed vs received costs, taxes, freight/fees, and accruals to keep procurement spending accurate.
- `purchase-order-sales-alignment`: Link purchase orders to sales orders or opportunities to support drop-ship/back-to-back fulfillment and demand-driven purchasing.

## Notes
- The `openspec` CLI is unavailable in this environment; specs and validation are authored manually.
