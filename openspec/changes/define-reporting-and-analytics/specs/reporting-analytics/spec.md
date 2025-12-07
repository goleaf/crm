# Reporting & Analytics Requirements

## ADDED Requirements

#### Requirement 1: Sales reports with grouping, filters, and time buckets
- Scenario: A manager runs a sales report grouped by owner and month for the last quarter, filtering status = Paid and currency = USD; the report returns total revenue, count of orders, and average order value per owner per month, with the applied filters and date range shown on the report header.

#### Requirement 2: Lead reports for funnel and conversion tracking
- Scenario: A marketing analyst builds a lead report grouped by status and source for the past 30 days; the output shows counts per status/source, conversion rate from New → Qualified, and average time-to-qualification with drill-down links to the underlying leads.

#### Requirement 3: Deal pipeline reports with weighted amounts and stages
- Scenario: A sales ops user selects the “Pipeline” dataset, filters to team = Enterprise and close date this quarter, and groups by stage; the report returns deal count, sum of amount, and weighted amount (amount * probability) per stage, plus total pipeline and weighted pipeline totals.

#### Requirement 4: Activity reports for productivity
- Scenario: A sales leader pulls an activity report grouped by user and week, filtering activity type in [Calls, Meetings, Tasks]; the report shows counts per activity type, total completed, and total duration for calls/meetings where available, enabling productivity comparison across the team.

#### Requirement 5: Custom report builder with saved definitions
- Scenario: A user opens the custom builder, selects dataset = Deals, dimensions = [Owner, Stage], measures = [Count, Weighted Amount], filter = close date this month and owner in their team, and saves the report as “Team Deals - This Month” with visibility = team; later, another teammate opens the saved report and sees the same definition and results without rebuilding it.

#### Requirement 6: Report exports with metadata
- Scenario: From any report, the user clicks Export → CSV; the system generates a CSV with the report rows and a header section that lists report name, generated at timestamp, applied filters, and date range. XLSX export preserves numeric types; PDF (when available) shows the same data and metadata in a printable layout.

#### Requirement 7: Dashboard widgets from saved reports
- Scenario: An admin adds a dashboard widget pointing to the “Pipeline by Stage - This Quarter” report, chooses chart type = stacked bar and refresh interval = 1 hour; the widget renders the chart using the saved report definition, honors the viewer’s permissions, and refreshes data automatically on interval.
