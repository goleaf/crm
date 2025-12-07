# Contact Management

## ADDED Requirements

### Individual contact records capture full contact information and roles
- The system shall create and update individual contact records with required name, primary contact channels (emails, phones), job title/department, role(s) such as Decision Maker or Technical Contact, and an optional organization link, validating email/phone formats before persistence.
#### Scenario: Save a contact with role and communication channels
- Given a user enters name = "Jordan Smith", title = "VP Sales", role = "Decision Maker", company = "Acme", primary_email, and mobile/office numbers
- When they save the contact
- Then the contact is created with validated emails/phones, stored title/role, and a link to the selected company

### Contact relationships track reporting lines and related people
- The system shall store relationships between contacts, including managers/reports, assistants, and peer links, so reporting structures and relationship maps appear in contact views and can be navigated bidirectionally.
#### Scenario: Associate a contact to their manager and assistant
- Given a contact "Jordan Smith" should report to "Casey Lee" and list "Alex Kim" as assistant
- When the user sets reports_to = Casey and assistant = Alex on Jordan's record
- Then Jordan's profile shows Casey as manager, Alex as assistant, and Casey's profile lists Jordan as a direct report

### Contact activity history maintains a timeline of interactions
- The system shall keep an activity timeline per contact that records creations, edits, assignments, calls, meetings, tasks, emails, imports, and conversions so users can audit engagement history chronologically.
#### Scenario: View recent contact activity
- Given a contact was created, had a call logged, and a follow-up task scheduled
- When a user opens the contact timeline
- Then entries appear in order for the creation, the call with subject/duration, and the task with due date and owner

### Contact notes capture narrative context
- The system shall allow adding timestamped notes tied to a contact, preserving author, rich text content, and attachments so contextual details remain with the record.
#### Scenario: Add a meeting summary note
- Given a user just met a contact and writes a summary with next steps
- When they save the note on the contact
- Then the note stores author and timestamp, is visible on the contact detail, and can be edited or deleted later

### Contact custom fields extend the profile schema
- The system shall support administrator-defined custom fields for contacts that surface on create/edit forms, list views, and exports, persisting values alongside standard fields without code changes.
#### Scenario: Use a custom field on a contact form
- Given an admin adds a custom field "Account Tier" to contacts
- When a user fills "Account Tier = Gold" while creating a contact
- Then the contact saves with the custom value, and the field appears on the detail view and in exports

### Contact tags and labels enable segmentation
- The system shall let users apply multiple tags/labels to contacts, manage the tag vocabulary, and filter/search by tags for list building and automation triggers.
#### Scenario: Tag a contact and filter by the tag
- Given tags "Partner" and "VIP" exist
- When a user tags a contact as Partner and VIP
- Then the contact shows both tags, and filtering the contact list by tag = VIP returns that contact
