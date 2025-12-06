# Account Entry Surfaces

## ADDED Requirements

#### Requirement 1: The full Create form is reachable via all standard navigation paths and organizes Account data into collapsible panels for Overview, Description, Billing Address, Shipping Address, and Additional Information.
- Scenario: A user opens the Accounts module from the top menu, sidebar, Quick Create dropdown, or Home dashboard, clicks “Create Account,” sees Panel 1 (overview fields like Name, Phone, Email, Website, Type, Industry), Panel 2 (description notes), Panels 3/4 (address blocks with copy-from-billing), and Panel 5 (custom or extended fields such as revenue, SIC, rating) before submitting.
- Scenario: Tooltips or inline guidance remind reps to enter the official Account Name with Title Case, format international phone numbers with country code, store websites with `https://`, and capture any requested custom fields so that data stays consistent.

#### Requirement 2: The form enforces required fields, field-specific formatting, and save-time validation including duplicates, then triggers workflows and related record creation.
- Scenario: Saving without an Account Name rejects the form with “Missing required field,” invalid email formats show “Invalid email format,” duplicate detection warns when a matching name/email exists, and a successful save timestamps the `accounts` row, creates `email_addresses` and `securitygroups_records`, and fires “Record Created” workflows while redirecting the user to Detail View.

#### Requirement 3: Quick Create is a lightweight modal (accessible via the lightning bolt icon) that captures mandatory data (Account Name, Phone, Email, Website, Type, Assigned To) and defers the rest to the full edit page.
- Scenario: During a call, a rep opens Quick Create, fills the core fields, submits, and the record is saved with those values; the modal closes and the representative can immediately click the new account name to finish details in the full form.

#### Requirement 4: The Import wizard ingests CSV/Excel/tab-delimited files with header row mappings, field-by-field validation, duplicate handling (create/update/skip), and post-import review.
- Scenario: An admin uploads a UTF-8 CSV with Account Name, Phone, Email, Website, Type, Industry, Assigned To; the wizard previews data, allows auto or manual mapping (including dropdown value matching), warns about invalid emails or missing Account Name, enforces duplicate checking on name/email, and after import provides a summary of created, updated, skipped, and failed rows plus an error log.

#### Requirement 5: Alternative capture paths (Web-to-Account forms and the API) submit the same data model with OAuth-secured REST calls and minimal required fields.
- Scenario: A marketing form embeds captured fields (Company Name, Phone, Email, Website, Interest, Comments), posts to the CRM, assigns the account automatically via routing rules, and triggers the same workflows as manual creation; similarly, an API client authenticates via OAuth 2.0, posts the required JSON payload, and receives the created Account ID in the response.
