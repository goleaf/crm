# Laravel Easy Metrics Integration

## What changed
- Added `sakanjo/laravel-easy-metrics` (^1.1.11) to replace `flowframe/laravel-trend` for dashboard metrics.
- Introduced `App\Support\Metrics\EasyMetrics` for reusable weekly/daily trends and doughnut distributions with tenant-aware builders.
- Updated Filament widgets to consume Easy Metrics:
  - `ResourceTrendChart`, `PipelinePerformanceChart`, and `NotesActivityChart` now derive labels/data from Easy Metrics (zero-filled ranges, 10m cache).
  - `ChartJsTrendWidget` / `LeadTrendChart` reuse Easy Metrics for Chart.js-based trends.
  - `ResourceStatusChart` and `NotesByCategoryChart` use doughnut metrics with `Range::ALL` and enum-aware labeling.

## Behavior
- Weekly trends default to the last 8 weeks; daily notes trend covers the last 30 days.
- Doughnut metrics auto-fill enum options (labels come from enum `getLabel`/`label` wrappers) and fall back to `app.charts.unknown` when empty.
- Cache keys remain tenant-scoped to avoid cross-tenant leakage.

## Usage example

```php
use App\Support\Metrics\EasyMetrics;
use App\Models\Lead;

// Weekly counts (8 weeks)
$trend = EasyMetrics::weeklyCounts(
    Lead::query(),
    (new Lead())->qualifyColumn('created_at'),
    8,
);

// Doughnut distribution
$distribution = EasyMetrics::doughnutCounts(
    Lead::query(),
    'status',
    \App\Enums\LeadStatus::class,
);
```

`$trend` returns `['labels' => [...], 'data' => [...]]`; `$distribution` returns aligned `labels`/`data` arrays.

## Operational notes
- Flowframe Trend dependency removed.
- Ranges must be whitelisted (`->ranges([$weeks])`) before calling `->range($weeks)`; the helper handles this internally.
- Weekly labels use ISO weeks and are formatted as `MMM D`; daily labels are formatted as `MMM D` for chart axes.
- Metric widgets retain the existing 10-minute cache defaults and tenant scoping.
