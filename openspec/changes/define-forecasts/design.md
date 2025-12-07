# Design Notes

## Data Model and Periodization
- Forecasts are scoped to discrete time buckets (monthly, quarterly, yearly) so pipeline inclusion is deterministic; a `forecast_periods` dimension paired with close dates and fiscal calendars drives the worksheet filters.
- Categories (Pipeline, Best Case, Commit, Omitted) live alongside opportunity states and are stored on forecast entries to decouple worksheet overrides from raw opportunity amounts; commit/best-case buckets can be recalculated without mutating the underlying deal.
- Quotas attach to users/teams per period and are stored in a dedicated table so rollups can align quota, commit, and best-case numbers without recomputing static goals.

## Rollups, Worksheets, and Adjustments
- Worksheets materialize opportunity rows per owner and period, caching both raw and weighted amounts to fuel quick pipeline analysis, revenue predictions, and scenario toggling (commit vs best case).
- Manager rollups aggregate subordinate worksheets based on ownership or security groups and allow additive or subtractive adjustments recorded separately from deal-level changes; adjustments store author, timestamp, reason, delta, and currency to preserve auditability.
- Historical snapshots capture submitted worksheets (commit, best case, pipeline, quota, adjustments) each time a user submits, enabling trend lines, versioned comparisons, and the forecast-vs-actual check after the period closes.

## Currency and Reporting
- Each forecast entry and adjustment carries both the user's working currency and a normalized corporate currency value using stored exchange rates, allowing multi-currency worksheets with accurate team rollups.
- Reporting surfaces reuse snapshot data to generate tables and charts (by period, category, user/team) and share a common export adapter that can emit CSV/XLSX for worksheets, rollups, and comparison views without recalculating totals.
