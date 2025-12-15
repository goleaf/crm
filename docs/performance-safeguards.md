# Performance Safeguards

Performance guardrails centralize pagination limits, lazy-loading controls, and slow-query logging to meet System & Technical requirements 2.1 and 2.2.

## Configuration (`config/performance.php`)

- `pagination.default_per_page` (env: `PERFORMANCE_PAGINATION_DEFAULT`, default 25)
- `pagination.max_per_page` (env: `PERFORMANCE_PAGINATION_MAX`, default 100)
- `pagination.parameter` (env: `PERFORMANCE_PAGINATION_PARAMETER`, default `per_page`)
- `query.slow_query_threshold_ms` (env: `PERFORMANCE_SLOW_QUERY_THRESHOLD_MS`, default 750)
- `lazy_loading.prevent` (env: `PERFORMANCE_PREVENT_LAZY_LOADING`, default false)
- `lazy_loading.strict_mode` (env: `PERFORMANCE_MODEL_STRICT_MODE`, default false)
- Additional toggles for asset minification, CDN enablement, cache TTL, and memory limits.

## Enforcement

- **Middleware:** `App\Http\Middleware\EnforcePaginationLimits` clamps incoming `per_page` values to configured defaults and max, preventing runaway result sizes.
- **Query builder macros:** `safePaginate()` and `safeSimplePaginate()` apply the same bounds when fetching records.
- **Lazy loading:** Configurable via `PERFORMANCE_PREVENT_LAZY_LOADING` and `PERFORMANCE_MODEL_STRICT_MODE`.
- **Slow queries:** Logged when duration exceeds `PERFORMANCE_SLOW_QUERY_THRESHOLD_MS`.

## Usage

```php
$perPage = $request->integer(config('performance.pagination.parameter'));

// Clamped automatically
$companies = Company::query()->safePaginate($perPage);
```

## Testing

- **Property 2: Performance safeguards** – `tests/Unit/Properties/PerformanceSafeguardsPropertyTest.php`
  - Validates pagination clamp defaults and middleware behavior.
- Run: `php artisan test --filter=PerformanceSafeguardsPropertyTest`

## Requirements Coverage

- ✅ Requirement 2.1: Pagination/result limits, lazy-loading controls, slow-query monitoring hooks
- ✅ Requirement 2.2: Exposed performance configuration and diagnostics

