# Proposal: define-company-information-management

## Change ID
- `define-company-information-management`

## Summary
- Capture Company Information Management as the Accounts module foundation by specifying the data model, entry experiences, and interaction patterns described in the provided SuiteCRM narrative.
- Document how identity, contact, address, intelligence, ownership, security, and history data map to the domain tables and interfaces.

## Capabilities
- `account-data`: Core tables and relationships that persist account information, custom fields, audits, and security metadata.
- `account-forms`: Full Create, Quick Create, Import, Web-to-Account, and API flows that collect the required fields plus optional business intelligence.
- `account-interactions`: Detail/List views, search, duplicate handling, alerts, and team/assignment workflows that surface and edit the stored data.

## Notes
- `openspec` CLI tooling is not available in this workspace, so validation steps are documented but must be executed manually or outside this repository.
