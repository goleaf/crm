# Customer Management

## ADDED Requirements

### Customer profiles unify contact and organization context
- The system shall expose customer profiles that aggregate contact and organization information (people, account, owner, segments, lifecycle stage) into a single view so revenue and success teams share the same context.
#### Scenario: Open a unified customer profile
- Given a contact Jordan Smith linked to Acme Corp is marked as a customer with owner = Riley and segment = Enterprise
- When a user opens the customer profile
- Then the view shows Jordan and Acme details, owner/segment, lifecycle stage, and links to related opportunities, cases, and invoices

### Customer history consolidates interactions and transactions
- The system shall maintain a customer history that merges activities (calls, meetings, tasks, emails), support cases, opportunities, invoices, and notes across the customer’s contacts and organizations into a chronological timeline.
#### Scenario: Review customer history
- Given Acme Corp has two closed-won opportunities, one open case, and three logged calls
- When a success manager opens the customer history
- Then the timeline shows the opportunities with amounts/stages, the open case with status, and the calls with timestamps and subjects

### Customer segmentation organizes accounts for targeting
- The system shall support assigning customers to segments (e.g., Enterprise, SMB, Partner) via tags/fields or dynamic rules and allow filtering/exporting by segment for campaigns and reporting.
#### Scenario: Segment customers for a campaign
- Given segments Enterprise and Growth exist
- When a user assigns Acme Corp to Enterprise and filters the customer list by segment = Enterprise
- Then Acme appears in the filtered list and can be exported for the campaign audience

### Customer lifecycle tracking records stage changes
- The system shall track customer lifecycle stages (Prospect, Onboarding, Active, Expansion, Churn Risk, Churned) with effective dates and actors, updating stage when triggers fire or users change it, and capturing the change in history.
#### Scenario: Move a customer to Expansion
- Given Acme Corp is Active
- When an expansion opportunity is won and the stage is set to Expansion with a timestamp and actor
- Then the customer shows stage = Expansion, the change is logged with when/who, and downstream reports use the new stage

### Customer value metrics quantify revenue performance
- The system shall calculate customer value metrics such as lifetime value, annual recurring revenue, average deal size, and last invoice amount by aggregating related opportunities and invoices, exposing the metrics on the customer profile and in reports.
#### Scenario: Display lifetime value and ARR
- Given Acme Corp has $200k in closed-won opportunities and $150k in invoiced ARR
- When a user views the customer metrics
- Then lifetime value displays $200k, ARR displays $150k, and the metrics reflect the latest closed-won deals and active subscriptions/invoices
