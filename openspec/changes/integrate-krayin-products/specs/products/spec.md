# Products

## ADDED Requirements

#### Requirement 1: Create products with required fields.
- Users shall create products by entering name, description, SKU, and price, and saving them to appear in the product grid.
#### Scenario: Create a product
- Given a user opens Products → Create Product
- When they enter Name, Description, SKU, and Price and click Save as Product
- Then the product is saved and appears in the product data grid with those values visible

#### Requirement 2: Edit existing product details.
- Users shall edit product name, description, SKU, and price from the product grid actions and save changes.
#### Scenario: Edit a product
- Given a product exists in the grid
- When the user selects Edit, updates the Description and Price, and saves
- Then the product stores the new values and the grid reflects the updates

#### Requirement 3: View products with files and notes.
- Users shall view a product and add files and notes that display in the product’s Files and Notes sections.
#### Scenario: Add files and notes to a product
- Given a product exists
- When the user opens View and uploads a file and adds a note
- Then the file and note appear in the product’s Files, Notes, and All sections for that record

#### Requirement 4: Delete single or multiple products safely.
- Users shall delete products individually via grid actions and perform mass delete via grid selection, confirming the action before removal.
#### Scenario: Mass delete products
- Given multiple products are selected in the grid
- When the user clicks Delete and confirms
- Then all selected products are removed from the grid and no longer available for selection in related flows

#### Requirement 5: Filter products by key fields.
- Users shall filter products by SKU, Product Name, or Price in the product grid to narrow results.
#### Scenario: Filter by SKU
- Given products exist with different SKUs
- When the user applies a filter for a specific SKU
- Then only products matching that SKU remain visible in the grid

#### Requirement 6: Use products in lead/quote creation.
- Products shall be selectable/assignable when creating related records (e.g., leads or quotes) consistent with the product grid data.
#### Scenario: Assign product to a lead
- Given products exist in the grid
- When the user creates a lead and assigns a product
- Then the selected product attaches to the lead and matches the grid data (name, SKU, price)
