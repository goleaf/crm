# Documents Module

## ADDED Requirements

### Document repository with upload and metadata capture
- The system shall provide a central document repository where users can upload files (PDF, DOCX, images, spreadsheets) and store metadata including title, description, type, status, owner/team, size, mime type, custom fields, and optional expiration.
#### Scenario: Upload a document with metadata
- Given a user with permission to add documents
- When they upload a PDF, enter a title, select type "Policy", status "Draft", set an expiration date, and save
- Then the repository stores the file blob and a document record with the entered metadata, initializes version 1, and shows the document in the library with its metadata available for search

### Classification by types, categories, folders, and tags
- Documents shall support categorization via document types, categories/subcategories, folder hierarchy, and free-form tags so users can browse and filter the library by any of these facets.
#### Scenario: Organize a document with categories and tags
- Given an uploaded document
- When a librarian assigns it type "Contract", category "Legal > NDAs", places it in folder `/Legal/NDAs`, and adds tags "partner" and "2024"
- Then the document appears under that folder, surfaces under the Legal > NDAs category, and is discoverable via type or tag filters

### Version control with check-in/check-out
- The repository shall maintain version history for each document; check-out locks edits to a single user until check-in, and each check-in creates a new version entry with uploader, timestamp, and comment while retaining prior versions for download.
#### Scenario: Check out and check in a new version
- Given a document at version 1.0
- When a user checks it out, uploads an updated file with a "Corrected clause" comment, and checks it in
- Then version 2.0 is created with the new file and comment, version 1.0 remains downloadable, and other users were prevented from checking in changes during the checkout lock

### Document status lifecycle (Draft, Active, FAQ)
- Documents shall support statuses including Draft, Active, and FAQ; status changes are tracked with timestamps and can control visibility (e.g., Draft hidden from general users, FAQ highlighted in self-service views).
#### Scenario: Promote a draft to active and FAQ
- Given a document in Draft status
- When an approver marks it Active and flags it as FAQ
- Then the document becomes visible to users allowed to view Active documents, is listed in FAQ views, and the status history records the change

### Document templates
- The system shall allow storing documents as templates that can be reused to generate new documents with prefilled metadata and mergeable placeholders without altering the original template.
#### Scenario: Create a document from a template
- Given an approved "Support FAQ Template" marked as a template
- When a user selects "Create from template", fills the placeholders, and saves
- Then a new document record is created with the rendered file, inherits template metadata defaults (type, category, tags), and begins its own version history separate from the template

### Document relationships to other records
- Documents shall link to other records (accounts, opportunities, cases, contacts, contracts, or other documents) so related records can display and navigate to associated documents.
#### Scenario: Relate a document to an account and a case
- Given a stored document
- When a user links it to Account A and Case B
- Then both the account and case show the document in their related items, and the document record lists Account A and Case B as relationships

### Document search across metadata and content
- Users shall search documents by keywords and filters (type, status, category, folder, tags, relationships, owner, expiration state) with results ranked by relevance and supporting pagination.
#### Scenario: Search for FAQ documents by tag
- Given multiple documents with varied tags and statuses
- When a user searches for keyword "invoice" filtered to status FAQ and tag "billing"
- Then the results list only FAQ documents tagged "billing" whose metadata or indexed content matches "invoice"

### Document preview
- The system shall provide inline previews for supported formats (PDF, images, text) showing the current version without requiring download, and fall back to download when preview is unavailable.
#### Scenario: Preview a PDF without downloading
- Given a user with view access to a PDF document
- When they open the document record
- Then the UI renders a preview of the latest version's first pages inline and offers controls to navigate pages without downloading the file

### Document download and version retrieval
- Authorized users shall download the latest document version or a specific prior version; downloads are logged with user, timestamp, and version number.
#### Scenario: Download a prior version
- Given a document with versions 1.0 and 2.0
- When a user selects version 1.0 and clicks Download
- Then the system serves the version 1.0 file, records the download event with that version number, and leaves version 2.0 unchanged

### Document sharing and permissions
- Document access shall be governed by role/team permissions and per-document ACLs; users may share documents or share-links with specific people/teams with scoped rights (preview-only or download) and optional expiry.
#### Scenario: Share a document with scoped access
- Given a document owned by the Legal team
- When a legal user shares it with the Sales team as preview-only for 14 days
- Then Sales users can open previews but cannot download the file, the share expires after 14 days, and unauthorized users receive an access denied message

### Document expiration enforcement
- Documents shall support expiration dates that block preview/download after expiry while retaining history; notifications warn owners before expiration.
#### Scenario: Enforce expiration on a document
- Given a document with expiration set to yesterday
- When a user attempts to preview or download it today
- Then the system blocks access, shows that the document is expired, and keeps prior version history intact for audit purposes

### Document approval workflows
- Documents shall support approval workflows where drafts can be submitted, routed to approvers, and only become Active after approval; approval decisions are recorded with comments and timestamps.
#### Scenario: Approve a document into Active status
- Given a draft document pending approval
- When an approver reviews, approves it, and adds a comment
- Then the document transitions to Active, the approval record stores the approver, decision, and comment, and the status history reflects the change
