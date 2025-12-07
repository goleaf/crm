# Quotes Module

## ADDED Requirements

### Professional quote composition
- The system shall let users create a quote with account, primary contact, opportunity linkage, owner, validity window, and payment/shipping terms while defaulting status to `Draft`.
#### Scenario: Create a draft quote with context
- Given a sales rep opens the New Quote form
- When they select an account, primary contact, opportunity, owner, and set an expiration date 30 days out with default net-30 terms
- Then the quote saves in `Draft` status with those relationships captured and an empty line item list ready for pricing

### Product catalog integration
- Quotes shall allow searching the product catalog and attaching items with catalog-driven fields (name, SKU, unit price, tax category, UOM) while still permitting one-off custom lines.
#### Scenario: Add catalog items to a quote
- Given a draft quote
- When the user searches the catalog for “Widget Pro” and adds it as a line item
- Then the line auto-populates SKU, description, UOM, and catalog price, and the system records a catalog reference while keeping a “Custom Line” option available

### Line item management
- Users shall manage line items with quantity changes, inline description edits, reordering, duplication, and removal while preserving calculated totals.
#### Scenario: Reorder and edit line items
- Given a quote with three line items
- When the user increases quantity on line 2 to 5 units, reorders it to the top, and deletes line 3
- Then the line order updates, totals recalc with the new quantity, and the deleted line no longer appears in the quote or PDF output

### Pricing and discount management
- The system shall support line-level and header-level discounts (percentage or fixed), enforce approval thresholds, and keep a clear audit of applied adjustments before taxes and shipping.
#### Scenario: Apply stacked discounts
- Given a quote with two catalog items
- When the user applies a 10% discount on line 1 and a 5% overall discount at the quote level
- Then the subtotal reflects the line discount first, the header discount applies next, approval is required if thresholds are exceeded, and the calculated total shows both discounts separately

### Tax calculation
- Quotes shall calculate taxes using the configured tax category on each line and the applicable jurisdiction derived from shipping/billing address.
#### Scenario: Compute tax by jurisdiction
- Given a quote with a taxable item and a shipping address in a region with 8% tax
- When the user saves the quote
- Then tax is calculated at 8% on taxable lines, exempt lines stay untaxed, and the tax amount appears as a separate component in the totals

### Shipping cost calculation
- Users shall attach a shipping method/carrier that contributes a shipping charge to the total and mirrors the selected delivery terms.
#### Scenario: Add shipping to totals
- Given a draft quote with items totaling $1,000
- When the user selects “Ground - UPS” with a $25 charge
- Then the quote total increases by $25, the method is recorded for fulfillment, and taxes remain unchanged unless the jurisdiction taxes shipping

### Quote versioning and revisions
- Quotes shall support immutable revisions linked to a master quote, incrementing version numbers and preserving prior versions for audit and PDF regeneration.
#### Scenario: Create a new revision from an approved quote
- Given an approved quote version 1.0
- When the rep creates revision 2.0 to adjust quantities
- Then a new editable revision is created, version 1.0 remains read-only, and the change log shows the revision link and author

### Quote templates (PDF)
- Users shall generate PDFs from selectable templates that merge quote data, terms, and branding, attaching the output to the quote record.
#### Scenario: Generate a PDF from a template
- Given a draft quote with line items and terms
- When the user selects the “Standard” template and clicks Generate PDF
- Then a PDF is produced with branding, pricing, terms, and signature blocks, and the generated file is attached to the quote with template name and timestamp metadata

### Quote approval workflows
- Quotes shall support submission for approval with routing rules (e.g., discount threshold, currency, amount) and capture approver decisions, comments, and timestamps.
#### Scenario: Submit for approval and record decision
- Given a draft quote exceeding the auto-approval threshold
- When the rep submits for approval and the manager approves with a comment
- Then the status changes to `Approved`, the approver name and comment are logged, and further edits are blocked until a new revision is created

### Quote expiration dates
- Every quote shall carry an expiration date that blocks acceptance after expiry and drives automatic status changes.
#### Scenario: Auto-expire an unanswered quote
- Given a quote expiring today that is still in `Approved` status
- When the date passes without acceptance
- Then the system marks the quote as `Expired`, notifies the owner, and prevents acceptance until a new revision or extended expiration is set

### Quote status tracking
- The system shall track status transitions (Draft, Pending Approval, Approved, Rejected, Expired, Revised, Accepted) with history for reporting and filtering.
#### Scenario: View status history
- Given a quote that moved from Draft → Pending Approval → Approved → Accepted
- When a user views the quote history
- Then the status timeline shows each transition with actor and timestamp, and list views can filter quotes by current status

### Bundle pricing
- Quotes shall support bundle lines that aggregate child items, allowing both roll-up pricing and display of included components.
#### Scenario: Add a bundle with roll-up pricing
- Given a quote
- When the user selects the “Starter Bundle” that contains three SKUs and chooses roll-up pricing
- Then the parent bundle line shows the total bundle price, child items display as included with zero-priced lines, and totals use the bundle price without double-counting components

### Group pricing options
- The system shall apply group pricing based on account attributes (e.g., customer tier or negotiated price list) before manual discounts.
#### Scenario: Apply account-tier pricing
- Given an account tagged as “Enterprise Tier” with a preferred price list
- When a quote is created for that account and items are added
- Then the unit prices auto-resolve from the enterprise price list, and the pricing source is visible on each line

### Currency support
- Quotes shall allow selecting a transactional currency, snapshotting the FX rate, converting catalog/base prices, and displaying both transactional and base currency totals.
#### Scenario: Quote in a foreign currency
- Given base prices in USD and an FX rate USD→EUR of 0.9
- When the rep sets the quote currency to EUR and adds catalog items
- Then line prices appear in EUR using the 0.9 rate, totals display in EUR with a USD equivalency, and the FX snapshot is stored on the quote

### Terms and conditions
- Quotes shall attach terms and conditions from a template library with the ability to append custom notes, persisting the resolved text per revision and PDF.
#### Scenario: Override default terms
- Given default “Net 30 + Standard Warranty” terms
- When the rep adds a custom note about installation scope and saves the quote
- Then the terms on that revision include both the default text and the custom note, and the PDF reflects the combined terms verbatim
