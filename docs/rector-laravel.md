# Rector for Laravel

Rector automates Laravel upgrades and refactors with the `driftingly/rector-laravel` extension. This project keeps it enabled by default so framework upgrades and code-quality fixes stay consistent.

## Configuration
- Config lives in `rector.php` and uses `LaravelSetProvider` with `withComposerBased(laravel: true)` to detect framework and first-party package versions automatically.
- Enabled sets: `LARAVEL_CODE_QUALITY`, `LARAVEL_COLLECTION`, `LARAVEL_TESTING`, and `LARAVEL_TYPE_DECLARATIONS` to tighten typing, improve collection usage, and modernize tests.
- Coverage paths: `app`, `app-modules`, `bootstrap/app.php`, `config`, `database`, `lang`, `public`, `routes`, and `tests`. Filament importer hooks remain skipped so Rector does not rewrite dynamic lifecycle methods.
- Guardrail rule: `RemoveDumpDataDeadCodeRector` strips `dd`, `ddd`, `dump`, `ray`, and `var_dump` calls to keep debugging helpers out of committed code.

## Usage
- Apply fixes locally with `composer lint` (runs Rector, then Pint). Use this before committing changes.
- For a read-only pass, run `composer test:refactor` (or `vendor/bin/rector --dry-run`) to view pending refactors without writing files.
- Rector respects the paths above; new Laravel modules under `app-modules` and new route files are processed automatically.

## Extending
- Add more Laravel sets from `RectorLaravel\Set\LaravelSetList` if you need targeted migrations (e.g., array helper conversions or DI enforcement).
- If you introduce project-specific debug helpers, add them to the `RemoveDumpDataDeadCodeRector` configuration so Rector removes them on lint.
