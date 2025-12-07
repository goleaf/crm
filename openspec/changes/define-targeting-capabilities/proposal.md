# Proposal: define-targeting-capabilities

## Change ID
- `define-targeting-capabilities`

## Summary
- Capture Target Lists and Targets modules so campaigns can build, segment, and reuse audiences with compliant contact flags and cross-module membership.
- Define how static and dynamic lists, list types (default, test, suppression), and target records interact with campaigns, imports, deduplication, and conversions.

## Capabilities
- `target-list-management`: Create and manage manual and dynamic lists with segmentation criteria, size tracking, membership visibility, and type classification.
- `target-list-operations`: Support imports/exports, merges, duplicate removal, bulk adds from searches, archiving, and relationship tracking between lists and campaigns.
- `target-records`: Maintain target entities with minimal contact data, opt-out and DNC flags, statuses, conversion, reuse, source tracking, and activity history while linking to lists and campaigns.

## Notes
- `openspec` CLI tooling is not available in this environment; specifications are drafted manually.
