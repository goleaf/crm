# Filament Activity Log Integration

Source: [pxlrbt/filament-activity-log](https://github.com/pxlrbt/filament-activity-log)

## What this adds
- A Filament page for viewing a record’s activity history using the `pxlrbt/filament-activity-log` plugin (Filament v4).
- Uses the existing `activities()` relationship on models with `App\Models\Concerns\LogsActivity`, plus `App\Models\Activity`’s Spatie-compatible `changes`/`properties` accessors.
- Table actions link directly to the activity page so users can jump from a record row to its audit history.

## Setup
1. **Dependency**: `composer require pxlrbt/filament-activity-log` (already required).
2. **Theme import**: Add the plugin styles to the custom Filament theme:
   - `resources/css/filament/admin/theme.css` → `@import '../../../../vendor/pxlrbt/filament-activity-log/resources/css/styles.css';`
3. **Model requirements**: Models should expose `activities()` (from `LogsActivity`) and persist `changes` in the `{attributes, old}` shape so the diff table renders and restore actions work.

## Resource wiring pattern
1. Create a page that extends `pxlrbt\FilamentActivityLog\Pages\ListActivities` and point it at the resource:
   ```php
   // app/Filament/Resources/FooResource/Pages/ListFooActivities.php
   final class ListFooActivities extends ListActivities
   {
       protected static string $resource = FooResource::class;
   }
   ```
2. Register the page and route in the resource:
   ```php
   public static function getPages(): array
   {
       return [
           // ...
           'activities' => ListFooActivities::route('/{record}/activities'),
       ];
   }
   ```
3. Add a table or page action to link users to the activity view:
   ```php
   Action::make('activities')
       ->label(__('ui.navigation.activities'))
       ->icon('heroicon-o-queue-list')
       ->url(fn ($record) => static::getUrl('activities', ['record' => $record]));
   ```

## Current usage
- `PeopleResource` exposes `/people/{record}/activities` with a row action to open the log. Use the same pattern for other resources that already log activity.
