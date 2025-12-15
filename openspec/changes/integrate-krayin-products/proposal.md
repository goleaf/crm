# Proposal: integrate-krayin-products

## Change ID
- `integrate-krayin-products`

## Summary
- Capture Krayin CRM product behaviors (create/edit/view/delete, mass delete, filters, files/notes) as requirements aligned with our OpenSpec.
- Ensure product records include core fields (name, description, SKU, price) and can be attached to leads/quotes via data grid actions.
- Document grid actions, filters, and bulk operations to keep parity with the referenced Krayin documentation.

## Capabilities
- `product-crud-and-grid`: Product creation/edit/view/delete with required fields, files/notes attachments, and grid actions.
- `product-filters-and-bulk-ops`: Filtering by SKU/name/price and mass delete across the product data grid.
- `product-associations`: Products usable/assignable when creating related records (e.g., leads/quotes).

## Notes
- Source: https://docs.krayincrm.com/2.x/product/products.html
