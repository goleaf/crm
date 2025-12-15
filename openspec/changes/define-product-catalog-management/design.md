# Design Notes

## Catalog Data
- Product records should anchor on unique SKU/part numbers with manufacturer, status, and lifecycle visibility flags so downstream modules can enforce availability and present the right fields.
- Index product name, SKU, manufacturer, and category breadcrumbs to support fast lookup from quotes, opportunities, and invoices without duplicating catalog data per module.
- Store primary description plus optional marketing copy so both transactional lines and catalog browsing can render the appropriate detail.

## Categories
- Represent categories as a tree (parent_id with cached path/level) to make filtering and reporting efficient while allowing safe reparenting without rewriting product links.
- Maintain denormalized breadcrumbs for search facets and UI navigation; updates to a node should cascade breadcrumb recalculation to child nodes.

## Pricing & Discounts
- Persist list and cost prices with currency, plus tiered price tables keyed by quantity ranges; resolve pricing through a pricing service that evaluates tiers, promotions, and account/group pricing in priority order.
- Discounts should carry type (percent/fixed), scope (catalog-wide, product, category, account/group), and validity windows so eligibility can be computed consistently across quotes and invoices.
- Bundle pricing overrides should optionally replace component price rollups while still storing the components for inventory and reporting.

## Relationships & Recommendations
- Model cross-sell, upsell, bundle, and dependency links as directional relationships with optional priority/context metadata to drive suggestion ordering in UI surfaces.
- Enforce dependency validation at quote/invoice submission so required components are present; cross-sell/upsell suggestions should exclude inactive or retired products.
