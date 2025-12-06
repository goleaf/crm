# Marketing & Campaign Management Design Document

## Overview

Marketing & Campaign Management covers omnichannel campaigns, target/segment management, attribution, tracking, and surveys. The goal is to plan, execute, and measure campaigns across email and offline channels with deliverability controls, analytics, and audience management.

## Architecture

- **Campaign Engine**: Supports email and non-email campaigns, budgeting, expected vs actual revenue/cost, ROI, scheduling, cloning, archiving.
- **Audience Layer**: Target Lists (static/dynamic), Targets (prospects), cross-module inclusion (Accounts/Contacts/Leads/Users).
- **Content & Delivery**: Email templates (HTML), tracker URLs, opt-out/unsubscribe, bounce handling, time-zone aware sending, test sends.
- **Analytics**: Click/open tracking, response tracking, dashboards, attribution (lead source, multi-touch), comparison reports.
- **Surveys**: Designer with question types, templates, scheduling, distribution via campaigns, response collection and analysis.

## Components and Interfaces

### Campaigns
- Create/manage campaigns with types/statuses, budget vs actual costs, expected revenue, ROI calculation, scheduling, cloning, archiving.
- Tracker URLs, click-through and response tracking, deliverability/bounce handling, unsubscribe management, performance dashboards.

### Target Lists
- Default/Test/Suppression lists; manual and dynamic creation; segmentation and import/export.
- Cross-module membership (Accounts, Contacts, Leads, Targets, Users); merge and deduplicate; list size tracking and archiving.

### Targets
- Prospect records with minimal contact data, DNC/opt-out flags, source tracking, conversion to Leads/Contacts, import and dedupe.

### Surveys
- Survey builder with multiple question types, templates, scheduling, anonymous responses, branching/logic, required fields, preview, URL generation/embedding.
- Response collection, completion tracking, response rate reporting, analytics.

## Data Models

- **Campaign**: name, type, status, budget, expected_revenue, actual_cost, start/end dates, schedule, ROI metrics, tracker URLs, deliverability settings.
- **TargetList**: name, type, segmentation rules, membership associations, size, archive flag.
- **Target**: basic contact fields, status, source, opt-out/DNC, list membership.
- **Survey**: title, questions (type/options/validation), templates, schedule, anonymity flag, logic, URL, response sets with analytics.

## Correctness Properties

1. **Audience integrity**: Target lists include only entities matching segmentation rules; suppression lists exclude members from sends.
2. **Tracker accuracy**: Campaign tracker URLs record clicks/opens and attribute responses to the correct campaign and target.
3. **ROI calculation**: ROI metrics use expected revenue, actual cost, and responses to compute accurate ROI and conversion rates.
4. **Send scheduling**: Campaign sends honor time-zone aware schedules and test sends without contacting suppression lists.
5. **Bounce and opt-out compliance**: Bounces and unsubscribes are recorded and prevent further sends to affected targets.
6. **Survey logic enforcement**: Branching/required questions are respected; responses are stored with timestamps and completion status.
7. **Import/export fidelity**: Target and list imports/exports preserve membership and flags; dedupe rules prevent duplicates.

## Error Handling

- Validate campaign dates, budgets, list selection, and template syntax before send.
- Handle email delivery/bounce webhooks and log failures; quarantine problematic targets.
- Survey response validation on required questions and question types.
- Import error logging with preview and rollback on malformed data.

## Testing Strategy

- **Property tests**: Segmentation accuracy, suppression enforcement, tracker attribution, ROI math, scheduling boundaries, bounce/unsubscribe effects, survey branching.
- **Unit tests**: List membership services, tracker URL generation, ROI calculator, survey renderer/validator.
- **Integration tests**: Campaign send pipeline with bounces/unsubscribes, survey distribution/collection, import/export flows, analytics dashboards.
