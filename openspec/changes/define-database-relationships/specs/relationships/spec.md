# Database Relationships Requirements

## ADDED Requirements

#### Requirement 1: One-to-many relationships with ordering and lifecycle rules
- Scenario: A company has many notes and tasks; creating a note/task sets `company_id`, enforces same `team_id`, and orders tasks by `order_column`. Deleting a company soft-deletes its notes/tasks but preserves their records; restoring the company restores children.

#### Requirement 2: Many-to-many relationships with pivot metadata and uniqueness
- Scenario: An opportunity links to collaborators via a pivot containing `role`, `team_id`, and timestamps; attaching a collaborator validates team membership, prevents duplicates via a unique constraint, and records `role`. Detaching a collaborator leaves history untouched and does not remove the user.

#### Requirement 3: Polymorphic relationships using enforced morph maps
- Scenario: An activity entry references its subject via `morphTo`; when the subject is a note or task, the morph map resolves to the correct class, applies tenant scope, and excludes soft-deleted subjects unless `withTrashed` is requested. Uploading a file attaches media via a morph and writes `team_id` for filtering.

#### Requirement 4: Eager loading to avoid N+1 queries on primary resources
- Scenario: Listing opportunities eager loads `company`, `contact`, `owner`, and stage/forecast custom-field values in one query set; the list page executes a bounded number of queries regardless of row count, and metrics services call `loadMissing` for stage/options when computing weighted pipeline.

#### Requirement 5: Relationship constraints for tenancy and soft deletes
- Scenario: When fetching a company’s tasks, the query automatically scopes to the company’s `team_id` and excludes soft-deleted tasks by default; requesting trashed tasks explicitly includes them. Adding a collaborator checks the collaborator belongs to the same team; otherwise the attach is rejected.
