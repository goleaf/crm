# Core CRM Modules Design Document

## Overview

The Core CRM Modules deliver the foundational data model and workflows for SuiteCRM-style functionality: Accounts, Contacts, Leads, Opportunities, and Cases. Each module must interoperate to provide a unified customer view, support sales and service lifecycles, and maintain auditability across creation, updates, conversions, and relationship management.

The solution assumes the existing Laravel + Filament stack, reusing the patterns from current resources, services, and custom field infrastructure. Multi-currency, attachment support, activity timelines, and parent-child relationships are handled consistently across modules.

## Architecture

- **UI Layer**: Filament resources for each module (tables, forms, actions, dashboards).
- **Domain Layer**: Eloquent models with traits for custom fields, soft deletes, media, team scoping, and activity history.
- **Services**: Lead conversion, opportunity forecasting, case SLA/escalation, duplicate detection, assignment routing.
- **Workflows**: Trigger-based automation for lead nurture, case escalation, and opportunity updates.
- **Integrations**: Email-to-case, web-to-lead, portal access, and document attachments shared across modules.

### Cross-Module Interaction Flow

1. Lead captured (web-to-lead/import) → assigned via round-robin/territory rules.
2. Lead conversion wizard creates/links Contact + Account + Opportunity.
3. Account serves as hub for Contacts, Opportunities, Cases, Tasks, Notes.
4. Opportunities feed forecasting, quotes, and pipeline KPIs.
5. Cases use SLA/escalation rules and surface Knowledge Base links.

## Components and Interfaces

### Accounts
- **Purpose**: Company record with hierarchies, ownership, and activity history.
- **Responsibilities**: Parent-child accounts, billing/shipping addresses, industry/revenue/employee stats, custom fields, attachments, multi-currency, relationship mapping to Contacts/Opportunities/Cases/Tasks/Notes.

### Contacts
- **Purpose**: Individual people tied to accounts.
- **Responsibilities**: Multiple emails/phones, addresses, job/department, reporting structure, lead source, portal enablement, segmentation, social profiles, custom fields.

### Leads
- **Purpose**: Unqualified prospects.
- **Responsibilities**: Capture, source/status/score/grade, qualification steps, distribution (round-robin/territory), nurturing workflows, duplicate detection, import/export, web-to-lead.

### Opportunities
- **Purpose**: Revenue pipeline records.
- **Responsibilities**: Sales stages, probability, weighted revenue, expected close, competitors, next steps, win/loss analysis, dashboards, quotes linkage, forecasting integration, multi-user collaboration.

### Cases
- **Purpose**: Support tickets.
- **Responsibilities**: Status/priority/type, SLA tracking, escalation rules, resolution tracking, response time metrics, email-to-case, portal visibility, queue routing, knowledge base integration, threading.

## Data Models

- **Account**: name, account type, parent_id, billing/shipping addresses, industry, revenue, employee_count, ownership, website/social, currencies, assignment fields, activity relationships, documents.
- **Contact**: name, primary/alt emails, multiple phones, addresses, job/department, reports_to, birthday/assistant, portal flag, lead source, account_id, social profiles, custom fields.
- **Lead**: name/company, status, source, score/grade, rating, assigned_user, territory, contact info, web form origin, duplication hash, converted flags (contact_id/account_id/opportunity_id).
- **Opportunity**: name, stage, probability, amount, weighted_amount, expected_close, next_step, competitor list, team/users, quote_ids, forecast category, dashboards.
- **Case**: number, subject, status, priority, type, SLA metrics, escalation level, resolution, queue, portal flag, email thread id, knowledge_article_id.

## Correctness Properties

1. **Account persistence**: Creating/updating an account with required fields and custom data must persist and be retrievable with identical values.
2. **Bidirectional account-contact links**: Associating a contact to an account makes the relationship queryable from both sides and survives soft deletes.
3. **Lead distribution rules**: Round-robin and territory assignment must deterministically assign new leads according to configured rules without skips.
4. **Lead conversion integrity**: Converting a lead creates/links Account, Contact, Opportunity exactly once and marks the lead converted atomically.
5. **Opportunity pipeline math**: Weighted revenue equals amount * probability and totals roll up correctly to dashboards/forecasts.
6. **Opportunity stage progression**: Stage changes must honor allowed transitions and record probability/close date expectations.
7. **Case SLA enforcement**: Cases exceeding SLA thresholds must trigger escalation actions and timestamp breaches.
8. **Case queue routing**: Email-to-case or portal submissions must land in the correct queue/team based on rules (status/type/priority).
9. **Activity timeline completeness**: Accounts display full activity history (notes/tasks/opportunities/cases) sorted by most recent.
10. **Duplicate detection for leads/accounts**: Similar names/domains/phones surface as potential duplicates with similarity scores on create and import.

## Error Handling

- Validation failures for required fields, formats (email/phone/url), and enum values block persistence with user-friendly errors.
- Lead conversion, SLA updates, and assignment routing run in transactions; failures roll back all side effects.
- Email-to-case parsing and web-to-lead ingestion log malformed payloads and store them as quarantined items for retry.
- Duplicate detection failures fall back to a safe create while logging detection issues.

## Testing Strategy

- **Property tests**: Persistence, bidirectional relationships, lead distribution determinism, conversion atomicity, weighted revenue math, SLA breach triggers, duplicate detection scoring.
- **Unit tests**: Model accessors/casts, assignment services, conversion service, SLA/escalation service, duplicate detection heuristics.
- **Integration tests**: Lead conversion wizard, web-to-lead pipeline, email-to-case ingestion, account activity timeline rendering, opportunity forecast rollups.
- **Performance tests**: Query counts for activity timelines, duplicate search index usage, assignment rule evaluation under load.
