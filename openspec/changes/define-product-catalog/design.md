# Design Notes

## Catalog Structure
- Product records anchor on unique identifiers (SKU/part number) with classification facets (type, category tree, manufacturer) and lifecycle/state flags; these attributes should sit in the master catalog table so every surface (forms, imports, APIs) can query a single source of truth.
- Descriptions, images, and attachments ride alongside the product and should support multiple assets while keeping a primary/featured image flag for storefronts and quote previews.
- Custom fields extend the product surface without schema churn, ensuring exports, imports, and list/detail views can include tenant-specific attributes.

## Pricing & Inventory
- Cost, list, and discount pricing rules should share a pricing service that applies effective dates, currencies, and quantity tiers, preventing divergent calculations between quotes, bundles, and cross-sell suggestions.
- Inventory tracking ties availability to status/lifecycle; Active or “Released” products can decrement stock, while Draft/Inactive/Retired items are blocked from fulfillment and recommendations.

## Relationships
- Bundles reference component products with quantities and optionally override pricing, so quotes and inventory decrements can fan out to underlying SKUs.
- Cross-sell and upsell relationships should be stored as directional links with optional recommendation context (e.g., trigger stage, position) to populate suggestive panels without hardcoding logic into UI layers.
