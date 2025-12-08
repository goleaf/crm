# PHP Insights Integration

## Overview
- Added `nunomaduro/phpinsights` as a dev dependency to monitor code quality, complexity, architecture, and style.
- Configuration lives in `config/insights.php`, scoped to `app`, `app-modules`, `config`, `database`, `routes`, and `tests`, and excludes build/output paths (`bootstrap`, `public`, `storage`, `vendor`, `node_modules`, published assets, coverage reports, tools).
- Timeout is increased to 180s to accommodate a full scan of the workspace.

## CLI usage
- `composer insights` — run the standard console report.
- `composer insights:json` — emit a JSON report (used by the Filament page and suitable for CI parsing).
- Both commands respect `config/insights.php`; adjust `paths`/`exclude` there if you need to scope scans.

## Filament v4.3+ page
- New settings page **Code Insights** (`App\Filament\Pages\PhpInsights`) surfaces the latest PHP Insights run with summary scores and the top findings per category.
- Access is limited to verified team owners/admins; find it under the Settings navigation group.
- Use the **Refresh insights** action to trigger a new run; success/failure is reported via Filament notifications.

## Service details
- `App\Services\PhpInsightsService` executes `phpinsights --format=json --quiet` via Symfony Process, parses the JSON payload, and returns structured data for the UI.
- Progress output is suppressed for faster parsing; the service guards against malformed output and surfaces errors through notifications on the page.

## CI or local checks
- Prefer the JSON command for pipelines so you can gate on scores (`summary` keys include `code`, `complexity`, `architecture`, `style`).
- If scans grow, tweak `config('insights.timeout')` or narrow `paths` to keep runtimes predictable.
