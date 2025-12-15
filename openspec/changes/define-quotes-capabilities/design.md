# Design Notes

## Pricing & Catalog Integration
- Quote line items resolve pricing from the product catalog and applicable price books before applying user-entered overrides; bundles reference a parent product ID so bundle totals stay traceable to catalog items.
- Group pricing is derived from account attributes (tier, industry, region) and should be applied before manual discounts to keep approvals predictable and auditable.
- Line-level discounts remain isolated from catalog price resolution so that re-pricing a quote version can recompute based on updated catalog data without losing user-entered adjustments.

## Totals, Taxes, Currency, and Shipping
- Currency selection sets the quote’s transactional currency and locks the FX rate snapshot used to convert catalog base prices and compute totals; taxes and shipping inherit this currency.
- Tax calculation uses jurisdiction rules derived from shipping/billing address, while shipping charges pull from carrier/method lookups; both feed the total calculation chain (subtotal → discounts → taxes → shipping → grand total).
- Terms and conditions templates are stored centrally and can be overridden per quote; the resolved text must travel with the version history to prevent retroactive changes.

## Workflow, Approvals, and Versioning
- Quote statuses (Draft → Pending Approval → Approved/Rejected → Expired/Revised) are driven by workflows that also stamp approver identity, timestamps, and comments into the audit log.
- Versioning creates immutable revisions linked to a master quote record; only the active revision is editable, and prior revisions remain read-only but available for PDF regeneration.
- PDF generation pulls the selected template, renders the active version data, and attaches the result to the quote record with metadata (template name, generated-by, timestamp) for auditing.
