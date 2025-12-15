# Design Notes

- Product grid should expose row actions (view, edit, delete) and selection for mass delete with confirmation to prevent accidental removal.
- Filters should index SKU, name, and price for responsive grid narrowing; filter state should be reflected in exports if grids are reused.
- Attachments (files/notes) on product view should store with audit metadata and display in dedicated tabs/sections, keeping links intact when products are referenced by leads/quotes.
- Product selections in related flows (e.g., leads/quotes) must source from the same product catalog to avoid drift between grid and assignments.
