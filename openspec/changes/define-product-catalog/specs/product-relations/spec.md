# Product Relationships

## ADDED Requirements

#### Requirement 1: Product bundles group component SKUs with quantity, pricing, and inventory behaviors.
- Scenario: A “Field Starter Kit” bundle references 1× Base Station, 2× Sensor, and 1× Carrying Case; the bundle can use its own bundle price or sum component prices, and when ordered, inventory reservations/decrements cascade to each component SKU while keeping the bundle line intact for reporting.

#### Requirement 2: Cross-sell and upsell relationships connect related products for recommendations.
- Scenario: The “Base Station” product lists cross-sell accessories (Extra Sensor, Extended Warranty) and an upsell (Base Station Pro) with defined priority; when a rep adds the base item to a quote or opens its detail page, the system suggests these related products, skipping any that are Inactive or End-of-Life.
