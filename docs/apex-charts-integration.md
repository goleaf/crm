# Apex Charts Integration (Filament v4.3+)

- Added `leandrocfe/filament-apex-charts` (`^4.0`) and registered the plugin in both `AppPanelProvider` and `SystemAdminPanelProvider`.
- Existing dashboard charts (`PipelinePerformanceChart`, `NotesActivityChart`, `NotesByCategoryChart`) now extend `ApexChartWidget` with per-tenant scoping and 10 minute caching.
- New reusable widgets:
  - `App\Filament\Widgets\ResourceTrendChart` – weekly creation trend (default last 8 weeks).
  - `App\Filament\Widgets\ResourceStatusChart` – status/source/category distribution (auto-picks the first available column).
- Time-series and doughnut widgets now use `sakanjo/laravel-easy-metrics` (via `App\Support\Metrics\EasyMetrics`) for gap-free weekly/daily aggregates and enum-aware distributions.
- New base list page: `App\Filament\Resources\Pages\BaseListRecords` automatically injects the above widgets into every resource index page when the backing table exposes a date column and a status-like column. It now probes `getChartDateCandidates()` (defaults: `created_at`, then `updated_at`) to keep charts available on models without `created_at`. Override `getChartDateColumn()` / `getChartDateCandidates()` / `getChartStatusCandidates()` / `getChartCacheSeconds()` / `getTrendWeeks()` in a list page if a resource needs different columns or cadence.
- Tenant awareness: chart queries add `team_id` filters when the column exists; cache keys include the tenant id to avoid cross-tenant leakage.
- Translations: chart labels and headings live under `app.charts.*` (e.g., `records_trend`, `status_breakdown`, `pipeline_momentum`, `notes_activity`, `notes_by_category`, `unknown`). Add mirrored keys in new locales when extending.
- Custom usage: to attach the generic charts elsewhere, call `ResourceTrendChart::make([...])`/`ResourceStatusChart::make([...])` and pass `resourceClass`, `dateColumn`/`statusColumn`, `weeks`, and `cacheSeconds` as needed.
