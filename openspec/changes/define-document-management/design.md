# Design Notes

## Repository and metadata model
- Each document stores a file reference, checksum, mime type, size, owner/team, status, type, category, folder path, tags, and expiration date so search, permissions, and lifecycle rules can rely on metadata without touching the file blob.
- Custom metadata fields should be extensible to support templates, FAQ labeling, and module-specific attributes while keeping a consistent core schema for indexing and permissions.
- Upload handling writes to managed storage with version-aware filenames and captures a preview-friendly derivative (e.g., first page image) when the format supports it.

## Versioning and lifecycle controls
- Version history keeps immutable records of each upload with uploader, timestamp, and check-in comment; check-out locks edits until the user checks in or an admin overrides.
- Status transitions (Draft → Active → Deprecated/FAQ) can be gated by approval workflows; approvals record approver, decision, and timestamp to support audits.
- Expiration enforces read/download blocks after the configured date while retaining historical versions for compliance and relationship references.

## Access, sharing, and relationships
- Permissions combine role/team ownership with per-document ACLs and optional share links that can be time-bound and scoped to preview or download; audit logs capture access, downloads, shares, and approval decisions.
- Documents link to other records (accounts, cases, opportunities, knowledge articles, etc.) through relationship tables so related entities can surface attached documents and honor their permissions.
- Templates are stored as specialized document records flagged for reuse; generating a document from a template clones metadata defaults and starts a new version history for the produced file.

## Search and preview experience
- Search indexes metadata (title, description, tags, categories, types, statuses, relationships) and, when available, text extracted from supported formats to enable filtered and keyword queries.
- Previews render supported formats inline (PDF, images, text) with fallbacks to download when preview derivatives are unavailable, respecting the same permission checks as downloads.
