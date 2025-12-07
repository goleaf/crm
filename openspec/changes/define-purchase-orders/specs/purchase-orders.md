# Purchase Orders

## ADDED Requirements

### Purchase order creation and lifecycle control
- The system shall let users create purchase orders with vendor selection, ship-to/bill-to details, currency, expected delivery dates, line items (product/description, quantity, unit cost, tax/fee), delivery/payment terms, and auto-assigned PO numbers; statuses include Draft, Pending Approval, Approved, Issued, Partially Received, Received, Closed, and Cancelled with history on each transition.
#### Scenario: Create and issue a purchase order
- Given a buyer selects vendor "ACME Supply", enters a ship-to warehouse, expected_delivery_date = next Friday, and 3 line items with quantities and unit costs
- When they save and click Issue after validation passes
- Then the purchase order receives a sequential number, status moves to `Issued`, subtotal/tax/total amounts are calculated, and the activity history records creation and issuance.

### Supplier and vendor management
- The system shall maintain supplier/vendor profiles with contact points, tax IDs, payment/shipping terms, preferred currency, status (Active/On Hold), and performance/rating fields that can be reused when issuing purchase orders.
#### Scenario: Reuse vendor defaults on a PO
- Given vendor "Bright Logistics" has payment_terms = Net 30, ship_method = Ground, and status = Active
- When a user creates a purchase order and selects that vendor
- Then the PO pre-fills payment_terms = Net 30 and ship_method = Ground, and the vendor link is stored so reports and compliance checks can reference the vendor profile.

### Purchase order approval workflows
- Purchase orders shall route through approval workflows based on thresholds/rules (e.g., amount, category, vendor status) and record approver actions, comments, timestamps, and outcomes before issuance or material changes.
#### Scenario: Approve a high-value PO
- Given a purchase order total is $25,000 and policy requires director approval above $20,000
- When the requester submits for approval
- Then the PO status changes to `Pending Approval`, the director receives an approval task, and upon approving the PO moves to `Approved` with the approver, timestamp, and comment logged.

### Receipt tracking and fulfillment updates
- The system shall record receipts against purchase orders at the line level, supporting partial receipts, over/short handling, returns, and attachments (packing slips) while updating received quantities and fulfillment status.
#### Scenario: Record a partial receipt
- Given a purchase order has a line for 10 widgets and is in status `Issued`
- When warehouse staff receive 6 widgets and log a receipt with qty_received = 6 and attach the packing slip
- Then the line shows received_quantity = 6/10, the PO status becomes `Partially Received`, and the receipt entry is stored with receiver and timestamp.

### Cost tracking and accruals
- Purchase orders shall track committed costs (subtotal, tax, freight/fees), received/actual costs, and variances, updating totals as receipts post and enabling accruals for unbilled receipts.
#### Scenario: Update costs after receipt
- Given a purchase order totals $12,000 with $300 freight and no invoices yet
- When a receipt for $6,000 of goods is posted and freight is adjusted to $350
- Then committed totals remain $12,350, received_cost records $6,000 goods + $350 freight, and outstanding_commitment reflects the remaining amount.

### Link purchase orders to sales demand
- Purchase orders shall link to sales orders or opportunities (at header and line level) to support drop-ship/back-to-back fulfillment and report coverage of sales demand by open POs.
#### Scenario: Tie a PO line to a sales order line
- Given a sales order line requests 5 routers for delivery next week
- When a buyer creates a purchase order line for 5 routers and links it to that sales order line
- Then the PO stores the sales_order_line_id, the sales order shows it is covered by the PO with expected receipt date, and fulfillment status can reflect when the PO line is received.
