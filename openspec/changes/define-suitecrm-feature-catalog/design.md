# Design Notes

## Catalog structure
- Mirror the structure in `docs/suitecrm-features.md` with numbered category headers and bullet subfeatures so future specs can reference category + bullet paths (e.g., `Core CRM > Accounts > Account hierarchies`).
- Use concise requirement blocks that group related bullets per category to avoid duplicating the entire list while keeping traceability to the source document.
- Preserve the detailed acceptance criteria provided for Company information management under Accounts as an embedded example of deeper acceptance within the catalog.

## Usage for downstream specs
- Treat this catalog as a baseline reference; implementation-focused specs (e.g., Contracts, Documents) can link to relevant bullets to ensure coverage.
- Keep the catalog static unless upstream SuiteCRM capabilities change; incremental changes should be new OpenSpec deltas referencing the affected category/bullet.

## Validation approach
- Manual review only (no `openspec` CLI available): verify each category requirement lists the bullets present in `docs/suitecrm-features.md` and includes at least one scenario describing discoverability of the catalog content.
