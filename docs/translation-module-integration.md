# Translation Module Integration

## Overview
This project fully integrates `laravel-translations-checker` with support for the modular architecture (`app-modules/`).

## How it works

### Scanning
The `TranslationCheckerService` has been enhanced to scan:
1. The standard `lang/` directory.
2. Any `src/resources/lang` directory within `app-modules/*`.

This ensures that translations defined inside modules (e.g., `SystemAdmin`, `Documentation`) are picked up by the translation system and available in the UI.

### Importing
To import translations from all sources (Main App + Modules):

```bash
php artisan translations:import-modules
```

Or use the **"Import Translations"** action in the Filament Translation Management page.
This will:
1. Run the native specific import for the main app.
2. Iterate through all modules defined in `config('translations.module_paths')` and merge their translation keys into the database.

### Exporting
Exporting via the UI or `php artisan translations:export` currently writes **all** translations to the main application's `lang/` directory.
- This creates a centralized "shadow" copy of all translations.
- This effectively overrides module translations with the versions in `lang/`.
- **Note**: If you change a module translation in the UI and export, it will be saved to `lang/{locale}/{file}.php`, NOT back to the module's source folder. This is intended behavior to avoid modifying vendor/module files directly and to keep a single source of truth for the deployed application.

### Automatic Sync
A Kiro hook (`.kiro/hooks/translation-sync.php`) is configured to watch changes in:
- `lang/en/**/*.php`
- `app-modules/*/src/resources/lang/**/*.php`

When you modify a translation file in code, it is automatically re-imported into the database.

## Configuration
Configuration is located in `config/translations.php`.

```php
    'module_paths' => [
        'app-modules/*/src/resources/lang',
    ],
```

Add more specific paths here if you add modules with different structures.
