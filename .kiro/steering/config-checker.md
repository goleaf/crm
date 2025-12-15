# Laravel Config Checker Integration

## Core Principles
- ensure all configuration keys referenced in code actually exist in config files.
- Use `chrisdicarlo/laravel-config-checker` via `php artisan config:check`.
- Integration via `ConfigCheckerService` to expose results to Filament.

## Service Usage
- `ConfigCheckerService` captures Artisan output and provides structured results.
- Cached for 5 minutes by default to prevent heavy disk I/O on every request.
- Use `$service->check()` for fresh results.

## Filament Integration
- **Page**: `App\Filament\Pages\System\ConfigChecker`
- **Widget**: `App\Filament\Widgets\System\ConfigHealthWidget`
- **Permissions**: `manage_system_config`

## Workflow
- Run check before major deployments or refactors.
- integrated into `composer test:config` pipeline.

## Best Practices
- ✅ Check config references after deleting/renaming config keys.
- ✅ Use the Filament page to verify config health in production/staging.
- ❌ Do not ignore missing config keys; they lead to runtime crashes.
