# Product Catalog Management

## ADDED Requirements

### Product catalog repository
- The system shall provide a centralized product catalog that stores names, descriptions, part numbers/SKUs, manufacturer, pricing, status, and visibility flags so products can be created once and reused across quotes, opportunities, and invoices.
#### Scenario: Create and reuse a catalog product
- Given a product manager adds a product with Name, Description, SKU, Manufacturer, List Price, Cost, and Status = Active
- When a sales rep searches the catalog while building a quote and an opportunity
- Then the product appears in search results with its captured details, can be selected for both records, and the linked quote/invoice lines retain the catalog reference.

### Hierarchical product categories
- The catalog shall support multi-level categories and subcategories to organize products for navigation, filtering, and reporting without losing parent-child context when the tree changes.
#### Scenario: Assign and navigate categories
- Given an admin creates categories “Hardware” > “Laptops” > “Ultrabooks”
- When they assign a product to “Ultrabooks” and later reparent “Ultrabooks” under “Mobile Devices”
- Then the product remains linked to the new hierarchy, users can filter by any category level, and reports show counts by both parent and child categories.

### Pricing and discount configurations
- The catalog shall capture list and cost prices, volume-based pricing tiers, and discount rules that can be percentage or fixed amounts, including promotional date windows, bundle pricing overrides, and customer/group-specific pricing.
#### Scenario: Apply tiered and promotional pricing
- Given a product has List Price $500, Cost $350, a tier rule for 10–49 units at $475, 50+ units at $450, and a promotional 10% discount for “VIP” accounts valid this month
- When a VIP customer order adds 60 units during the promo window
- Then the system prices the line at $450 from the tier, applies the 10% VIP discount, records the applied rules, and falls back to list pricing after the promo expires.

### Product relationships for recommendations
- The system shall model cross-sell, upsell, bundle, and dependency relationships between products so sales flows can suggest complements, premium alternatives, and required components.
#### Scenario: Recommend related products on a quote
- Given “Base Station” has cross-sells “Extra Sensor” and upsell “Base Station Pro”, and “Starter Bundle” includes “Base Station” + “Sensor” as bundled components
- When a rep adds “Base Station” to a quote
- Then the quote suggests the cross-sell and upsell items, the rep can add the “Starter Bundle” to include the related components in one step, and dependency rules block submission if any required component is missing.
