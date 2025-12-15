# Tasks

- [x] Capture the SuiteCRM Account narrative and translate it into explicit requirements for the CRM data model; validate by checking that every table (accounts, accounts_cstm, accounts_audit, email_addresses, relationships, securitygroups) appears in the spec.
- [x] Write the `account-forms` specification covering Create, Quick Create, Import, Web-to-Account, and API flows along with field expectations and save-time validations; review against the provided field guidance to confirm required/optional statuses.
- [x] Draft the `account-interactions` specification addressing Detail/List views, subpanels, action buttons, searches, duplicate handling, and audit logs; confirm each scenario (e.g., Find Duplicates, mass update, favorites) has a matching requirement.
- [x] Compose `design.md` to explain architectural linkages between the data tables and the various UI/workflow surfaces, noting where workflows, teams, and security groups interact with the Accounts module.
- [x] Perform a manual inspection of the new spec files for clarity and consistency, and note that `openspec validate` could not run because the CLI is unavailable in this environment.
