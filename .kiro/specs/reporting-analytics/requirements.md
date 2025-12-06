# Requirements: Reporting & Analytics

## Introduction

Defines reporting, dashboards, scheduling, and analytics capabilities including SuiteCRM Analytics add-on.

## Glossary

- **Dashlet**: Widget displayed on dashboards.
- **ETL**: Extract, Transform, Load processes for warehouse sync.

## Requirements

### Requirement 1: Report Builder
**User Story:** As an analyst, I create custom reports with charts and exports.
**Acceptance Criteria:**
1. Select fields across modules with drag-and-drop; configure filters (AND/OR, date ranges), prompts, saved views, templates, and favorites.
2. Group results and calculate summaries (count/sum/avg/min/max); add charts (bar/line/pie); sort results.
3. Export to PDF/CSV/Excel; schedule delivery via email; manage folders, permissions, sharing, versioning.
4. Support drill-down, real-time and historical reporting, trend/comparative analysis.

### Requirement 2: Dashboard Integration
**User Story:** As a manager, I monitor KPIs on dashboards.
**Acceptance Criteria:**
1. Add report/list/chart dashlets with drag-and-drop layouts, templates, multiple pages, personal/team dashboards, sharing.
2. Provide interactive filters, favorites, and drill-down to underlying reports.
3. Include activity streams, recent items, and upcoming activities where relevant.

### Requirement 3: Scheduling & Delivery
**User Story:** As a user, I receive reports automatically.
**Acceptance Criteria:**
1. Configure report schedules with cadence, recipients, formats (PDF/CSV/Excel), and permissions.
2. Track schedule history/status and handle retries; prevent duplicate deliveries.
3. Allow subscriptions and favorites for quick access.

### Requirement 4: Analytics Add-on
**User Story:** As a BI consumer, I use prebuilt analytics and warehouse data.
**Acceptance Criteria:**
1. Provide BI dashboards with interactive charts, KPIs, trends, geographic and team/user performance metrics.
2. Include pre-built reports (100+) across Leads/Cases/Opportunities/Campaigns/Sales/Activities; allow custom dashboard creation.
3. Integrate with warehouse/ETL (Pentaho/CTools), including fact tables, time-based analysis, and exports.
4. Support scheduled generation and data refresh indicators.
