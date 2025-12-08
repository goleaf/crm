# Filament “Underrated Features” Integration

Based on [Laravel News — 5 Underrated Filament Features](https://laravel-news.com/5-underrated-filament-features), the CRM now has first-class hooks for the highlighted capabilities using Filament v4.3+.

## 1) Simple Chart Generation (Chart.js + Trend)
- Added a reusable Chart.js widget base: `app/Filament/Widgets/ChartJsTrendWidget.php`.
- New Lead trend widget using it: `app/Filament/Widgets/LeadTrendChart.php` registered on the dashboard (`AppPanelProvider` + `Dashboard`).
- Uses `sakanjo/laravel-easy-metrics` (via `App\Support\Metrics\EasyMetrics`) for gap-free weekly counts, tenant-aware cache keys, and translated headings.
- To add another trend chart, subclass `ChartJsTrendWidget`, set `$resource`, and optionally `$dateColumn/$weeks/$cacheSeconds`.

## 2) Real-Time Notifications
- New wrapper notification: `app/Notifications/RealTimeFilamentNotification.php` broadcasts Filament notifications through Laravel Echo/Pusher.
- Calendar page now sends a broadcast + in-panel toast for create/update actions to demonstrate the pattern (`Calendar::broadcastNotification`).
- When adding long-running actions (jobs/imports), create a `Filament\Notifications\Notification`, then `auth()->user()?->notify(new RealTimeFilamentNotification($notification))` to stream it live.

## 3) Native Global Search
- Resources keep `protected static ?string $recordTitleAttribute` and `getNavigationGroup()` so titles and search labels stay consistent.
- For richer search, override `getGloballySearchableAttributes()` / `getGlobalSearchResultDetails()` on resources; follow the steering notes in `.kiro/steering/filament-navigation.md`.

## 4) Standalone Packages
- Trend + Chart.js widgets rely only on core Filament + Laravel Easy Metrics; no Apex dependency required for new charts.
- Notifications reuse Filament’s notification package directly; broadcasting uses the Laravel notifications channel.

## 5) Custom Pages
- Existing custom pages (`app/Filament/Pages/Calendar.php`, `SeederGenerator.php`, etc.) illustrate non-CRUD flows; align new pages with the v4 schema system (see `.kiro/steering/filament-conventions.md`).

## Spec Alignment
- Satisfies charting/reporting hooks from `.kiro/specs/reporting-analytics/requirements.md` (dashlets, trends).
- Real-time notifications pattern supports collaboration timelines in `.kiro/specs/communication-collaboration/*`.

## How to Extend
- Charts: extend `ChartJsTrendWidget`, register in `Dashboard::getWidgets()` or a resource page; keep cache keys tenant-scoped.
- Notifications: wrap Filament notifications with `RealTimeFilamentNotification` when background work needs live updates.
- Search: add global search attributes/details when introducing new resources; ensure tenancy-safe queries.
