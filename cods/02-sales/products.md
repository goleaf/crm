# Products

Status: Partial

Summary:
- Products capture catalog data (SKU/part number, manufacturer, type, status, lifecycle), pricing windows, cost/list prices, and bundle flags with media and custom fields still supported.
- Categories now support parent/child hierarchy with slug generation and breadcrumb display.
- Pricing tiers and discount rules (percent/fixed, quantity thresholds, date windows, optional customer/category scoping) plus cross-sell/upsell/bundle/dependency relationships are managed from the Product resource.
- Filament forms and relation managers expose the new fields; pricing helper resolves tiered pricing and applies the best active discount.

Gaps:
- Quote/Order price calculations are not yet wired to the new pricing engine; no automated recommendations/reservations when adding related products.
- Promotional scheduling is stored but not surfaced in storefront/quote flows; no import/export for these structures yet.

Source: docs/suitecrm-features.md (Products)
