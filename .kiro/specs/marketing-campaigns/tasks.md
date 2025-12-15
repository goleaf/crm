# Implementation Plan: Marketing & Campaign Management

- [ ] 1. Campaign engine
  - Build campaign resource with budgeting, expected/actual metrics, ROI calculator, statuses, cloning, scheduling, archiving.
  - Add tracker URL generation, click/open/response logging, dashboards.
  - _Requirements: 1.1-1.5_
  - **Property 2: Tracker accuracy**, **Property 3: ROI calculation**, **Property 4: Send scheduling**

- [ ] 2. Content and deliverability
  - Implement email templates (HTML), test sends, unsubscribe links, bounce handling, deliverability tracking.
  - _Requirements: 1.3-1.4_
  - **Property 5: Bounce and opt-out compliance**

- [ ] 3. Target lists
  - Create list resource for default/test/suppression; support manual/dynamic segments, merge/dedupe, import/export, membership size tracking.
  - Enforce suppression during sends.
  - _Requirements: 2.1-2.4_
  - **Property 1: Audience integrity**, **Property 7: Import/export fidelity**

- [ ] 4. Targets
  - Implement target model with DNC/opt-out flags, source/status, import/dedupe tools, conversion to leads/contacts with history preservation.
  - _Requirements: 3.1-3.4_

- [ ] 5. Surveys
  - Build survey designer (question types, templates, branching, required fields, preview), scheduling, URL/embedding; connect to campaigns.
  - Capture responses, completion tracking, analytics, exports, anonymous mode.
  - _Requirements: 4.1-4.4_
  - **Property 6: Survey logic enforcement**

- [ ] 6. Testing
  - Property tests for segmentation, suppression, tracker attribution, ROI math, scheduling boundaries, bounce/unsubscribe behavior, survey branching.
  - Integration tests for send pipeline, survey distribution/collection, import/export flows.
  - _Requirements: all_
