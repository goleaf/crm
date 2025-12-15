# Proposal: define-database-relationships

## Change ID
- `define-database-relationships`

## Summary
- Standardize how we model one-to-many, many-to-many, and polymorphic relationships across entities (contacts, companies, deals, products, activities).
- Specify eager-loading practices and constraints (soft-deletes, tenant scoping) to avoid N+1 queries and keep data isolated per team.
- Document required relationship metadata (timestamps, pivot columns) and validation rules for safer data access.

## Capabilities
- `one-to-many-relationships`: Define parent/child links with ownership, ordering, and cascade behaviors.
- `many-to-many-relationships`: Capture pivot metadata, timestamps, and uniqueness for shared associations.
- `polymorphic-relationships`: Standardize morph maps, constraints, and activity/file/comment linking.
- `relationship-eager-loading`: Guidance for default eager loads and query constraints to reduce N+1.
- `relationship-constraints`: Enforce tenant, soft-delete, and role-based filters on relationship queries.

## Notes
- OpenSpec CLI tooling is unavailable in this environment; specifications are drafted manually.
