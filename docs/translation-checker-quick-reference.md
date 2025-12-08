# Translation Checker - Quick Reference

## Quick Start

### Access Translation UI
```
https://your-app.com/translations
```

### Import Translations
```bash
php artisan translations:import
```

### Export Translations
```bash
php artisan translations:export
```

## Common Commands

| Command | Description |
|---------|-------------|
| `translations:import` | Import all translation files from `lang/` directory |
| `translations:import --language=uk` | Import specific language |
| `translations:export` | Export all languages to PHP files |
| `translations:export --language=uk` | Export specific language |
| `translations:sync` | Sync database with filesystem |
| `translations:clean` | Remove unused translation keys |

## Service Usage

### Inject Service
```php
use App\Services\Translation\TranslationCheckerService;

public function __construct(
    private readonly TranslationCheckerService $translations
) {}
```

### Get Languages
```php
$languages = $this->translations->getLanguages();
```

### Get Completion Percentage
```php
$percentage = $this->translations->getCompletionPercentage('uk');
```

### Get Missing Translations
```php
$missing = $this->translations->getMissingTranslations('uk');
```

### Get Statistics
```php
$stats = $this->translations->getStatistics();
```

### Export to Files
```php
$this->translations->exportToFiles('uk');
```

### Import from Files
```php
$this->translations->importFromFiles();
```

### Clear Cache
```php
$this->translations->clearCache();
```

## Filament Integration

### Translation Management Page
```
Settings → Translations
```

### Translation Status Widget
Add to dashboard:
```php
protected function getHeaderWidgets(): array
{
    return [
        TranslationStatusWidget::class,
    ];
}
```

## Workflow

### Adding New Translations
1. Add keys to `lang/en/app.php`
2. Run `php artisan translations:import`
3. Translate in UI at `/translations`
4. Run `php artisan translations:export`
5. Commit files

### Updating Translations
1. Edit in UI at `/translations`
2. Run `php artisan translations:export`
3. Review git diff
4. Commit files

## Translation Keys

### Navigation
```php
__('app.navigation.translations')
__('app.navigation.system')
```

### Labels
```php
__('app.translations.management')
__('app.translations.statistics')
__('app.translations.completion')
__('app.translations.missing')
```

### Actions
```php
__('app.translations.import_translations')
__('app.translations.export_translations')
__('app.translations.open_ui')
```

### Notifications
```php
__('app.translations.translations_imported')
__('app.translations.translations_exported')
```

## Permissions

### Required Permissions
- `manage_translations` - Manage translations
- `view_translation_statistics` - View statistics

### Grant Permissions
```php
$user->givePermissionTo('manage_translations');
$user->givePermissionTo('view_translation_statistics');
```

## Configuration

### Environment Variables
```env
TRANSLATIONS_ENABLED=true
TRANSLATIONS_ROUTE_PREFIX=translations
TRANSLATIONS_MIDDLEWARE=web,auth
GOOGLE_TRANSLATE_API_KEY=your-key
TRANSLATIONS_CACHE_TTL=3600
TRANSLATIONS_CACHE_DRIVER=redis
```

## Testing

### Run Tests
```bash
# Unit tests
pest tests/Unit/Services/Translation/TranslationCheckerServiceTest.php

# Feature tests
pest tests/Feature/Translation/TranslationCheckerTest.php

# All tests
composer test
```

### Mock Service
```php
$this->mock(TranslationCheckerService::class)
    ->shouldReceive('getLanguages')
    ->andReturn(collect([...]));
```

## Troubleshooting

### Translations Not Showing
```bash
php artisan translations:import
php artisan cache:clear
```

### Export Not Working
```bash
php artisan translations:export --language=en
php artisan optimize:clear
```

### Cache Issues
```bash
php artisan cache:clear
php artisan config:clear
```

## Database Tables

| Table | Purpose |
|-------|---------|
| `ltu_languages` | Supported languages |
| `ltu_translations` | Translation values |
| `ltu_translation_files` | File metadata |
| `ltu_phrases` | Translation keys |
| `ltu_contributors` | Collaborators |
| `ltu_invites` | Pending invitations |

## Best Practices

### DO
- ✅ Import after adding new keys
- ✅ Export before deploying
- ✅ Use Translation UI for editing
- ✅ Monitor completion percentages
- ✅ Cache aggressively
- ✅ Test imports/exports

### DON'T
- ❌ Edit database directly
- ❌ Skip importing after file changes
- ❌ Forget to export before deploy
- ❌ Ignore cache clearing
- ❌ Mix database and file workflows

## Resources

- **Documentation**: `docs/laravel-translation-checker-integration.md`
- **Steering**: `.kiro/steering/translation-checker.md`
- **GitHub**: https://github.com/MohmmedAshraf/laravel-translations
- **Laravel News**: https://laravel-news.com/translation-checker

## Support

1. Check documentation
2. Review steering files
3. Consult package docs
4. Run `--help` on commands
