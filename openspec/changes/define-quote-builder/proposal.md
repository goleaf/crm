# Proposal: define-quote-builder

## Change ID
- `define-quote-builder`

## Summary
- Capture a Quote Builder capability that covers quote authoring, pricing, delivery, acceptance, and downstream conversion into orders.
- Address professional quote composition, catalog-driven line items with taxes/discounts, templates/PDFs, email delivery with accept/reject, expiration handling, and order conversion.

## Capabilities
- `quote-authoring`: Compose quotes with account/contact context, validity windows, catalog and custom lines, taxes, discounts, terms, templates, and PDF output.
- `quote-acceptance`: Send quotes via email with tracked links, allow customers to accept or reject, enforce expiration rules, and log decisions.
- `quote-conversion`: Version and revise quotes while preserving history, and convert accepted quotes into orders without losing audit trails.

## Notes
- OpenSpec CLI tooling is unavailable in this environment; requirements are documented manually for later validation.
