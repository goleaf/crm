# Proposal: integrate-filament-activity-log

## Change ID
- `integrate-filament-activity-log`

## Summary
- Capture requirements to surface Spatie activity logs inside Filament resources using the `pxlrbt/filament-activity-log` plugin.
- Ensure activity pages are registered per resource, linked from tables/pages, and backed by properly configured `spatie/laravel-activitylog`.
- Document version/theming considerations (Filament v4 custom theme CSS import) so UI renders correctly.

## Capabilities
- `activity-log-visibility`: Expose activity logs within Filament resources using plugin pages and links.
- `activity-log-dependency`: Require and configure `spatie/laravel-activitylog` with `LogsActivity` on models to populate logs.
- `activity-log-theming`: Apply plugin CSS (v4 custom theme import) so activity pages render as expected.

## Notes
- Source: https://madewithlaravel.com/go/filament-activity-log (repository `pxlrbt/filament-activity-log`)
