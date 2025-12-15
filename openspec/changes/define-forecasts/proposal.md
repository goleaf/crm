# Proposal: define-forecasts

## Change ID
- `define-forecasts`

## Summary
- Translate the SuiteCRM Forecasts capability list into explicit OpenSpec requirements covering periodized sales forecasting, forecast categories and scenarios, rollups and quotas, worksheets and adjustments, historical tracking, and reporting/export expectations.

## Capabilities
- `forecast-periods-and-categories`: Define the time-based forecast periods, pipeline inclusion, and category/bucket logic for best case vs commit scenarios.
- `forecast-rollups-and-quotas`: Specify per-user and team rollups, quota tracking, and weighted revenue projections that align to period slices.
- `forecast-worksheets-history-export`: Set expectations for worksheets, adjustments (reps and managers), historical snapshots with actuals, multi-currency handling, and reporting/export surfaces.

## Notes
- OpenSpec CLI tooling is not available in this environment, so validation steps are documented in `tasks.md` for manual execution.
