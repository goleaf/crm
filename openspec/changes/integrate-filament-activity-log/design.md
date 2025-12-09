# Design Notes

- Require `spatie/laravel-activitylog` configured with the `LogsActivity` trait on models so the plugin's page can load `activities()` results.
- Each resource should register a `ListActivities`-derived page and route, and table/page actions should link to it with the record identifier.
- For Filament v4, ensure a custom theme imports `vendor/pxlrbt/filament-activity-log/resources/css/styles.css` and is rebuilt so styles load.
- Keep plugin/vendor assets published or available to the custom theme; ensure authorization aligns with resource policies when exposing activity pages.
