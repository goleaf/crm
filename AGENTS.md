# Repository Guidelines

## Project Structure & Module Organization
- `app/` contains controllers, models, console commands, and service providers.
- `app-modules/` stores Relaticle packages (SystemAdmin, Documentation, OnboardSeed) with their own `src`, configs, and migrations via Composer’s PSR-4 autoload.
- `resources/` (JS, CSS, Blade snippets) handle frontend assets built by Vite/Tailwind; `routes/`, `config/`, and `lang/` follow Laravel layout.
- `database/` carries migrations, seeders, and a committed SQLite file for quick local work; `tests/` uses Pest with `Feature`, `Unit`, and `ArchTest.php` suites.

## Build, Test, and Development Commands
- `composer install` installs PHP packages, copies `.env.example`, generates the app key, and touches `database/database.sqlite`.
- `composer dev` starts artisan serve, the queue listener, Pail log tailer, and `npm run dev` via `npx concurrently`.
- `npm run dev` runs the Vite dev server; `npm run build` compiles production assets.
- `composer lint` runs Rector v2 (with Laravel 12 sets and composer-based detection) followed by `pint --parallel` to keep refactoring and formatting aligned before commits.
- `composer test:refactor` runs Rector in dry-run mode to verify no pending refactors without writing files (used in CI).
- `composer test` executes linting, Rector dry-run checks, type coverage, `phpstan analyse`, and the default Pest parallel suite; `composer test:ci` mirrors the CI pipeline with the dedicated phpunit config.
- `composer test:translations` runs the translation checker to ensure all keys are present in all supported locales.

## Coding Style & Naming Conventions
- Follow PSR-12 for PHP classes (`App\`, `Relaticle\*`) and keep Blade components in kebab-case, while Livewire classes stay PascalCase.
- Use `pint` (and the `pint.json` config) to format PHP/Blade files; Rector v2 keeps typed props, modern Laravel APIs, and code quality consistent during refactors.
- Rector v2 uses composer-based detection to automatically apply Laravel 12-specific rules, converts arrays to collections, adds type declarations, removes dead code, and enforces early returns.
- Always run `composer lint` before commits to apply Rector refactoring + Pint formatting; review changes in git diff before committing.
- Frontend JS leverages Vite + Tailwind; keep entrypoints (`resources/js/app.js`) lean and prefer semantic class names documented in `resources/css`.
- Tailwind theming runs on the 3.4+ utility set (dvh viewport units, `size-*`, `text-balance`/`text-pretty`, `has-*`, forced-colors); use these in Filament v4.3+ themes instead of hand-rolled width/height or viewport hacks.

## Testing Guidelines
- Pest drives the test suite; place specs under `tests/Feature` or `tests/Unit`, suffix files with `*Test.php`, and share helpers in `tests/Pest.php`.
- Use `defstudio/pest-plugin-laravel-expectations` for HTTP/model/storage checks (e.g., `toBeOk()`, `toBeRedirect()`, `toExist()`); keep assertions Pest-style alongside Livewire helpers.
- Use `spatie/pest-plugin-route-testing` to ensure all routes remain accessible; test routes by type (public, authenticated, API) in `tests/Feature/Routes/` with centralized config in `RouteTestingConfig`—see `.kiro/steering/pest-route-testing.md`.
- Stress testing is available via `pestphp/pest-plugin-stressless`; keep runs opt-in (`RUN_STRESS_TESTS=1` + `STRESSLESS_TARGET`) and small (`STRESSLESS_CONCURRENCY`, `STRESSLESS_DURATION`, `STRESSLESS_P95_THRESHOLD_MS`).
- Coverage gates are enforced by `composer test:type-coverage` (`pest --type-coverage --min=99.9`) and `composer test:coverage` (`pest --coverage --min=80`).
- `composer test:types` (`phpstan analyse`) and `composer test:pest:ci` (`phpunit.ci.xml`) validate static analysis and CI-specific shards when needed.

## Commit & Pull Request Guidelines
- Commit messages follow the conventional `<type>: <summary>` pattern (`feat:`, `fix:`, `chore:`) with a short imperative subject.
- PR descriptions should summarize behavior changes, link relevant issues/RFCs, and list verification steps (e.g., `composer lint`, `composer test:coverage`).
- Generated files (`bootstrap/cache`, `storage`, `vendor`) should remain unchanged unless required for a deployment artifact.

## Environment & Configuration Tips
- Composer scripts already copy `.env.example` and touch the SQLite file, but double-check `.env` values before running `composer dev`.
- Clear cached config when providers or module bindings change via `php artisan optimize:clear`.
- HTTP clients are centralized via `config/http-clients.php` (`Http::external`, `Http::github`) with retry/backoff, timeouts, and a brand-aware user agent; override defaults with `HTTP_CLIENT_*`/`GITHUB_HTTP_*` env vars and reuse the macros in Filament actions/pages instead of ad-hoc `Http::` calls.
- PCNTL must be enabled locally for the bundled Pail log tailer; prefer `php artisan pail --timeout=0` (used in `composer dev`) and the Filament "Log streaming" page for curated commands/filters—see `docs/laravel-pail.md`.
- Security headers/CSP/security.txt live in `config/security.php` (stacked with `config/headers.php`); adjust env toggles there instead of adding per-controller headers. Use the Filament Security Audit page to run `composer audit` and review checklist items.
- Services follow the container pattern with constructor injection and readonly properties; register in `AppServiceProvider::register()` using `bind()` or `singleton()`, and avoid service locator pattern in business logic—see `docs/laravel-container-services.md` and `docs/laravel-container-implementation-guide.md` for comprehensive patterns and examples.

## Filament Shield Integration
- Filament Shield provides role-based access control (RBAC) using Spatie Laravel Permission.
- Generate permissions with `php artisan shield:generate --all` after creating new resources.
- Roles are team-scoped in multi-tenant applications; assign with `$user->assignRole('role', $team)`.
- Super admin role (`super_admin`) bypasses all permission checks when enabled.
- Shield resource is located in Settings cluster at `/app/shield/roles`.
- Permissions follow pattern: `{action}::{Resource}` (e.g., `view_any::Company`, `create::Task`).
- See `docs/filament-shield-integration.md` and `.kiro/steering/filament-shield.md` for complete patterns.

## Repository expectations

- Document public utilities in `docs/` when you change behavior. Also read and use `.kiro/system`, `.kiro/hooks/`, `.kiro/steering/` inside existing files
- When adjusting model inheritance or shared base models, update the relevant `.kiro/steering` rule (e.g., `laravel-conventions.md`) in the same change so future edits avoid repeating the issue.
- When fixing enum method/translation issues (label/color), also update the relevant `.kiro/steering` guideline (e.g., `filament-conventions.md`) in the same change to prevent regressions.
- When changing brand-visible text, prefer the config-driven `brand_name()` helper and update `.kiro/steering` guidance accordingly rather than hardcoding names.
- When formatting array/JSON data, prefer `App\Support\Helpers\ArrayHelper` (`docs/array-helpers.md`) over manual `implode()` so Filament v4.3+ schemas, exports, and notifications handle strings/arrays consistently.
- When wrapping long text in Filament tables or infolists, use `App\Support\Helpers\StringHelper::wordWrap()` (e.g., `break: '<br>'`, `cutLongWords: true`) instead of manual `wordwrap()` or inline CSS.
- App models extend the shared `App\Models\Model` base with Laravel Date Scopes—avoid importing `Illuminate\Database\Eloquent\Model` in domain models and reuse the DateScopes trait/`App\Filament\Support\Filters\DateScopeFilter` for timestamp filtering instead of custom Carbon ranges.
- Use Laravel Precognition for real-time form validation; always use Form Requests for validation logic, debounce text input validation (300-500ms), validate on blur for better UX, and test both precognitive and actual submissions—see `docs/laravel-precognition.md` and `.kiro/steering/laravel-precognition.md`.
- Security headers are enforced globally via `treblle/security-headers` and `App\Http\Middleware\ApplySecurityHeaders`; tune via `SECURITY_HEADERS_*` env vars and `config/headers.php` (`headers.except` for narrow opt-outs) instead of removing the middleware.
- Warden security audits (`dgtlss/warden`) run automated `composer audit` checks with scheduled execution (daily at 3 AM by default); configure notifications via `WARDEN_EMAIL_RECIPIENTS`/`WARDEN_SLACK_WEBHOOK_URL`, enable audit history with `WARDEN_HISTORY_ENABLED=true`, and access via Filament Security Audit page—see `docs/warden-security-audit.md` and `.kiro/steering/warden-security.md`.
- Services should use constructor injection with readonly properties; register in `AppServiceProvider` and avoid service locator pattern (`app()`, `resolve()`) in business logic—see `docs/laravel-container-services.md` and `.kiro/steering/laravel-container-services.md` for patterns.
- Profanity filtering uses `ProfanityFilterService` (singleton) with multi-language support (English, Spanish, German, French, All); validate user content with `NoProfanity` rule, use `CleanProfanityAction` in Filament resources, and cache frequently checked content—see `docs/blasp-profanity-filter-integration.md` and `.kiro/steering/blasp-profanity-filter.md`.
- OCR services follow the container pattern with Tesseract driver, AI cleanup via Prism PHP, template-based extraction, and queue processing—see `docs/ocr-complete-implementation.md` and `.kiro/steering/ocr-integration.md` for usage patterns and best practices.
- World data (countries, states, cities, currencies, languages, timezones) is accessed via `WorldDataService` singleton with caching; use dependent selects in Filament forms for country → state → city hierarchies—see `docs/world-data-integration.md` and `.kiro/steering/world-data-package.md`.
- Union pagination (`austinw/laravel-union-paginator`) combines data from multiple models into paginated results; use `ActivityFeedService` for activity feeds, `UnifiedSearchService` for cross-model search, and ensure consistent column counts/types across union queries—see `docs/laravel-union-paginator.md` and `.kiro/steering/laravel-union-paginator.md` for patterns and performance optimization.
- **Filename Generation**: Use `Blaspsoft\Onym\Facades\Onym` for generating sanitized, structured filenames (UUIDs, slugs, timestamps) for all user uploads; configure default strategies in `config/onym.php` and integrate with Filament's `FileUpload` via `getUploadedFileNameForStorageUsing`—see `docs/laravel-onym-integration.md`.

## Translation Management Integration
- Translation management uses Laravel Translation Checker (`outhebox/laravel-translations`) with database-backed storage.
- Run `php artisan translations:import` after adding new keys to PHP files in `lang/en/`.
- Use the Translation UI at `/translations` for editing translations across all languages.
- Run `php artisan translations:export` before deploying to sync database changes back to PHP files.
- Use `TranslationCheckerService` (singleton) for programmatic access to translation data.
- Translation Management page available in Filament at Settings → Translations.
- Translation Status Widget displays completion percentages on dashboard.
- Automatic import enabled via serial hook on `lang/en/**/*.php` changes.
- See `docs/laravel-translation-checker-integration.md` and `.kiro/steering/translation-checker.md` for complete workflow patterns.

## API Documentation
- API documentation is automatically generated by `dedoc/scramble`.
- Document public API resources with proper return types and PHPDocs.
- Access via `viewApiDocs` gate or Filament "API Utils" group.
- See `.kiro/steering/api-documentation.md` for guidelines.

## Config Checker Integration
- Config health monitoring uses `chrisdicarlo/laravel-config-checker` wrapped in `ConfigCheckerService`.
- Run `php artisan config:check` to verify references in CLI or use `composer test:config`.
- Use `System > Config Checker` in Filament to view status and run checks.
- Results are cached for 5 minutes; use the UI refresh button or `ConfigCheckerService::clearCache()` to reset.
- See `docs/laravel-config-checker-integration.md` and `.kiro/steering/config-checker.md`.
