# Campaigns Module

## ADDED Requirements

### Email campaign creation
- The system shall let marketers create email campaigns that capture name, subject, sender identity, target lists, and an email template while saving in `Draft` status until scheduled.
#### Scenario: Save a draft email campaign
- Given a marketer opens New Campaign and selects channel `Email`
- When they enter a campaign name, subject, from name/address, attach a default target list, pick an email template, and click Save
- Then the campaign is stored as a draft email campaign with the selected target list and template linked for later scheduling

### Non-email campaign planning
- Users shall create non-email campaigns (telesales, radio, print) by selecting the channel/type, entering budgets and timelines, and saving without requiring email-only fields.
#### Scenario: Plan a radio campaign
- Given a marketer starts a new campaign and chooses type `Radio`
- When they provide a name, budget, start/end dates, and station notes
- Then the campaign saves with channel `Radio`, omits email template requirements, and can be reported alongside email campaigns

### Campaign budgeting and ROI tracking
- Campaigns shall record budget, expected revenue, and actual costs, and automatically calculate ROI as `(expected or actual revenue - actual cost) / actual cost` with timestamps for each update.
#### Scenario: Track ROI against spend
- Given a campaign with a $25,000 budget and $80,000 expected revenue
- When $10,000 of actual costs are logged and expected revenue remains $80,000
- Then the campaign shows actual cost $10,000, expected revenue $80,000, and ROI of 700%, and updates ROI again when new costs or actual revenue are recorded

### Campaign status management
- Campaigns shall enforce a status lifecycle (Draft, Planned, Scheduled, In Progress, Completed, Cancelled) that gates actions such as scheduling, sending, and edits to sent content.
#### Scenario: Move a campaign through statuses
- Given a campaign in `Draft`
- When the marketer finalizes targeting, sets a schedule, and submits for send
- Then the status advances to `Scheduled`, shifts to `In Progress` when sending starts, and to `Completed` after sending finishes with a status history visible on the record

### Campaign type categorization
- Campaigns shall be categorized by type (Email, Telesales, Radio, Print, Other) for reporting filters and to drive channel-specific UI fields during creation and edit.
#### Scenario: Filter campaigns by type
- Given multiple campaigns exist across Email, Telesales, and Print types
- When a user filters the campaign list for `Telesales`
- Then only telesales campaigns appear, and new telesales campaigns hide email-specific inputs while exposing call script notes

### Target list integration
- Campaigns shall attach default, test, and suppression target lists so recipient resolution pulls from linked lists while honoring suppression and opt-out rules.
#### Scenario: Attach target lists to a campaign
- Given a campaign record
- When the marketer links a default target list of prospects, a suppression list of unsubscribed contacts, and a small test list
- Then the campaign stores all three lists, uses the test list for test sends, and excludes suppression members from scheduled sends

### Email template design with HTML support
- The system shall provide an HTML email template designer with merge fields, inline image support, and preview so campaigns can send branded emails.
#### Scenario: Design and preview an HTML template
- Given a marketer opens the template designer
- When they build an HTML layout with a hero image, CTA button, and merge fields for first name and company, then preview the result
- Then the template saves with its HTML content, assets referenced, and shows the merge-field preview used by any linked email campaign

### Campaign wizard interface
- An assisted wizard shall guide marketers through campaign setup steps (details, target lists, content/templates, schedule) and prevent completion until required inputs per channel are satisfied.
#### Scenario: Complete the campaign wizard
- Given a marketer starts the wizard for an email campaign
- When they enter campaign details, select target lists, choose an email template, and set a schedule
- Then the wizard allows submission, validates required fields by step, and saves the campaign in `Scheduled` status with the captured configuration

### Campaign scheduling
- Campaigns shall allow scheduling a start time (and optional end window) that triggers sending at the configured datetime while locking content after scheduling.
#### Scenario: Schedule a future send
- Given a draft email campaign with content and target lists
- When the marketer schedules it for next Tuesday at 10:00
- Then the campaign moves to `Scheduled`, the send job is queued for that datetime, and content editing is blocked until the job finishes or is cancelled

### Time-zone aware sending
- Scheduling shall capture a timezone so the system stores the canonical UTC send time and executes the send at the intended local time for the selected zone.
#### Scenario: Send at 9 AM Eastern
- Given a marketer schedules a campaign for 9:00 AM America/New_York
- When the system saves the schedule
- Then it records the UTC equivalent, queues the send for that UTC timestamp, and displays the original timezone to users reviewing the schedule

### Test email capability
- The system shall provide a test send that uses the campaign template and the linked test target list (or ad hoc addresses) without affecting campaign metrics or status.
#### Scenario: Send a test email
- Given an email campaign with a selected template and test target list
- When the marketer clicks “Send Test” and chooses themselves and one teammate
- Then the system sends the rendered email to only those recipients, logs the test event, and leaves the campaign status and main schedule unchanged
