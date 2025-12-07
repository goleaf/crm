# Forecasting Requirements

## ADDED Requirements

#### Requirement 1: Forecasts align to selectable time periods with pipeline-based revenue predictions.
- Scenario: A sales manager opens the forecast for Q3 2025 and selects monthly granularity; the worksheet filters opportunities whose expected close dates fall inside the slice, calculates weighted revenue predictions for each month, and plots pipeline analysis for every period without requiring manual data refresh.

#### Requirement 2: Forecast categories drive best-case and commit scenarios.
- Scenario: A sales rep updates an opportunity's forecast category to "Commit" from "Best Case"; the worksheet immediately recalculates commit vs best-case totals, excludes any "Omitted" deals from rollups, and refreshes the scenario columns so the rep can see the impact on their pipeline analysis.

#### Requirement 3: Forecast rollups aggregate by user and team while tracking quotas.
- Scenario: A regional manager views the rollup for their team; the system aggregates subordinate worksheets (including shared/team-owned opportunities), compares commit and best-case totals to assigned quotas for the selected period, and surfaces coverage metrics (attainment percentage and gap) per user and for the team overall.

#### Requirement 4: Forecast worksheets support inline adjustments with multi-currency handling.
- Scenario: A rep adjusts a worksheet row to increase the commit amount and adds a note explaining the change; the system records the adjustment separately from the opportunity amount, converts the local currency to the corporate currency for rollups, and keeps both original and adjusted values so managers can audit changes and apply top-down adjustments without overwriting deal data.

#### Requirement 5: Historical submissions enable forecast vs actual comparisons.
- Scenario: When a rep submits their forecast, the system captures a snapshot of commit, best-case, pipeline totals, quotas, and adjustments; after the period closes, the snapshot is compared to actual closed-won revenue, producing variance metrics and a history view that shows how forecasts trended across submissions.

#### Requirement 6: Reporting and exports deliver forecast insights across periods.
- Scenario: A revenue operations user runs a forecast report that charts commit vs quota by month, lists pipeline coverage by category, and exports both the worksheet and rollup views to CSV/XLSX (including currency codes and exchange rates) so the data can be shared or analyzed offline.
