# Product Catalog Foundations

## ADDED Requirements

#### Requirement 1: Product master records capture identity, classification, state, and lifecycle data for catalog management.
- Scenario: A product manager creates a product with Name, SKU, Part Number, Type (e.g., Stocked/Service/Non-stock), Manufacturer, Status (Active/Inactive), and Lifecycle Stage (Draft → Released → End-of-Life), and the system enforces SKU/part-number uniqueness, timestamps the record, and exposes it to list/detail views only when Active/Released.

#### Requirement 2: Products can be organized by categories and subcategories with manufacturer tracking.
- Scenario: The admin assigns a product to “Hardware > Laptops” while tagging the Manufacturer as “Relaticle Devices”; if the category tree is reparented or a subcategory is added, existing products retain their placement and can be filtered or reported by any category level or manufacturer.

#### Requirement 3: Descriptions, images, and attachments are stored alongside each product.
- Scenario: A product includes a marketing description, tech specs, a designated primary image, and supporting attachments (datasheets, CAD files); uploads are versioned per product so quotes and storefronts always show the current primary image while keeping a history of prior assets.

#### Requirement 4: Custom product fields can be added without disrupting the base schema.
- Scenario: An admin creates a custom field “Voltage Rating” that appears on Create/Edit forms, exports, and the API; values persist with the product, respect validation (e.g., numeric range), and surface in list filters without schema migrations.
