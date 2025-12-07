# Deal Management Requirements

## ADDED Requirements

#### Requirement 1: Customizable deal pipeline with governed stage movement
- Scenario: An admin defines a stage set with colors, orders, and closed/won/lost flags; the pipeline board renders columns in that order and uses the stage option IDs as column keys. When a rep drags a deal from Qualification to Proposal, the system updates the stage field, logs the actor/timestamp, seeds the probability from the new stage default, and prevents moves into Closed Won/Closed Lost unless the deal is marked ready to close.

#### Requirement 2: Track deal value, probability, and expected close for weighted pipeline totals
- Scenario: A rep enters amount = $50,000 USD, expected_close_at = 2025-11-15, and probability = 60%; the deal stores currency/amount separately from probability, computes a weighted amount of $30,000 for pipeline rollups, and shows aging warnings if the close date passes without closure. Updating the stage to Negotiation auto-seeds probability to 75% unless the rep overrides it, and all changes are captured on the deal.

#### Requirement 3: Ownership, assignment strategies, and collaborator tracking
- Scenario: Creating a deal requires a primary owner; the rep can add collaborators and select an assignment strategy (Manual/Round Robin/Territory). The system records owner, collaborators, strategy, and timestamp in an assignment log, updates team permissions accordingly, and preserves prior assignments for reporting on time-in-owner and handoffs.

#### Requirement 4: Conversion to orders with lineage and data carryover
- Scenario: When a deal in stage Closed Won is converted, the system creates an order (or invoice when orders are unavailable) linked to the same company/contact, copies amount/currency and close date as the fulfillment/expected delivery date, and stores converted_order_id on the deal plus converted_from_deal_id on the order. The deal’s status is set to closed-won, and the conversion appears in the activity timeline with the actor and timestamp.

#### Requirement 5: Win/loss analysis and activity history
- Scenario: Closing a deal to Closed Lost requires selecting a loss reason, optional competitor, and outcome notes; closing to Closed Won requires a win reason and next steps. The deal stores closed_by/closed_at, outcome fields, and stage, and writes timeline entries for stage changes, owner/assignment changes, probability/value edits, conversions, and linked activities (tasks/meetings/notes/emails) so users can review a chronological activity history filtered by event type.
