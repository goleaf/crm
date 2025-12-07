# Quote Builder

## ADDED Requirements

### Professional quote creation
- The system shall allow creating a quote with account, primary contact, owner, currency, billing/shipping addresses, and a validity window while defaulting status to `Draft`.
#### Scenario: Start a draft quote with context
- Given a seller opens the New Quote form and selects an account, contact, currency, billing, shipping, and sets an expiration date 14 days out
- When they save the quote
- Then the quote is stored in `Draft` status with those relationships and dates captured and ready for line item entry

### Line item management with products
- Quotes shall support attaching catalog products as line items with quantity, UOM, unit price, tax category, and editable descriptions while also allowing ad-hoc custom lines.
#### Scenario: Add catalog and custom lines
- Given a draft quote
- When the user adds a catalog product “Widget Pro” (auto-filling SKU, description, price, tax category) and adds a custom service line with quantity 5
- Then both lines appear in the quote with correct quantities, editable descriptions, and pricing pulled from the catalog for the product line

### Tax calculations
- Quotes shall calculate tax per line using the line’s tax category and the quote’s ship-to jurisdiction, showing the tax total in quote totals.
#### Scenario: Calculate tax by ship-to region
- Given a quote with a taxable item and ship-to address in a region with 8% tax and another non-taxable service line
- When the quote is saved
- Then tax is computed at 8% for the taxable line only, the non-taxable line remains untaxed, and the tax total appears separately in the summary

### Discount management
- The system shall support line-level and header-level discounts (fixed or percent), applying line discounts before header discounts and showing both in totals.
#### Scenario: Stack line and header discounts
- Given a quote with two lines totaling $1,000
- When the user applies a 10% discount to line 1 and a $50 header discount
- Then totals show the line discount first, the header discount second, and the resulting subtotal reflects both adjustments

### Quote templates
- Users shall choose a quote template that sets layout, branding, and default terms while persisting the chosen template on the quote revision.
#### Scenario: Apply a quote template
- Given a draft quote
- When the seller selects the “Standard Brand” template
- Then the quote stores that template key, applies the template’s default sections/terms, and uses it for downstream PDF generation

### Send quotes via email
- Users shall be able to send a quote via email to selected recipients, attach the latest PDF, and record send metadata (sender, timestamp, recipients, message).
#### Scenario: Email a quote with PDF attachment
- Given a draft quote with a generated PDF
- When the seller clicks “Send Quote”, enters a subject/body, and chooses the primary contact as recipient
- Then the system emails the PDF, records send details on the quote, and sets the status to `Sent`

### Accept/Reject functionality
- Customers shall be able to accept or reject a quote via a secure public link, providing optional notes, which transition the quote status and log the decision.
#### Scenario: Customer accepts via public link
- Given a sent quote with a public acceptance link
- When the customer opens the link, clicks Accept, and adds a note
- Then the quote status changes to `Accepted`, the note and timestamp are logged, and the seller is notified

### Quote versioning
- The system shall support creating a new revision from an existing quote, incrementing the version number, copying content, and locking prior revisions for edits.
#### Scenario: Revise an approved quote
- Given an approved quote version 1.0
- When the seller creates revision 2.0 to adjust quantities
- Then version 2.0 is editable with copied lines/terms, version 1.0 stays read-only, and the version history shows both entries

### Quote to order conversion
- Users shall convert an accepted quote into an order, copying line items, discounts, taxes, currency, and terms while linking the order back to the source quote/revision.
#### Scenario: Convert an accepted quote
- Given an accepted quote version 2.0
- When the seller clicks “Convert to Order”
- Then an order is created with the same lines, pricing, taxes, and terms, the order references quote version 2.0, and the quote is marked as converted

### Custom terms and conditions
- Quotes shall attach terms and conditions from a library with the ability to append custom text per revision, storing the resolved terms with the quote.
#### Scenario: Override default terms on a revision
- Given a draft quote with default “Net 30” terms
- When the seller adds a custom note about installation scope
- Then the stored terms for that revision include both the default and custom text, and subsequent PDFs/emails show the combined terms

### Quote expiration dates
- Each quote shall carry an expiration date that blocks acceptance after expiry and can automatically transition status to `Expired`.
#### Scenario: Auto-expire past validity
- Given a quote expiring today that is still `Sent`
- When the date passes without acceptance
- Then the system marks the quote as `Expired`, prevents acceptance, and notifies the owner

### PDF generation
- Users shall generate a PDF of the quote using the selected template, attaching the output to the quote with metadata (template, generated at, author).
#### Scenario: Generate a PDF
- Given a draft quote with lines and a selected template
- When the seller clicks “Generate PDF”
- Then the system produces a PDF with branding, pricing, and terms, attaches it to the quote, and records the template name and timestamp
