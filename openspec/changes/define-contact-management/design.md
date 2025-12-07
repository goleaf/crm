# Design Notes

## Data relationships
- Contacts (people) link to organizations for account context and to other contacts for reporting chains; both contacts and organizations support custom fields to extend schema without code changes.
- Organizations hold parent/child relationships to express hierarchies, and contacts inherit visibility to that hierarchy for navigation and rollups.

## Activity timelines
- Contact timelines capture atomic activities (calls, meetings, tasks, emails, notes) tied directly to the person.
- Organization timelines aggregate activities logged against the organization and its linked contacts so account teams see a complete history.
- Customer history surfaces the union of contact and organization activities plus commercial records (opportunities, invoices, cases) for a single customer view.

## Segmentation and roles
- Roles (Decision Maker, Technical Contact, Billing Contact) live on contact-organization links to clarify responsibilities.
- Tags and segments attach to contacts and customers for targeting, exports, and automation triggers; segment membership should be filterable in list views.

## Metrics and lifecycle
- Customer value metrics (LTV, ARR, average deal size) derive from opportunities and invoices linked to the customer’s organization and contacts, recalculating when commercial records change.
- Lifecycle state transitions are logged with actor/time and can be driven by workflow rules or manual updates to keep history auditable.
