# Contract Data Requirements

## ADDED Requirements

#### Requirement 1: Contract records capture typed agreements with required dates, value, and status fields.
- Scenario: A user creates or edits a contract selecting a Contract Type (MSA, SOW, NDA, etc.), enters Start Date and End Date (validated so End Date is on or after Start Date), sets Renewal Date if applicable, and enters Contract Value with currency; saving enforces mandatory fields and assigns a Status (Draft by default) so all contracts share consistent core data.
- Scenario: Updating a contract adjusts Start/End/Renewal dates and Status while logging who changed the values, ensuring lifecycle services (renewals, expirations) and reporting can trust the stored dates and status.

#### Requirement 2: Auto-renewal options and renewal tracking are stored on the contract record.
- Scenario: When a user enables auto-renewal, they can set renewal term length and notice period; saving persists Auto-Renew (on/off), Renewal Date, and Next Term End Date so schedulers can extend the contract or queue reminders without further input.
- Scenario: Disabling auto-renewal clears the auto-renew flag while retaining Renewal Date for manual renewals; the system warns if the date is missing for contracts marked renewable.

#### Requirement 3: Terms, SLAs, and templates populate structured fields and references during creation.
- Scenario: Selecting a Contract Template pre-fills Terms and Conditions text, default SLA values (response/resolution windows, service level metrics), and a recommended Status; the user can review and edit before saving, and the chosen template ID is stored alongside the contract.
- Scenario: Manual entry of Terms and Conditions and SLA commitments is supported when no template is chosen, with validation that SLA fields include units (hours/days) and thresholds so downstream compliance checks have structured data.
