# Targets

## ADDED Requirements

### Lightweight target creation with minimal required data
- The system shall allow creating Targets with minimal required fields (first/last name or organization name plus at least one of email or phone), defaulting status to `New` and storing prospect intent.
#### Scenario: Create a target with only an email
- Given a user enters first name “Alex” and email “alex@example.com” on the Target form
- When they save without a phone or account linkage
- Then the Target is created in `New` status, passes validation because email is present, and is available for list membership

### Campaign and target list association with reuse
- Targets shall link to one or more campaigns and target lists without restriction, enabling the same Target to participate in multiple outreach efforts while keeping membership history.
#### Scenario: Reuse a target across campaigns
- Given a Target is on the “Fall Webinar” target list tied to Campaign A
- When a marketer adds the same Target to the “Product Launch” list for Campaign B
- Then the Target appears on both lists with separate membership records and histories, and campaign metrics treat the Target independently per campaign

### Conversion and safe deletion
- The system shall convert a Target to a Lead or Contact (and optionally Account) while retaining activity history and list memberships by re-pointing memberships to the new record; deleting a Target removes its memberships but does not affect campaigns or other members.
#### Scenario: Convert a target to a lead
- Given a Target has list memberships and an activity timeline
- When a user converts it to a Lead
- Then the Lead inherits list memberships and campaign associations, the original Target is marked `Converted`, history remains visible, and deleting the original Target does not remove the Lead or its memberships

### Do Not Call and email opt-out enforcement
- Targets shall store Do Not Call flags and email opt-out status; these flags shall prevent inclusion in phone/email outreach and add the Target to suppression logic during send resolution.
#### Scenario: Respect DNC during outreach
- Given a Target has phone number set and `Do Not Call = true`
- When a call list is generated from a target list
- Then the Target is excluded from the dialer export, appears with a DNC reason in the log, and any email opt-out flag also excludes it from email sends

### Target source tracking, import, and deduplication
- The system shall capture Target source (manual entry, import file, campaign response, web form) and support imports with deduplication by email/phone + name, reusing existing Targets instead of creating duplicates.
#### Scenario: Import and dedupe tradeshow targets
- Given a CSV of tradeshow leads with names and emails
- When the marketer imports with dedupe on email
- Then existing Targets with matching emails are updated with source “Tradeshow 2025” without creating duplicates, and new Targets are created for unmatched rows with the source recorded

### Target activity history
- Targets shall maintain an activity history including list additions/removals, campaign sends/responses, imports, conversions, and status changes for audit and reporting.
#### Scenario: View a target’s activity timeline
- Given a Target was imported, added to two lists, sent an email, and converted
- When a user opens the activity timeline
- Then entries show the import event, list additions, email send result, and conversion with timestamps and user/system actors
