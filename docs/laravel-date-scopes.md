# Laravel Date Scopes

## What changed
- Installed `laracraft-tech/laravel-date-scopes` (^2.4) and wrapped it in the shared `App\Models\Model` base so every domain model inherits the package scopes without individual trait imports.
- Added `App\Filament\Support\Filters\DateScopeFilter` to provide reusable table filters (e.g., `DateScopeFilter::make()` for `created_at`, `DateScopeFilter::make(name: 'start_at_range', column: 'start_at')` for calendar events).
- Updated System Admin analytics widgets to use the new scopes for month-over-month growth and daily/ monthly trend buckets.

## Runtime usage
- All app models now expose the scope methods: `ofToday()`, `ofLast7Days()`, `ofLast30Days()`, `monthToDate()`, `quarterToDate()`, `yearToDate()`, `ofLastMonth(startFrom: now())`, etc.
- Scopes accept an optional `$column` argument: `Lead::ofLast7Days(column: 'updated_at')`, `CalendarEvent::monthToDate(column: 'start_at')`.
- Default ranges are exclusive (e.g., `ofLast7Days()` excludes “today”); use `ofToday()` or the `DateRange::INCLUSIVE` overloads if you need current-day inclusion.

## Filament patterns
- Use `DateScopeFilter::make()` in resources that sort by `created_at` to avoid hand-rolled `whereBetween`/Carbon logic.
- Pass a custom column for event-style dates: `DateScopeFilter::make(name: 'start_at_range', column: 'start_at')`.
- Prefer these helpers over ad-hoc `DatePicker` + query callbacks so filters stay consistent with the global scopes and translations.

## Notes & tests
- Tests: `tests/Unit/Models/DateScopesTest.php` covers created-at and alternate-column filtering.
- No config overrides added; defaults remain (`created_at` column, exclusive ranges). Update `config/date-scopes.php` if you need inclusive defaults.
