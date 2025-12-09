# Laravel Metrics Integration

We use [`eliseekn/laravel-metrics`](https://laravel-hub.com/package/laravel-metrics) for lightweight, database-driven metrics and trend calculations (counts, sums, averages, mins, maxes) without writing ad-hoc SQL for dashboards.

## Patterns

- Build trends with `LaravelMetrics::query()` then choose an aggregate (`count`, `sum`, `average`, `max`, `min`) and a period (`byDay`, `byWeek`, `byMonth`, `byYear`, or `between`/`from`). The package handles date column formatting and locale-aware labels.
- Use `fillMissingData()` to backfill empty periods with zeros so Chart.js datasets always align.
- Use `metricsWithVariations()` when you need a single total plus an increase/decrease indicator compared to a previous period.

## Helper shortcuts

`App\Support\Metrics\LaravelMetricsHelper` wraps the library with app-friendly shapes for Filament/Chart.js widgets:

```php
use App\Support\Metrics\LaravelMetricsHelper;
use Illuminate\Support\Facades\DB;

// Month-over-month record counts (zero-filled)
$trend = LaravelMetricsHelper::monthlyCountTrend(
    DB::table('orders'),
    dateColumn: 'created_at',
    months: 6,
);

// Month-over-month revenue sums (zero-filled)
$revenueTrend = LaravelMetricsHelper::monthlySumTrend(
    DB::table('orders'),
    column: 'amount',
    dateColumn: 'created_at',
    months: 6,
);

// Current month total with a comparison to the previous month
$summary = LaravelMetricsHelper::currentMonthTotalWithVariation(
    DB::table('orders'),
    column: 'id',
    dateColumn: 'created_at',
    previousMonths: 1,
);
// $summary = ['count' => 42, 'variation' => ['type' => 'increase', 'value' => 5]]
```

The helper returns arrays shaped as:
- Trends: `['labels' => [...], 'data' => [...]]`
- Variation: `['count' => int, 'variation' => ['type' => 'increase|decrease', 'value' => int|string]]`

## Recommendations

- Keep aggregates simple and run them on already-filtered queries (team scoped, status scoped, etc.).
- Prefer `fillMissingData()` when driving charts so missing months/weeks don’t shift series.
- Stick to UTC dates in tests and set `Carbon::setTestNow()` for deterministic outputs.
