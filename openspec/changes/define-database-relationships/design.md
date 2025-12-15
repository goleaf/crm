# Design Notes

## Relationship Modeling
- One-to-many: parent tables (companies, people, opportunities, orders) expose child collections (notes, tasks, line items). Foreign keys use `cascadeOnDelete` only where child data should not survive the parent; otherwise `nullOnDelete` with soft deletes. Ordering columns (`order_column`, `sort_order`) support board/list ordering.
- Many-to-many: pivot tables include `team_id`, timestamps, and metadata (e.g., `role`, `access_level`, `position`). Unique constraints prevent duplicate associations; pivot models can encapsulate extra logic where needed.
- Polymorphic: morph maps are enforced centrally (see `AppServiceProvider`) to prevent class-name leakage. Activities, files (media), notes, and tasks link via morphs; ensure tenant/team scoping is applied when resolving morphs.

## Query and Eager Loading
- Default eager loads on resource queries should include relationships commonly displayed in tables (owner, company, contact) and custom-field values where needed. Use `loadMissing` inside services when calculating metrics to avoid repeated queries.
- Relationship queries apply tenant and soft-delete scopes consistently; board queries that join custom field values should still honor these scopes.

## Constraints and Validation
- Tenant/team filters must be enforced on relationship lookups to avoid cross-tenant leakage. When attaching/detaching pivot records, validate membership in the same team.
- Morph relations should disallow polymorphic targets that are soft-deleted unless explicitly querying trashed.

## Performance
- Use indexed foreign keys on pivots and morph tables; add composite indexes for (`team_id`, `parent_id`) patterns. Cache resolved custom fields/options per tenant for repeated relationship reads in metrics services.
