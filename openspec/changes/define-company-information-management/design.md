# Design Notes

## Data-to-UI Mapping
- The core `accounts` table captures identity (name, legal entity, ticker), contact info (phones, email, website), addresses, and operational metadata (assigned user, rating, ownership). `accounts_cstm` extends this surface with bespoke business intelligence fields such as revenue brackets, SIC/NAICS codes, campaign attribution, and descriptive notes so that the form panels can be populated without touching the base schema.
- `accounts_audit` must track any audited fields referenced in the narrative so change logs can surface who modified assignments, types, or ratings; the Detail View “View Change Log” action reads directly from this table.
- Supporting tables (`email_addresses`, `relationships`, `securitygroups_records`) feed the relational context panels and security gating in the Detail View and imports.

## Workflow Interactions
- Create Form, Quick Create, Import, Web-to-Account, and API submissions share validation and duplicate handling logic; reusing service layers here keeps the multi-channel entry experience consistent (same duplicate rules, same security-group assignments, same email association).
- Saving a record must trigger workflow hooks (assignments, rating-based notifications) and subpanel wiring (teams, related campaigns) so that data entered through any path behaves identically in reporting, notifications, and automation.

## Viewing & Editing
- Detail View, List View, and search filters compose around the same metadata: action buttons rely on the same record-state service that Quick Create uses to lock required fields, and inline edits deflect to the same validation layer as the Create form.
- Audit logs, favorites, and saved searches feed contextual cues for users while keeping the data layer agnostic of the presentation (Detail View only reads the recorded change log).
