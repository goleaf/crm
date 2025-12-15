# Proposal: define-quotes-capabilities

## Change ID
- `define-quotes-capabilities`

## Summary
- Translate the SuiteCRM Quotes feature set into explicit requirements for composing, pricing, approving, and delivering quotes within the CRM.
- Clarify how quotes consume the product catalog, pricing models, currency handling, and PDF templates so downstream modules (opportunities, orders) stay aligned.

## Capabilities
- `quote-composition`: Build quotes with account/contact context, validity windows, versioning, and status tracking.
- `quote-pricing`: Add catalog items, bundles, group pricing, discounts, taxes, shipping, currency, and terms into a single total.
- `quote-approvals`: Govern approval workflows, revisions, and PDF/template generation with auditable outcomes.

## Notes
- `openspec` CLI tooling is not available in this environment; validation steps must be performed manually.
