- [x] 3. Meeting management
  - Create meeting resource with recurrence, attendees, reminders, agenda/minutes, video links, room booking; show in calendar.
  - _Requirements: 3.1-3.3_
  - **Property 7: Recurring rules**

- [x] 4. Unified calendar
  - Build shared/team calendar views (day/week/month/year), drag-and-drop, filters, search, iCal export, external sync (Google/Outlook).
  - _Requirements: 4.1-4.3_
  - **Property 1: Calendar sync fidelity**

- [x] 5. Task management
  - Implement tasks with priorities/statuses/due dates/reminders/recurrence/dependencies/subtasks/checklists/time tracking/delegation; enforce dependency rules.
  - _Requirements: 5.1-5.3_
  - **Property 4: Task workflow integrity**, **Property 7: Recurring rules**

- [x] 6. Notes
  - Add rich text notes with attachments, categories, privacy controls, history, search; link to any record and activity timelines.
  - _Requirements: 6.1-6.3_
  - **Property 6: Privacy enforcement**, **Property 3: Activity association**

- [x] 7. Activity layer and reminders
  - Ensure all activities associate to CRM records with permissions; build reminder delivery pipeline with idempotent jobs.
  - _Requirements: all_
  - **Property 2: Reminder delivery**, **Property 3: Activity association**

- [ ] 8. Testing
  - Property tests for calendar sync idempotence, reminder timing, recurring generation, dependency enforcement, privacy filters, email scheduling.
  - Integration tests for email-to-record, VOIP logging, calendar sync, meeting/video booking, unified timeline rendering.
  - _Requirements: all_
