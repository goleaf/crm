# Design Notes

## Lifecycle and Acceptance
- Quotes track statuses across Draft, Sent, Accepted, Rejected, Expired, and Revised; acceptance links expose a limited public surface that records actor identity, timestamp, and notes before transitioning status.
- Expiration enforcement applies to both internal edits and public acceptance endpoints; scheduled jobs can auto-expire records and notify owners while leaving a path to spawn a new revision.
- Versioning copies line items, totals, terms, and template choice into a new revision while locking prior revisions for audit and PDF regeneration.

## Authoring and Pricing
- Catalog-backed line items resolve SKU, description, unit price, tax category, and UOM; custom lines allow bespoke entries while maintaining consistent totals.
- Taxes derive from the line’s tax category and the quote’s ship-to jurisdiction; discounts apply at the line first and header second so totals stay auditable.
- Terms and template selection are stored on each revision so PDFs and email content reflect the exact state that was shared externally.

## Templates, PDFs, and Communications
- A template library defines layout, branding, and default terms; PDF generation uses the selected template, snapshots the merged content, and attaches the artifact to the quote.
- Email sending picks a communication template, attaches the latest PDF, embeds acceptance/rejection links, and logs delivery/opens for compliance and follow-up cues.

## Conversion to Order
- Converting an accepted quote creates an order record linked to the source quote/revision, copying line items, discounts, taxes, currency, and terms while marking the quote as converted.
- The conversion flow preserves references for audit (who converted, when, and from which revision) so downstream fulfilment and invoicing can trace back to the agreed quote.
