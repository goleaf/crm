# Account Interaction Requirements

## ADDED Requirements

#### Requirement 1: The Detail View surface presents action buttons, key fields, and subpanels as described so users see identity, contact, rating, assignment, billing/shipping, description, and related records at a glance.
- Scenario: After saving an account, the user lands on Detail View that shows [Edit], [Duplicate], [Delete], [More Actions], displays Account Name with icons for Phone, Email, Website, lists Type, Industry, Employees, Revenue, Rating, Assigned To, Teams, and renders Billing/Shipping addresses, the Description block, and subpanels for Contacts, Opportunities, Activities, etc.

#### Requirement 2: Subpanel actions support creating, linking, importing, and mass-updating related records in context.
- Scenario: An account manager opens the Contacts subpanel, clicks [Create], fills contact details in the popup, clicks Select to link an existing contact, uses Mass Update to bump the Rating in 20 chosen opportunities, and uses Export to download the list; each action respects the same account link.

#### Requirement 3: List View defaults to columns for Account Name, Type, Industry, Assigned To, phone/email, and provides column customization, sorting, pagination, inline editing, bulk actions, favorites, and recently viewed access.
- Scenario: A rep filters to “My Accounts,” sorts by Rating descending, edits a phone number inline, selects 50 rows, chooses Mass Update → Type=Customer, and tracks the sanitized list via Favorites/Recently Viewed shortcuts.

#### Requirement 4: Search covers both basic filters and advanced queries with saved searches, supports wildcards/phrases, and integrates relevance features when available.
- Scenario: Searching for “Acme Corporation” in the top nav returns matching accounts immediately; advanced search allows the user to combine Type equals Customer with Revenue between $1M and $10M, save that filter as “Mid-market Customers,” and rerun it with one click.

#### Requirement 5: Duplicates and audit logs are surfaced via Detail View actions and save-time checks.
- Scenario: Clicking More Actions → Find Duplicates shows potential matches by name/email/phone; saving a new record with the same Account Name triggers “Account name already exists” warning while letting the user continue or cancel; the View Change Log action reads `accounts_audit` entries and reports who changed Assigned To from one rep to another.
