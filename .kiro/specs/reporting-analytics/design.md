# Reporting & Analytics Design Document

## Overview

Reporting & Analytics provides ad-hoc and scheduled reporting, dashboards, and BI integration. It includes a report builder, charting, cross-module joins, calculated columns, scheduling, and SuiteCRM Analytics add-on integration with data warehouse/ETL support.

## Architecture

- **Report Builder**: Module-based field selection with drag-and-drop, conditions/filters, groupings, summaries, charts, prompts, saved views, versioning, permissions, folders.
- **Scheduling & Delivery**: Scheduled report generation with email delivery, exports (PDF/CSV/Excel), subscriptions, favorites.
- **Dashboard Integration**: Dashlets for charts/lists/reports, drill-down, interactive filters.
- **Analytics Add-on**: BI dashboards with Pentaho/CTools, data warehouse schema (fact tables for Leads/Cases/Opportunities/Campaigns/Sales/Activities), ETL processes, trend and geographic analysis.

## Components and Interfaces

### Reports
- Custom reports with conditions (AND/OR, date ranges), group by, summary calculations (count/sum/avg/min/max), sorting, charts (bar/line/pie), exports, scheduling, permissions, folders, templates, favorites, drill-down, prompts, saved views, sharing, versioning, real-time vs historical reporting, trend analysis, comparative reporting.

### Analytics Dashboards
- Pre-built reports (100+), interactive charts/graphs, KPI dashboards, trend visualization, custom dashboard creation, data export, scheduled generation, drill-down, geographic analysis, team/user performance, call/email engagement metrics, lead conversion, CLV.

### Data Pipeline
- Data warehouse integration, ETL transforms, dimensional modeling, fact tables, time-based analysis, Pentaho integration, CTools support, report designer, data transformation, export capabilities.

## Data Models

- **ReportDefinition**: module(s), fields, filters, groupings, summaries, chart config, prompts, permissions, folder, schedule, version, owner, favorite flag.
- **ReportSchedule**: report_id, format (PDF/CSV/Excel), recipients, cadence, next_run, last_run, status.
- **Dashboard**: layout, dashlets, filters, shares, templates.
- **AnalyticsConfig**: warehouse connection, ETL jobs, fact table mappings, prebuilt reports catalog.

## Correctness Properties

1. **Filter accuracy**: Reports honor all filters/conditions, including AND/OR, date ranges, and prompts.
2. **Aggregation correctness**: Groupings and summary calculations match the underlying data with correct totals and chart values.
3. **Permission enforcement**: Report results and dashboards respect module/field permissions and team scoping.
4. **Scheduling reliability**: Scheduled reports generate and deliver on time without duplication and track status.
5. **Export fidelity**: PDF/CSV/Excel exports contain identical data and formatting per definition.
6. **Drill-down consistency**: Drilling from dashboards to underlying reports preserves filters/context.
7. **Warehouse synchronization**: ETL jobs produce consistent fact tables; analytics dashboards reflect up-to-date warehouse data.

## Error Handling

- Validate report definitions (fields, filters, joins) before save; fail fast on invalid configurations.
- Schedule failures logged with retry/backoff; notify owners.
- Guard against heavy queries with limits/pagination and warn for large exports.
- ETL job failures logged with lineage; prevent stale data indicators.

## Testing Strategy

- **Property tests**: Filter correctness, aggregation math, permission filtering, schedule idempotence, export equivalence, drill-down context retention, ETL sync accuracy.
- **Unit tests**: Condition parser, aggregation calculator, exporter (PDF/CSV/Excel), scheduler, prompt resolver.
- **Integration tests**: Cross-module reports, dashboard drill-down, scheduled delivery, analytics warehouse refresh, pre-built report validation.
