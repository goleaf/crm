# Target Lists

## ADDED Requirements

### Manual and dynamic target lists with classification
- The system shall allow marketers to create manual (static) or dynamic (saved-search) target lists, associate segmentation criteria, and mark each list as `Default`, `Test`, or `Suppression` to control campaign behavior.
#### Scenario: Create default and suppression lists
- Given a marketer creates a list named “Q3 Prospects” as manual with type `Default`
- When they save it with no initial members
- Then the list stores as a static default list ready for membership and exposes its type on the list detail
- Given the marketer creates another list named “Global Opt-Outs” as dynamic with type `Suppression` using criteria “Email Opt-Out = true”
- When they save the dynamic list
- Then the list rebuilds membership from the criteria and is treated as a suppression list during campaign targeting

### Cross-module membership tracking and size management
- Target lists shall accept members from Accounts, Contacts, Leads, Targets, and Users, track per-member status (active/removed/bounced), and display list size as the count of unique active members after deduplication.
#### Scenario: Track mixed membership counts
- Given a list contains 50 Contacts, 20 Leads, and 10 Accounts with 5 duplicates by email
- When the list size is displayed
- Then the UI shows 75 raw members, 70 unique active members, and a breakdown by module with flags for removed or bounced entries

### Bulk addition from searches
- The system shall let users take the results of an advanced search or saved filter and bulk-add all matches to a selected target list while respecting dedupe rules and list type constraints.
#### Scenario: Add search results to a test list
- Given a user runs a Contacts search for “Title = Marketing Manager”
- When they choose “Add to Target List” and select a `Test` list
- Then all matched contacts are added to that list once, duplicates are skipped, and the list size updates accordingly

### Import and export of target lists
- Users shall import CSV/Excel files into a target list, mapping columns to member fields and applying duplicate checks; exports shall include list metadata (type, size, member status) and member attributes.
#### Scenario: Import targets into a default list
- Given a CSV containing first name, last name, email, and type = Lead
- When the marketer imports it into a `Default` list with email-based dedupe enabled
- Then new leads are added as members, existing leads are linked instead of duplicated, and the list size reflects only unique additions

### List merging and duplicate removal
- The system shall merge two or more target lists into a new or existing list while collapsing duplicates by configurable keys (email, phone, or entity id) and preserving source list references for audit.
#### Scenario: Merge event and house lists
- Given an “Event Attendees” list and a “House Prospects” list
- When the user merges them into “Q4 Master” with dedupe on email
- Then the resulting list contains each unique member once, records provenance of which source lists contributed each member, and updates size and counts

### Subscriber status management and suppression enforcement
- Target lists shall maintain subscriber status (Subscribed, Opted-Out, Bounced, Invalid) per member and enforce suppression: members on suppression lists are excluded from sends even if present on default or test lists.
#### Scenario: Suppress opted-out members
- Given a contact exists on both a default list and a suppression list
- When the campaign resolves recipients
- Then the contact is excluded, marked as suppressed in the resolution log, and list size calculations note the suppression

### List archiving and relationship tracking
- Users shall archive target lists to lock membership while keeping them visible for reporting; the system shall track list lineage (parent/child, merged-from) and relationships to campaigns for historical reference.
#### Scenario: Archive a legacy list with lineage
- Given a marketer archives the “2019 Prospects” list that was merged into “Q4 Master”
- When they view the archived list
- Then it remains read-only, shows its merged-into relationship, and any campaigns that previously used it still reference it in history without allowing new sends
