# Filament Empty States & Index Enhancements

- **Coverage:** `App\Filament\Resources\Pages\BaseListRecords` now configures table empty states for every resource index. Headings/descriptions come from `app.empty_states.*` translations, the icon uses `Heroicon::OutlinedDocumentPlus`, and the footer reuses the page’s create action when available.
- **Global tables:** `Table::configureUsing` applies the same empty-state pattern to all tables (including relation managers) when a create action is present.
- **Behavior:** When a table has no rows, users see the empty state with a CTA to create the first record. Tenancy/authorization continue to be respected via the underlying create action visibility.
- **Customization:** Override `getTableEmptyStateHeading()`, `getTableEmptyStateDescription()`, or `getEmptyStateCreateAction()` in a list page if a resource needs a bespoke message or different CTA.
- **Charts:** Base list pages now pick the first available date column from `getChartDateCandidates()` (defaults to `created_at`, then `updated_at`) to keep trend charts available even when records omit `created_at`.
- **Translations:** Keys live under `app.empty_states.*`; mirrored across locales. Add resource-specific copies there if a custom message is needed.
