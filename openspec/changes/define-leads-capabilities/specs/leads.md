# Leads

## ADDED Requirements

### Lead capture with source identification and tracking
- The system shall accept new leads from manual entry, imports, and web-to-lead forms while recording creation source, lead source, and the original submission payload to maintain attribution and audit history.
#### Scenario: Capture from web form with attribution
- Given a public web-to-lead form submits name, email, and source = “Web Form”
- When the lead is created
- Then the lead stores creation_source = Web, source = Web Form, preserves the submitted payload for audit, and appears in the lead list with status `New`

### Lead status management and qualification workflow
- Lead records shall support statuses (New, Working, Nurturing, Qualified, Unqualified, Converted, Recycled) and capture qualification details (qualified_by, qualified_at, notes) when moving into Qualified or Unqualified states.
#### Scenario: Qualify a working lead
- Given a lead in status `Working`
- When a rep marks it Qualified with notes and qualified_by set to themselves
- Then status changes to `Qualified`, qualified_at stores the timestamp, notes are saved, and the change appears on the activity timeline

### Lead scoring and grading
- Leads shall carry an integer score and a grade (e.g., A/B/C/D) that can be updated by automation or users, with changes tracked in the activity log to reflect scoring models and manual grading.
#### Scenario: Update score and grade after engagement
- Given a lead has score 10 and grade C
- When an engagement rule adds 20 points and a rep upgrades the grade to B
- Then the lead shows score 30, grade B, and both updates are logged with who/what triggered them

### Lead conversion to Contacts, Accounts, and Opportunities
- The system shall convert a lead into a Contact, optionally create or link an Account, and optionally create an Opportunity while keeping ownership/teams aligned and preventing duplicate conversions.
#### Scenario: Convert to contact and existing account
- Given a lead linked to company “Acme” in status `Qualified`
- When the rep converts it selecting the existing “Acme” account and chooses to create an Opportunity
- Then a Contact is created/linked, an Opportunity is opened with the lead’s details, the lead moves to `Converted`, and conversion metadata (by, at, converted entity ids) is stored

### Lead distribution and assignment strategies
- Leads shall support assignment strategies (Manual, Round Robin, Territory) and record assignment strategy, assignee, and timestamp on each assignment change for audit.
#### Scenario: Round-robin assignment
- Given round-robin is enabled for the “Default” pool with three active owners
- When three new leads are created with assignment_strategy = Round Robin
- Then each lead is assigned to a different owner in rotation, and the assignment log shows strategy = Round Robin with timestamps

### Territory-based assignment
- Territory-based routing shall select an assignee from the territory roster using the lead’s territory/geography; when no eligible assignee exists, the lead remains unassigned with a flagged routing outcome.
#### Scenario: Route to a territory owner
- Given territory “West” has two eligible owners and a lead arrives with territory = West
- When routing runs
- Then the lead is assigned to one of the territory owners, the assignment log notes strategy = Territory and selected user, and the lead shows territory = West

### Lead nurturing workflows
- Leads shall track nurture status, program, and next touch date and allow workflows to schedule nurture tasks/emails; workflow actions shall update nurture_status and next_nurture_touch_at when steps are completed.
#### Scenario: Advance nurture step
- Given a lead with nurture_status = Not Started and next_nurture_touch_at = today
- When the nurture workflow sends the first email and schedules a follow-up for +7 days
- Then nurture_status moves to Active, next_nurture_touch_at is set to the follow-up date, and both actions are recorded on the timeline

### Lead activity tracking
- Lead records shall maintain an activity timeline capturing creation, status changes, assignments, nurture events, tasks/notes, imports, web form submissions, deduplication, and conversions for reporting and audit.
#### Scenario: View recent lead activity
- Given a lead was created via web form, assigned round-robin, had status changed to Working, and was qualified
- When a user opens the timeline
- Then entries show the web form submission, assignment with strategy, status change, and qualification with timestamp and actor

### Duplicate detection and resolution
- The system shall detect potential duplicate leads using email and phone + name matching, surface a duplicate score, and allow users to merge or mark as not a duplicate while linking duplicates via `duplicate_of`.
#### Scenario: Resolve a suspected duplicate
- Given a new lead matches an existing lead on email and is flagged with duplicate_score 0.92
- When a user merges it into the existing lead
- Then the new lead is linked via duplicate_of, duplicate_score is stored, and the merge appears in the activity log with the surviving lead retaining ownership and history

### Lead import and export
- Lead imports shall map CSV/XLS columns to lead fields, apply deduplication rules before creation, and report created vs updated counts; exports shall include source, status, assignment, score/grade, nurture fields, and conversion status with filterable criteria.
#### Scenario: Import leads with dedupe and report results
- Given a CSV with 100 rows where 10 match existing emails
- When the import runs with dedupe on email
- Then 10 leads are updated, 90 are created, import_id is stored on affected leads, and the import report summarizes created/updated counts

### Web-to-lead forms
- Web-to-lead forms shall support configurable required fields, validation (email/phone formats), CSRF or token-based submission protection, and spam filtering, creating leads with preserved payload and source attribution.
#### Scenario: Validate and accept a web-to-lead submission
- Given a web form requiring name and email and protected with a submission token
- When a visitor submits valid data with the token
- Then the lead is created with creation_source = Web, validation passes, spam checks are logged, and the submission payload is stored for audit
