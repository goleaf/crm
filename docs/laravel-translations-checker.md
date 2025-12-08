# Laravel Translations Checker Integration

We use `larswiegers/laravel-translations-checker` to ensure all translation keys are present across all supported locales.

## Usage

### Command Line
Run the following command to check for missing translations:

```bash
php artisan translations:check
```

Or via Composer:

```bash
composer test:translations
```

### Filament Admin Panel
You can view the translation status in the Filament admin panel:
1. Navigate to **System** > **Translation Status**.
2. Click **Refresh Status** to run the check.
3. The output will show any missing keys or confirm that all translations are in sync.

## Configuration
The configuration file is located at `config/translations-checker.php`. You can customize:
- `directories`: Directories to scan for translation keys.
- `excluded_directories`: Directories to ignore.
- `supported_locales`: List of locales to check against.

## CI/CD Integration
The translation check is integrated into our testing pipeline. The `composer test` command includes `composer test:translations`, ensuring that no code with missing translations is merged.

## Best Practices
- Always add new translation keys to the English file first.
- Run the checker before committing changes.
- Use the Filament page for a quick check during development.
