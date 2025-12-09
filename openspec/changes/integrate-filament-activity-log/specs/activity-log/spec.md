# Filament Activity Log Integration

## ADDED Requirements

#### Requirement 1: Enable activity logging dependencies.
- Projects shall install and configure `spatie/laravel-activitylog`, applying the `LogsActivity` trait to models whose activity should be shown, so the plugin page can load `activities()`.
#### Scenario: Prepare a model for logging
- Given `spatie/laravel-activitylog` is installed
- When a model uses the `LogsActivity` trait per Spatie docs
- Then activity entries for that model are recorded and retrievable for display in Filament

#### Requirement 2: Expose activity pages per Filament resource.
- Each Filament resource that needs activity visibility shall define a page extending `pxlrbt\FilamentActivityLog\Pages\ListActivities`, pointing to the resource, and register it in `getPages()` with an `activities` route.
#### Scenario: Register an activities page
- Given an `OrderResource`
- When a `ListOrderActivities` page extends `ListActivities` and is added to `getPages()` at `/ {record}/activities`
- Then users can navigate to `/orders/{record}/activities` to see that record’s activity log in Filament

#### Requirement 3: Link to activity pages from tables or pages.
- Resources shall provide actions/links from record tables or detail pages that route to the corresponding activities page with the record identifier.
#### Scenario: Table action opens activities
- Given an order row in a Filament table
- When the user clicks the “activities” action
- Then they are taken to the order’s activities page showing the related activity log

#### Requirement 4: Apply required theming for Filament v4.
- For Filament v4, projects shall include the plugin CSS import (`@import '../../../../vendor/pxlrbt/filament-activity-log/resources/css/styles.css';`) in a custom theme and rebuild assets so activity pages render correctly.
#### Scenario: Ensure plugin styles load
- Given the project uses Filament v4 with a custom theme
- When the plugin CSS import is added and assets are rebuilt
- Then the activity log page displays with the expected styling and layout
