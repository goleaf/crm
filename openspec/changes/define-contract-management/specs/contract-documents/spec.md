# Contract Document and Relationship Requirements

## ADDED Requirements

#### Requirement 1: Contract documents are stored with versioning and execution metadata.
- Scenario: A user uploads a draft or signed contract file (PDF, DOCX) to the contract, enters a Version label and Execution Date (for signed copies), and the system stores the file with uploader, timestamp, and version metadata so prior versions remain downloadable.
- Scenario: Marking a document as the current executed version updates the contract to reference that file while retaining older versions for compliance and audit.

#### Requirement 2: Contract templates are managed as reusable, versioned assets.
- Scenario: An admin creates or updates a contract template with clauses, SLA defaults, auto-renew settings, and merge fields; the template is versioned and can be selected during contract creation to pre-fill fields and attach the rendered document.
- Scenario: Deprecating a template hides it from new contracts while preserving historical links on existing contracts that used it.

#### Requirement 3: Relationship tracking links contracts to upstream and peer records.
- Scenario: While creating or editing a contract, the user links it to an Account (mandatory) and optionally to Opportunities, Cases, or a Parent Contract; saving creates relationship records so those entities show the contract in their subpanels.
- Scenario: Linking a child contract (e.g., SOW) to a master agreement allows navigation between them, and deactivating the master can warn about active children to prevent orphaned terms.
