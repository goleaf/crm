# Implementation Plan: Core CRM Modules

- [x] 1. Data model extensions
  - Add required fields for Accounts (hierarchies, addresses, industry, revenue, employee_count, ownership, website/social, multi-currency).
  - Ensure Contacts support multi-email/phone, reporting structure, portal flags, and segmentation fields.
  - Enhance Leads with score/grade/status/source, routing metadata, duplication hashes, and conversion flags.
  - Add Opportunity fields for stage/probability/amount/weighted_amount/expected_close/competitors/next_steps.
  - Expand Cases with SLA timers, escalation metadata, queue assignment, threading ids, and portal visibility flags.
  - _Requirements: 1.1, 2.1, 3.1, 4.1, 5.1_

- [x] 2. Relationship wiring
  - Implement bidirectional Account↔Contact, Account↔Opportunity, Account↔Case, and activity relationships with soft-deletes.
  - Support many-to-many contact-account links and parent-child accounts.
  - _Requirements: 1.2, 2.2, 6.1_

- [x] 3. Duplicate detection
  - Add services for fuzzy matching on name/domain/phone for Accounts and Leads with similarity scoring.
  - Surface detections on create/import flows with user prompts.
  - _Requirements: 1.5, 3.4_
  - **Property 10: Duplicate detection for leads/accounts**

- [x] 4. Lead routing and conversion
  - Implement round-robin/territory assignment services with audit logs.
  - Build conversion wizard to atomically create/link Account, Contact, Opportunity; update lead status.
  - _Requirements: 3.2, 3.5_
  - **Property 3: Lead distribution rules**, **Property 4: Lead conversion integrity**

- [x] 5. Opportunity management
  - Configure stage pipelines, probability defaults, weighted revenue calculations, and dashboards/forecasts integration.
  - Enforce stage transition rules and close reasons; add competitor tracking.
  - _Requirements: 4.1, 4.3, 4.4_
  - **Property 5: Opportunity pipeline math**, **Property 6: Opportunity stage progression**

- [ ] 6. Case management
  - Implement SLA timers, breach detection, escalation workflows, queue routing, and knowledge base linkage.
  - Add email-to-case and portal intake handlers with threading and assignment.
  - _Requirements: 5.1, 5.2, 5.3_
  - **Property 7: Case SLA enforcement**, **Property 8: Case queue routing**

- [ ] 7. Activity timeline
  - Build consolidated timeline on Account with notes/tasks/opportunities/cases; support filters and permissions.
  - _Requirements: 6.1-6.5_
  - **Property 9: Activity timeline completeness**

- [ ] 8. Validation and error handling
  - Add format validation (email/phone/url), enum guards, and transactional safeguards for conversion and SLA updates.
  - _Requirements: all_
  - **Property 1: Account persistence**, **Property 2: Bidirectional account-contact links**

- [ ] 9. Testing
  - Property tests: persistence, relationship symmetry, routing determinism, conversion atomicity, weighted revenue math, SLA breach triggers, duplicate detection.
  - Integration tests: web-to-lead intake, conversion wizard, email-to-case ingestion, opportunity forecast rollup, activity timeline rendering.
  - _Requirements: all_
