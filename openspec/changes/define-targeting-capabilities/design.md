# Design Notes

## List Composition & Membership Model
- Target Lists store members from multiple modules (Accounts, Contacts, Leads, Targets, Users) via a polymorphic join so list size can be counted and deduped without duplicating entity data.
- Manual lists accept direct additions, imports, and bulk adds from saved searches; dynamic lists persist query criteria and rebuild membership on demand using the same search builder used by campaign targeting.
- Membership entries carry status (active, archived, removed, bounced/invalid) to support subscriber status management without deleting the underlying record.

## List Types & Enforcement
- Type classification (Default, Test, Suppression) controls campaign behavior: test lists limit blast scope, suppression lists are always subtracted from default lists during resolution, and defaults represent the primary audience.
- List relationships (parent/child, merged-from) are tracked so archived or merged lists keep lineage and can be audited.
- Size management runs on the membership join, counting unique members after dedupe rules (email/phone or entity id) and surfacing counts by type and source.

## Operations & Data Integrity
- Merge and duplicate removal operate on the membership join to collapse duplicates across member types while preserving the source list reference for audit.
- Imports/exports map to CSV with minimal required fields per member type; imports honor opt-out/DNC flags and suppression entries, and exports include list type and subscriber status for reconciliation.
- Archiving a list freezes membership, blocks new additions, and keeps the list available for historical reporting while leaving campaigns that referenced it intact.

## Target Entities & Conversion
- Targets are lightweight prospects with minimal required data (name or email/phone), opt-out and DNC flags, status (New, In Progress, Converted, Invalid), and source attribution (import, campaign, manual).
- Targets can participate in multiple lists and campaigns; deletion is non-destructive to lists (removes membership, preserves activity history).
- Conversion upgrades a Target to a Lead or Contact (and optionally Account) while retaining list memberships for the new record and marking the original Target as converted for history.
