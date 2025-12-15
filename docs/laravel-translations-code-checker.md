# Laravel Translations Code Checker Integration

We use `larswiegers/laravel-translations-checker` to perform static analysis on the codebase, ensuring that all translation keys used in the code actually exist in the translation files.

## Distinction from Translation Management
- **Translation Management (`outhebox`)**: Used for *managing* translations (editing values, database storage, UI).
- **Code Checker (`larswiegers`)**: Used for *verifying* correctness (finding missing keys, finding unused keys).

## Usage

### Command Line
Run the checks via Artisan:

```bash
# Check for missing translations
php artisan translations:check

# Check for unused translations
php artisan translations:check --unused
```

### Filament Integration
The results of these checks are integrated into the **Translation Management** page in Filament.
Navigate to **System > Translations** and look for the "Codebase Health" action/tab.

## Configuration
The configuration is located at `config/translations-checker.php`.

Key settings:
- `directories`: Paths to scan (e.g., `app`, `resources/views`).
- `excluded_directories`: Paths to ignore.

## Best Practices
- Run `php artisan translations:check` before committing code.
- Ignore false positives by adding specific keys to the ignore list in the config.
