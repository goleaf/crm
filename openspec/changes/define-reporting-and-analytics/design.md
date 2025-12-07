# Design Notes

## Data Sources and Metrics
- Reports pull from normalized tables (orders/invoices for sales, leads, opportunities, activities). Each report defines allowed dimensions (owner, team, stage, status, date buckets, territory) and measures (count, amount, weighted amount, duration).
- Time bucketing supports day/week/month/quarter/year using the tenant timezone; filters accept absolute and relative ranges (e.g., last 30 days, this quarter).
- Pipeline metrics read opportunity custom fields (stage, probability, forecast category) and compute weighted values; activity metrics aggregate counts/durations across tasks, notes, calls, meetings.

## Report Builder
- Builder offers selectable dataset (sales, leads, deals, activity), dimensions, measures, grouping, sorting, and filters with preview pagination. Builders enforce per-dataset constraints (e.g., sales: group by owner/status/date; pipeline: stage/owner/forecast).
- Saved reports store definition JSON, owner/team visibility, and refresh interval; scheduled exports can email/report to storage.

## Exports and Dashboards
- Export formats: CSV and XLSX always; PDF optional when templating available. Exports include applied filters metadata and timestamps.
- Dashboard widgets can render saved reports as tables, charts, or KPI tiles with cached results per refresh interval; widgets honor user permissions and share the saved report definition.

## Security and Performance
- Row-level access uses team/owner constraints; all queries apply tenant filters. Heavy aggregations should paginate and cache results by report hash + parameters with TTL.
