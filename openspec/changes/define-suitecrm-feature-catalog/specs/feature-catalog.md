# SuiteCRM Feature Catalog

## ADDED Requirements

#### Requirement 1: Core CRM module features are cataloged.
- The catalog lists Accounts features (company information management, hierarchies, addresses, types, industries, revenue, employees, custom fields, teams, relationships, multi-currency, web/social links, ownership/assignment, activity history, document attachments) and includes detailed acceptance for Company information management (required fields, address handling, controlled dropdowns, numeric validation, parent hierarchy updates, URL/phone/email validation, attachment metadata, timeline of core field changes).
- The catalog lists Contacts features (person tracking, emails, phones, addresses, job/dept, reporting structure, birthdays/assistants, account relationships, lead source, portal, sync, social links, custom fields, segmentation).
- The catalog lists Leads features (capture, source, status, scoring/grading, qualification, conversion, distribution/assignment including round-robin and territory, nurturing, activity tracking, duplicate detection, import/export, web-to-lead).
- The catalog lists Opportunities features (pipeline, sales stages, amount/weighted revenue, probability, expected close, cycle tracking, competitors, win/loss, next steps, collaboration, multi-user, related quotes, forecasting integration, dashboards).
- The catalog lists Cases features (support tickets, assignment/routing, statuses, priorities, types, SLAs, escalations, resolution tracking, response time, portal and knowledge base integration, threading, email-to-case, queues, team assignment).
#### Scenario: Review core CRM catalog
- Given the catalog document
- When a reader opens the Core CRM section
- Then they see the Accounts, Contacts, Leads, Opportunities, and Cases bullet lists including the detailed acceptance for company information management under Accounts

#### Requirement 2: Sales and revenue features are cataloged.
- The catalog lists Quotes features (quote generation, product catalog integration, line items, pricing/discounts, tax/shipping, versioning, PDF templates, approvals, expiration, status, bundle/group pricing, currency, terms).
- The catalog lists Products features (catalog, categories/subcategories, manufacturer and type, cost/list pricing, discount rules, status, SKU/part numbers, descriptions, images/attachments, inventory, bundles, cross/upsell, lifecycle, custom fields).
- The catalog lists Contracts features (management, types, start/end/renewal dates, value, status, terms, templates, auto-renewal, expiration notifications, amendments, SLAs, approvals, document storage, relationship tracking).
- The catalog lists Forecasts features (forecasting by period, revenue predictions, pipeline analysis, categories, rollups, time-based forecasting, quotas, worksheets, history, adjustments, forecast vs actual, reporting, multi-currency, exports).
- The catalog lists Invoice extension features (creation/tracking, numbering, line items, payments, statuses, templates, taxes, due dates, payment terms, late tracking, PDF generation, multi-currency, recurring invoices, reminders, history).
#### Scenario: Review sales and revenue catalog
- Given the catalog document
- When a reader opens the Sales and Revenue Management section
- Then they see the bullet lists for Quotes, Products, Contracts, Forecasts, and Invoices as described

#### Requirement 3: Marketing and campaign features are cataloged.
- The catalog lists Campaigns features (email and non-email campaign creation, budgeting, expected/actual revenue, ROI, status lifecycle, type categorization, target list integration, email template design with HTML, wizard, scheduling, timezone, test sends).
- The catalog lists Campaign continuation features (tracker URLs, click-through, opt-out management, response tracking, real-time metrics, analytics dashboard, lead source attribution, comparisons, multi-touch attribution, cloning, archiving, bounce handling, unsubscribe management, deliverability tracking, performance reports).
- The catalog lists Target Lists features (distribution lists, manual/dynamic creation, segmentation, import/export, merge, dedupe, types default/test/suppression, membership tracking/size, cross-module targeting, bulk add, archiving, relationship tracking, subscriber status).
- The catalog lists Targets features (prospect tracking, minimal data, campaign association, list membership, conversion, status, DNC/opt-out, source tracking, deletion, reusability, import, deduplication, activity history).
- The catalog lists Surveys features (creation/design, question types, templates, distribution via campaigns, anonymous responses, scheduling, response collection, results analysis, response/completion tracking, logic/branching, required settings, preview, URL generation, embedding).
#### Scenario: Review marketing catalog
- Given the catalog document
- When a reader opens the Marketing and Campaign Management section
- Then they see the bullet lists for Campaigns, Campaign Features, Target Lists, Targets, and Surveys

#### Requirement 4: Communication and collaboration features are cataloged.
- The catalog lists Emails features (client integration, inbound/outbound, templates, archiving, threading, HTML support, attachments, signatures, folders, search, assignment, group boxes, email-to-record association, tracking).
- The catalog lists Email continuation features (drafts, scheduling/send later, forwarding, reply/reply-all, CC/BCC, importance flags, read receipts, analytics, bounce handling configuration, SMTP/IMAP/POP3, OAuth including Microsoft and Gmail integration).
- The catalog lists Calls features (logging, scheduling, inbound/outbound classification, duration, status, purpose, outcome, reminders, participants, related records, history, notes, follow-up tasks, VOIP integration, click-to-dial).
- The catalog lists Meetings features (scheduling, location, attendees internal/external, reminders, statuses, duration, recurring, notes, outcomes, calendar integration, room booking, video conference integration, agenda, minutes).
- The catalog lists Calendar features (unified view with day/week/month/year, scheduling, sharing, shared/team calendars, color-coded events, drag/drop reschedule, Google/Outlook sync, iCal export, permissions, printing, quick create, filters/search).
- The catalog lists Tasks features (creation/assignment, priority, status, due dates, reminders, relationships, subtasks/dependencies/checklists, comments, time tracking, delegation, recurring, categories, filtering/sorting).
- The catalog lists Notes features (creation on any record, rich text, attachments, privacy, timestamps, author tracking, search, categories, relationships/history, shared notes, printing/export, templates, internal vs external).
#### Scenario: Review communication catalog
- Given the catalog document
- When a reader opens the Communication and Collaboration section
- Then they see the bullet lists for Emails (and continuation), Calls, Meetings, Calendar, Tasks, and Notes

#### Requirement 5: Project and resource management features are cataloged.
- The catalog lists Projects features (creation/tracking, status, templates, task assignment/tracking, timelines/milestones, team management, resource allocation, budgeting, time tracking, progress, Gantt via extensions, dependencies, deliverables, documentation, reporting, phases, risk/issues, dashboards).
- The catalog lists Project Tasks features (breakdown structure, dependencies, duration estimation, percentage complete, priorities, predecessors/successors, critical path, assignments, comments, time logging/billing, templates, milestones, notifications, reporting).
- The catalog lists Employees features (directory, information management, contact details, department, reporting structure, employment status, job/role, start date, emergency contacts, portal access, skills/certifications, performance, documents, payroll integration, time-off).
#### Scenario: Review project catalog
- Given the catalog document
- When a reader opens the Project and Resource Management section
- Then they see the bullet lists for Projects, Project Tasks, and Employees

#### Requirement 6: Knowledge and document management features are cataloged.
- The catalog lists Documents features (repository, upload/storage, categorization, version control, statuses Active/Draft/FAQ, types, templates, relationships, search, preview, download, sharing, permissions, expiration, folders, tags, approval workflows, check-in/out, metadata, cloud storage integration).
- The catalog lists Knowledge Base features (article creation, categories, statuses, approval workflows, versioning, search, rating, comments, FAQ management, solution database, template responses, tags, permissions, analytics, related linking, export, public/internal, attachments, SEO, portal integration).
- The catalog lists Bugs features (tracking/logging, statuses, priorities, severity, types, source tracking, product/component association, version affected, assignment, resolution, verification, release notes, relationships/dependencies, comments/history, attachments, duplicate detection, reporting, search/filtering, notifications, developer integration).
#### Scenario: Review knowledge/document catalog
- Given the catalog document
- When a reader opens the Knowledge and Document Management section
- Then they see the bullet lists for Documents, Knowledge Base, and Bugs

#### Requirement 7: Workflow and automation features are cataloged.
- The catalog lists Workflows (process creation, triggers create/edit/after save, multiple conditions with operators and types, scheduled/time-based triggers, actions create/modify/send email, unlimited actions, calculations, status active/inactive, repeat options, testing, logs/audit).
- The catalog lists Workflow Actions (create/modify records, email notifications, tasks/calls/meetings creation, field updates, status/assignment changes, relationship creation, validation, record locking, approvals, escalations, SLA enforcement).
- The catalog lists Workflow Calculated Fields (formula calculations, math/date/time/text/conditional, field references, cross-module, rollups, counts/sum/avg/min/max, custom formulas, real-time and scheduled recalculation).
#### Scenario: Review workflow catalog
- Given the catalog document
- When a reader opens the Workflow and Automation section
- Then they see the bullet lists for Workflows, Workflow Actions, and Workflow Calculated Fields

#### Requirement 8: Reporting and analytics features are cataloged.
- The catalog lists Reports (custom report builder, drag/drop fields, module and cross-module reporting, conditions/filters with AND/OR and date ranges, group by, summary calcs, sorting, charts, export PDF/CSV/Excel, scheduling/email delivery, permissions, templates, folders/favorites, dashboard integration).
- The catalog lists Report continuation features (drill-down, prompts/dynamic filters, saved views, sharing, versioning, real-time/historical/trend/comparative reporting, custom fields/columns, formatting/printing, subscriptions).
- The catalog lists SuiteCRM Analytics add-on features (BI dashboards, data warehouse integration, pre-built reports, interactive charts, sales/campaign/case/activity metrics, ROI, forecasting, KPIs, trends, custom dashboards, export, scheduling).
- The catalog lists Analytics continuation features (Pentaho/CTools, report designer, data transformation/ETL, dimensional modeling, fact tables, time/geographic analysis, team performance, user activity, call/email engagement, lead conversion, CLV).
#### Scenario: Review reporting catalog
- Given the catalog document
- When a reader opens the Reporting and Analytics section
- Then they see the bullet lists for Reports, Report Features, SuiteCRM Analytics, and Analytics Features

#### Requirement 9: Customization and administration features are cataloged.
- The catalog lists Studio features (module customization, field management/types, custom fields, layout customization, subpanels, labels, relationships, module builder, custom modules, deployment, templates, validation, dependencies).
- The catalog lists Module Builder features (custom modules, packages, deployment, templates Basic/Person/Company/Sale/File, field/relationship/layout designer, export/versioning, documentation/licensing/distribution/uninstall/publishing/updates).
- The catalog lists Developer Tools (module loader/installer/manager, dropdown editor, global dropdowns, language editor, display modules, tabs, module rename, rebuild/repair, diagnostics, DB management, cache/permission/index management).
- The catalog lists Admin Panel features (user management/status/types, password management/policies/expiration, LDAP/SAML/OAuth, 2FA, sessions, login history, activity tracking, bulk operations).
- The catalog lists Role Management (role creation/config, access levels, module/field permissions, action permissions, create permissions, admin/studio rights, inheritance/copying/templates, assignment to users/groups, permission matrix, audit trail).
- The catalog lists Security Suite/Groups (group creation/membership/inheritance, non-inheritable groups, group-based access, owner/group permissions, hierarchical models, custom layouts, dashlets, mass assignment/automation, record-level security, login-as, hookup tool, group fields/filtering, primary group, broadcast messaging).
#### Scenario: Review customization catalog
- Given the catalog document
- When a reader opens the Customization and Administration section
- Then they see the bullet lists for Studio, Module Builder, Developer Tools, Admin Panel, Role Management, and Security Suite/Groups

#### Requirement 10: Integration and API features are cataloged.
- The catalog lists API Access features (REST v8, Legacy v4.1, JSON, OAuth2, documentation/OpenAPI, rate limiting/versioning, webhooks, permissions/logging/error handling, custom endpoints, third-party and mobile app support).
- The catalog lists External Integrations (Gmail/Google Calendar/Drive/Maps, Outlook/Exchange/Office 365, Microsoft OAuth, Mailchimp/SendGrid/Mautic/Constant Contact/Active Campaign, Twilio, Zoom, Slack, WooCommerce/Shopify/Magento, Xero/QuickBooks, Zapier/Make).
- The catalog lists Email Platform Integrations (SMTP/IMAP/POP3, bounce handling, inbound routing, archiving, tracking pixels/open/click tracking, deliverability monitoring, spam filter management, SPF/DKIM/DMARC, queuing/batch/throttling, blacklist management).
#### Scenario: Review integration catalog
- Given the catalog document
- When a reader opens the Integration and API section
- Then they see the bullet lists for API Access, External Integrations, and Email Platform Integrations

#### Requirement 11: Mobile and portal features are cataloged.
- The catalog lists Mobile Access features (responsive/mobile-optimized UI, touch controls, navigation, dashboards, list views, record editing/search, calendar, tasks, call logging, email access, offline via apps, notifications, location services, mobile app availability).
- The catalog lists Customer Portal features (self-service portal, login, case submission/tracking, knowledge base/FAQ access, document download, customization/branding, registration/password reset, search/notifications, multi-language, analytics).
#### Scenario: Review mobile/portal catalog
- Given the catalog document
- When a reader opens the Mobile and Portal section
- Then they see the bullet lists for Mobile Access and Customer Portal

#### Requirement 12: Data management and search features are cataloged.
- The catalog lists Import/Export features (CSV/Excel/vCard import, duplicate checking, field mapping/validation, preview/error handling/history; CSV/Excel export with templates, selective fields, mass export, list view export).
- The catalog lists Data Management features (duplicate detection/merging, cleanup tools, bulk updates/delete/assignment, archiving, backup, repair tools, relationships, integrity checks, validation/required fields, encryption, GDPR tools).
- The catalog lists Search features (global/module-specific/advanced/full-text, Elasticsearch, filters, saved searches, quick filters, operators/wildcards/boolean, history/suggestions, ranking, cross-module search).
#### Scenario: Review data/search catalog
- Given the catalog document
- When a reader opens the Data Management section
- Then they see the bullet lists for Import/Export, Data Management Features, and Search Features

#### Requirement 13: User interface and experience features are cataloged.
- The catalog lists Themes (SuiteP and sub-themes, custom theme creation/customization, color/logo/CSS, inheritance, responsive/dark mode, preview/export/import, per-user selection, builder tools, SASS).
- The catalog lists Dashboard/Home Page features (customizable dashboards, dashlets, drag/drop layout, multiple pages/templates, personal/team dashboards, charts/list/report dashlets, RSS, activity stream, recent items, upcoming activities, sharing).
- The catalog lists Navigation features (top menu/module tabs/dropdowns, quick create, favorites/recent, breadcrumbs, search bar, user menu, notifications center, action/bulk/contextual menus, keyboard shortcuts, navigation history).
- The catalog lists Views (list/detail/edit/quick create/popup/subpanel/dashboard/calendar/timeline/kanban/map/grid/card/split/preview).
- The catalog lists UX features (inline editing, auto-save, undo/redo, validation messages, error/success notifications, loading/progress indicators, tooltips/help/contextual help, desktop/browser/email notifications, popup alerts).
#### Scenario: Review UI/UX catalog
- Given the catalog document
- When a reader opens the User Interface and Experience section
- Then they see the bullet lists for Themes, Dashboard/Home, Navigation, Views, and UX features

#### Requirement 14: System and technical features are cataloged.
- The catalog lists System Administration features (settings, company info, locale/date/time, currency/multi-currency/exchange rates, fiscal year, business hours/holidays, email settings/accounts, notifications, scheduler/cron).
- The catalog lists Performance/Optimization features (query/indexing/cache/image optimization, JS/CSS minification, lazy loading, pagination/limits, memory/session/connection pooling, load balancing, CDN, performance monitoring).
- The catalog lists Logging/Debugging features (system/error/slow query/email/import/export/workflow/api/auth logs, debug mode, log levels, rotation/archiving/analysis, error reporting).
- The catalog lists Security features (auth, password encryption, SSL/TLS, session security, CSRF/XSS/SQLi protection, upload restrictions, IP allow/deny, brute force protection/login throttling/lockout, audits, vulnerability scanning, patch management).
- The catalog lists Backup/Recovery features (DB/file backups, automation/scheduling/compression, incremental, verification, disaster recovery/PITR, restoration, migration tools, upgrade utilities, rollback, version control/change tracking).
#### Scenario: Review system catalog
- Given the catalog document
- When a reader opens the System and Technical section
- Then they see the bullet lists for System Administration, Performance/Optimization, Logging/Debugging, Security Features, and Backup/Recovery

#### Requirement 15: Advanced feature sets are cataloged.
- The catalog lists Process Management features (definition/automation/monitoring/optimization, approvals/escalations/SLA, rules engine, event-driven automation, analytics, templates/versioning/documentation, compliance/audit trails).
- The catalog lists Advanced Customization features (logic hooks, custom code, extension framework/plugins, entry points/views/controllers/metadata/vardefs/language strings/schedulers/dashlets/modules/relationships/calculations).
- The catalog lists PDF Management features (templates, dynamic generation, email attachments, customization/merge fields/layout/styling/watermarks/permissions/encryption, e-sign via extensions, archiving/versioning, multipage/forms).
- The catalog lists Territory Management features (territory definitions by geography/product, hierarchies, assignment rules/access/quotas/reporting/balancing/overlap handling/permissions/transfers/analytics/forecasting, multi-territory assignment).
- The catalog lists Advanced Email Features (drip/nurture/automation/personalization/dynamic content/A-B testing/scoring/unsubscribe/bounce/deliverability optimization/templates with variables/conditional sending/tracking/analytics/queue/archiving).
#### Scenario: Review advanced catalog
- Given the catalog document
- When a reader opens the Advanced Features section
- Then they see the bullet lists for Process Management, Advanced Customization, PDF Management, Territory Management, and Advanced Email Features
