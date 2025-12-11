# Laravel Auto Translations Integration

Automatically collect translation keys, generate locale JSON files with Anthropic/Groq, and bring the results back into our PHP translation files without changing the source of truth.

## Package + Commands
- **Package:** `iperamuna/laravel-auto-translations` (dev-only)
- **Vendor commands:** `lang:generate-en-json`, `lang:new {locale} [--preview]`, `lang:export-i18n {lang}`
- **Project commands:**
  - `auto-translations:export-base [--locale=en] [--output=]` – flatten PHP translations (app + modules) into `lang/{locale}.json` and `storage/app/auto-translations/{locale}.json`
  - `auto-translations:json-to-php {locale} [--source=] [--target=] [--import]` – convert `lang/{locale}.json` back into PHP files and optionally run `translations:import`

## Configuration
- `config/auto-translations.php` holds scan paths (app, modules, routes, views, JS), storage path, and AI settings.
- Environment flags (see `.env.example`):
  - `AUTO_TRANSLATE_ENABLED`, `AUTO_TRANSLATE_SOURCE_LOCALE`, `AUTO_TRANSLATE_STORAGE_PATH`
  - `AUTO_TRANSLATE_REMOTE_VIEW_ENABLED`, `AUTO_TRANSLATE_REMOTE_VIEW_TOKEN`
  - `AUTO_TRANSLATE_ANTHROPIC_API_BASE`, `AUTO_TRANSLATE_ANTHROPIC_MODEL`, `AUTO_TRANSLATE_ANTHROPIC_API_KEY`, `AUTO_TRANSLATE_TEMPERATURE`, `AUTO_TRANSLATE_CHUNK_SIZE`
- JSON outputs under `lang/*.json`, `resources/lang/*.json`, and `storage/app/auto-translations` are gitignored; PHP translation files remain the source of truth.

## Recommended Workflow
1. **Export base JSON from PHP translations**
   ```bash
   php artisan auto-translations:export-base
   # Optional: specify a different locale/output
   php artisan auto-translations:export-base --locale=uk --output=lang/uk.json
   ```
   The command also mirrors the JSON to `resources/lang/{locale}.json` for `lang:export-i18n` compatibility and to `storage/app/auto-translations/{locale}.json` for backups. If you need to re-scan the codebase for keys, use the vendor command with configured paths: `php artisan lang:generate-en-json --path=app --path=app-modules/SystemAdmin/src --path=app-modules/Documentation/src --path=app-modules/OnboardSeed/src --path=routes --path=resources/views --path=resources/js`.
2. **Generate a new locale via AI**
   ```bash
   php artisan lang:new es --preview
   # Uses lang/en.json as the base and AUTO_TRANSLATE_* settings for model/api key
   ```
3. **Convert AI JSON back to PHP + import**
   ```bash
   php artisan auto-translations:json-to-php es --import
   # Writes lang/es/*.php and runs translations:import to sync the database
   ```
   Shortcut: `php artisan auto-translations:sync-json es` does the same import step for the generated JSON.
4. **(Optional) Frontend export**
   ```bash
   php artisan lang:export-i18n es
   # Creates resources/lang/es.i18n.json for Vue/i18n consumers
   ```

## Local Browser UI
- `/langs` shows any `lang/*.json` file when `APP_ENV=local`.
- For remote debugging, enable `AUTO_TRANSLATE_REMOTE_VIEW_ENABLED=true` and supply `?_token={AUTO_TRANSLATE_REMOTE_VIEW_TOKEN}`.

## Notes
- Keep running `translations:check` (`composer test:translations`) to ensure generated keys are present across locales.
- Always convert JSON outputs back into PHP files (`auto-translations:json-to-php`) before committing so the PHP files stay authoritative.
