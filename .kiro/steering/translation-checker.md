# Laravel Translation Checker Integration

## Core Principles
- Laravel Translation Checker (`outhebox/laravel-translations`) provides a comprehensive UI for managing translations with database-backed storage.
- All translation management flows through the Translation UI at `/translations` or via `TranslationCheckerService`.
- Translations are stored in database tables (`ltu_*`) and exported to PHP files for version control.
- Supports scanning module translations from `app-modules/*/src/resources/lang` via configured `module_paths`.
- Use `php artisan translations:import` after adding new keys to PHP files; use `php artisan translations:export` before deploying.

## Service Usage
- Use `TranslationCheckerService` (singleton in container) for programmatic access to translation data.
- Inject via constructor: `public function __construct(private readonly TranslationCheckerService $service) {}`.
- All methods return cached results (1-hour TTL by default); cache keys follow `translations.*` pattern.
- Call `$service->clearCache()` after bulk changes or when cache invalidation is needed.

## Filament Integration
- Translation Management page at `app/Filament/Pages/TranslationManagement.php` displays statistics and quick actions.
- Translation Status Widget (`app/Filament/Widgets/TranslationStatusWidget.php`) shows completion percentages on dashboard.
- Access control via `manage_translations` and `view_translation_statistics` permissions.
- Use header actions for import/export operations with confirmation modals.

## Workflow Patterns
### Adding New Translations
1. Add translation keys to PHP files in `lang/en/`
2. Run `php artisan translations:import` to sync with database
3. Use Translation UI (`/translations`) to translate to other languages
4. Run `php artisan translations:export --language=uk` to update PHP files
5. Commit updated translation files to version control

### Updating Existing Translations
1. Edit translations in Translation UI
2. Export changes: `php artisan translations:export`
3. Review changes in git diff
4. Commit updated files

### Collaborating on Translations
1. Invite collaborators via Translation UI at `/translations/contributors`
2. Assign languages to specific collaborators
3. Collaborators edit translations in UI
4. Export and commit changes regularly

## Artisan Commands
- `php artisan translations:import` - Import all translation files from `lang/` directory
- `php artisan translations:import --language=uk` - Import specific language
- `php artisan translations:export` - Export all languages to PHP files
- `php artisan translations:export --language=uk` - Export specific language
- `php artisan translations:sync` - Sync database with filesystem
- `php artisan translations:clean` - Remove unused translation keys

## Configuration
- Enable/disable: `TRANSLATIONS_ENABLED=true`
- Route prefix: `TRANSLATIONS_ROUTE_PREFIX=translations`
- Middleware: `TRANSLATIONS_MIDDLEWARE=web,auth`
- Google Translate API: `GOOGLE_TRANSLATE_API_KEY=your-key` (optional)
- Cache TTL: `TRANSLATIONS_CACHE_TTL=3600`
- Cache driver: `TRANSLATIONS_CACHE_DRIVER=redis`

## Testing
- Feature tests in `tests/Feature/Translation/TranslationCheckerTest.php` cover import/export, completion calculation, and missing translations.
- Unit tests in `tests/Unit/Services/TranslationCheckerServiceTest.php` validate service methods and caching.
- Use `Cache::fake()` to test caching behavior without Redis.
- Mock `TranslationCheckerService` in unit tests to avoid database queries.

## Translations
- Navigation: `__('app.navigation.translations')`, `__('app.navigation.system')`
- Labels: `__('app.translations.management')`, `__('app.translations.statistics')`, `__('app.translations.completion')`
- Actions: `__('app.translations.import_translations')`, `__('app.translations.export_translations')`, `__('app.translations.open_ui')`
- Notifications: `__('app.translations.translations_imported')`, `__('app.translations.translations_exported')`
- Messages: `__('app.translations.import_translations_confirmation')`, `__('app.translations.translation_statistics_description')`

## Best Practices
- ✅ Use `TranslationCheckerService` for programmatic access
- ✅ Import translations after adding new keys to PHP files
- ✅ Export translations before deploying to production
- ✅ Monitor translation completion percentages via widget
- ✅ Use Google Translate API for initial translations, then review manually
- ✅ Invite collaborators for translation management
- ✅ Cache translation data aggressively
- ✅ Test translation imports/exports in CI/CD
- ❌ Don't edit translations directly in database without exporting
- ❌ Don't skip importing after manual file changes
- ❌ Don't expose translation UI to unauthorized users
- ❌ Don't forget to clear cache after bulk changes
- ❌ Don't rely solely on automated translations
- ❌ Don't mix database and file-based translation workflows

## Integration with Kiro Hooks
- Existing auto-translation hook can be enhanced to sync with Translation Checker
- After auto-translating files, run `php artisan translations:import` to sync with database
- Consider adding a hook to export translations after database changes

## Related Documentation
- `docs/laravel-translation-checker-integration.md` - Complete integration guide
- `.kiro/steering/translations.md` - Translation conventions
- `.kiro/steering/TRANSLATION_GUIDE.md` - Translation implementation guide
- `docs/laravel-container-services.md` - Service pattern guidelines
- `.kiro/steering/filament-conventions.md` - Filament integration patterns

## Package Resources
- Package: `outhebox/laravel-translations` v1.4.1
- GitHub: https://github.com/MohmmedAshraf/laravel-translations
- Laravel News: https://laravel-news.com/translation-checker
- Stack: Inertia.js + Vue 3
- Database Tables: `ltu_languages`, `ltu_translations`, `ltu_translation_files`, `ltu_phrases`, `ltu_contributors`, `ltu_invites`
