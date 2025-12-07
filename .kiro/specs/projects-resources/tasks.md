# Implementation Plan: Project & Resource Management

- [x] 1. Project foundation
  - Build project model/resource with statuses, phases, milestones, templates, risks/issues, documentation, dashboards, Gantt export hook.
  - _Requirements: 1.1-1.3_
  - **Property 5: Template consistency**

- [x] 2. Task engine
  - Implement hierarchical tasks with dependencies, durations, priorities, % complete, milestones, assignments, notifications, comments.
  - Add recurrence/time logging/billing support and templates.
  - _Requirements: 2.1-2.3_
  - **Property 1: Dependency enforcement**, **Property 2: Progress accuracy**, **Property 6: Time logging integrity**

- [x] 3. Resource management
  - Create employee directory with departments/roles/managers/status, skills/certifications, documents, performance fields, time-off tracking, portal flag.
  - _Requirements: 3.1-3.3_
  - **Property 3: Resource allocation**

- [x] 4. Budgeting and costs
  - Connect time logs and billing to project budgets; surface budget vs actuals, overruns, and exports.
  - _Requirements: 4.1-4.3_
  - **Property 4: Budget adherence**

- [x] 5. Scheduling and reporting
  - Compute critical path, generate timelines, integrate Gantt (extension), and provide reports/dashboards for progress and risks.
  - _Requirements: 1.2, 2.3_
  - **Property 1: Dependency enforcement**, **Property 2: Progress accuracy**

- [x] 6. Access control and safety
  - Enforce permissions for projects/tasks; handle soft deletes with reassignment prompts; validate time logs for overlaps.
  - _Requirements: all_
  - **Property 7: Access control**

- [x] 7. Testing
  - Property tests for dependencies, critical path, percent complete rollups, allocation limits, budget vs actuals, template cloning, time log uniqueness.
  - Integration tests for project-from-template, over-allocation alerts, time-off impact on scheduling, exports.
  - _Requirements: all_
