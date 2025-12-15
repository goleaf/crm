# Laravel Translation Checker Integration Guide

## Overview

Laravel Translation Checker (`outhebox/laravel-translations`) provides a comprehensive UI for managing translations in your Laravel application. It includes features for viewing, editing, creating, deleting, importing, exporting translations, and integrating with Google Translate API for automated translations.

## Package Information

- **Package**: `outhebox/laravel-translations` v1.4.1
- **Stack**: Inertia.js + Vue 3 (migrated from Livewire in v1.4+)
- **Database Tables**: `ltu_languages`, `ltu_translations`, `ltu_translation_files`, `ltu_phrases`, `ltu_contributors`, `ltu_invites`
- **Routes**: Mounted at `/translations` by default
- **Middleware**: `web`, `auth` (configurable)

## Installation

### 1. Install Package

```bash
composer require outhebox/laravel-translations --dev
```

### 2. Run Installation Command

```bash
php artisan translations:install
```

This will:
- Publish assets to `public/vendor/translations-ui`
- Publish migrations
- Prompt to run migrations

### 3. Import Existing Translations

```bash
php artisan translations:import
```

This scans your `lang/` directory and imports all translation files into the database.

## Configuration

### Environment Variables

```env
# Translation Checker Configuration
TRANSLATIONS_ENABLED=true
TRANSLATIONS_ROUTE_PREFIX=translations
TRANSLATIONS_MIDDLEWARE=web,auth

# Google Translate API (optional)
GOOGLE_TRANSLATE_API_KEY=your-api-key-here

# Cache Configuration
TRANSLATIONS_CACHE_ENABLED=true
TRANSLATIONS_CACHE_TTL=3600
TRANSLATIONS_CACHE_DRIVER=redis
```

### Config File

Publish and customize the configuration:

```bash
php artisan vendor:publish --tag=translations-config
```

Edit `config/translations.php`:

```php
return [
    'enabled' => env('TRANSLATIONS_ENABLED', true),
    
    'route' => [
        'prefix' => env('TRANSLATIONS_ROUTE_PREFIX', 'translations'),
        'middleware' => explode(',', env('TRANSLATIONS_MIDDLEWARE', 'web,auth')),
    ],
    
    'languages' => [
        'default' => 'en',
        'supported' => ['en', 'uk', 'lt', 'ru'],
    ],
    
    'google_translate' => [
        'enabled' => env('GOOGLE_TRANSLATE_ENABLED', false),
        'api_key' => env('GOOGLE_TRANSLATE_API_KEY'),
    ],
    
    'cache' => [
        'enabled' => env('TRANSLATIONS_CACHE_ENABLED', true),
        'ttl' => env('TRANSLATIONS_CACHE_TTL', 3600),
        'driver' => env('TRANSLATIONS_CACHE_DRIVER', 'redis'),
    ],
];
```

## Service Layer Integration

### TranslationCheckerService

Create a service to interact with the Translation Checker:

```php
<?php

declare(strict_types=1);

namespace App\Services\Translation;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

final readonly class TranslationCheckerService
{
    public function __construct(
        private int $cacheTtl = 3600
    ) {}

    /**
     * Get all languages
     */
    public function getLanguages(): Collection
    {
        return Cache::remember(
            'translations.languages',
            $this->cacheTtl,
            fn () => DB::table('ltu_languages')->get()
        );
    }

    /**
     * Get missing translations for a language
     */
    public function getMissingTranslations(string $locale): Collection
    {
        $baseLocale = config('app.locale', 'en');
        
        return DB::table('ltu_translations as base')
            ->leftJoin('ltu_translations as target', function ($join) use ($locale) {
                $join->on('base.phrase_id', '=', 'target.phrase_id')
                     ->where('target.language_id', '=', $this->getLanguageId($locale));
            })
            ->where('base.language_id', $this->getLanguageId($baseLocale))
            ->whereNull('target.id')
            ->select('base.*')
            ->get();
    }

    /**
     * Get translation completion percentage
     */
    public function getCompletionPercentage(string $locale): float
    {
        $baseLocale = config('app.locale', 'en');
        $baseCount = $this->getTranslationCount($baseLocale);
        $targetCount = $this->getTranslationCount($locale);
        
        if ($baseCount === 0) {
            return 100.0;
        }
        
        return round(($targetCount / $baseCount) * 100, 2);
    }

    /**
     * Get translation count for a language
     */
    public function getTranslationCount(string $locale): int
    {
        return DB::table('ltu_translations')
            ->where('language_id', $this->getLanguageId($locale))
            ->count();
    }

    /**
     * Export translations to PHP files
     */
    public function exportToFiles(string $locale): void
    {
        $translations = DB::table('ltu_translations as t')
            ->join('ltu_phrases as p', 't.phrase_id', '=', 'p.id')
            ->join('ltu_translation_files as f', 'p.translation_file_id', '=', 'f.id')
            ->where('t.language_id', $this->getLanguageId($locale))
            ->select('f.name as file', 'p.key', 't.value')
            ->get()
            ->groupBy('file');

        foreach ($translations as $file => $items) {
            $path = lang_path("{$locale}/{$file}.php");
            $directory = dirname($path);

            if (! is_dir($directory)) {
                mkdir($directory, 0755, true);
            }

            $content = "<?php\n\nreturn [\n";
            foreach ($items as $item) {
                $key = addslashes($item->key);
                $value = addslashes($item->value);
                $content .= "    '{$key}' => '{$value}',\n";
            }
            $content .= "];\n";

            file_put_contents($path, $content);
        }

        $this->clearCache();
    }

    /**
     * Import translations from PHP files
     */
    public function importFromFiles(): void
    {
        // Trigger the artisan command
        \Artisan::call('translations:import');
        $this->clearCache();
    }

    /**
     * Clear translation cache
     */
    public function clearCache(): void
    {
        Cache::forget('translations.languages');
        Cache::tags(['translations'])->flush();
    }

    /**
     * Get language ID by locale code
     */
    private function getLanguageId(string $locale): int
    {
        return Cache::remember(
            "translations.language_id.{$locale}",
            $this->cacheTtl,
            fn () => DB::table('ltu_languages')
                ->where('code', $locale)
                ->value('id') ?? 0
        );
    }
}
```

### Register Service

In `app/Providers/AppServiceProvider.php`:

```php
use App\Services\Translation\TranslationCheckerService;

public function register(): void
{
    $this->app->singleton(TranslationCheckerService::class, function ($app) {
        return new TranslationCheckerService(
            cacheTtl: config('translations.cache.ttl', 3600)
        );
    });
}
```

## Filament Integration

### Translation Management Page

Create a Filament page for translation management:

```php
<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use App\Services\Translation\TranslationCheckerService;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

class TranslationManagement extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-language';
    protected static string $view = 'filament.pages.translation-management';
    protected static ?string $navigationGroup = 'System';
    protected static ?int $navigationSort = 100;

    public static function getNavigationLabel(): string
    {
        return __('app.navigation.translations');
    }

    public function getTitle(): string
    {
        return __('app.labels.translation_management');
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('open_ui')
                ->label(__('app.actions.open_translation_ui'))
                ->icon('heroicon-o-arrow-top-right-on-square')
                ->url('/translations', shouldOpenInNewTab: true)
                ->color('primary'),
                
            Action::make('import')
                ->label(__('app.actions.import_translations'))
                ->icon('heroicon-o-arrow-down-tray')
                ->action(function (TranslationCheckerService $service) {
                    $service->importFromFiles();
                    
                    Notification::make()
                        ->title(__('app.notifications.translations_imported'))
                        ->success()
                        ->send();
                })
                ->requiresConfirmation(),
                
            Action::make('export')
                ->label(__('app.actions.export_translations'))
                ->icon('heroicon-o-arrow-up-tray')
                ->form([
                    \Filament\Forms\Components\Select::make('locale')
                        ->label(__('app.labels.language'))
                        ->options(function (TranslationCheckerService $service) {
                            return $service->getLanguages()->pluck('name', 'code');
                        })
                        ->required(),
                ])
                ->action(function (array $data, TranslationCheckerService $service) {
                    $service->exportToFiles($data['locale']);
                    
                    Notification::make()
                        ->title(__('app.notifications.translations_exported'))
                        ->success()
                        ->send();
                }),
        ];
    }

    public static function canAccess(): bool
    {
        return auth()->user()->can('manage_translations');
    }
}
```

### Translation Status Widget

Create a widget to display translation status:

```php
<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Services\Translation\TranslationCheckerService;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class TranslationStatusWidget extends BaseWidget
{
    protected function getStats(): array
    {
        $service = app(TranslationCheckerService::class);
        $languages = $service->getLanguages();
        
        $stats = [];
        
        foreach ($languages as $language) {
            $completion = $service->getCompletionPercentage($language->code);
            $count = $service->getTranslationCount($language->code);
            
            $stats[] = Stat::make(
                $language->name,
                "{$completion}%"
            )
                ->description(__('app.labels.translations_count', ['count' => $count]))
                ->color($completion >= 90 ? 'success' : ($completion >= 50 ? 'warning' : 'danger'))
                ->chart($this->getCompletionTrend($language->code));
        }
        
        return $stats;
    }

    protected function getCompletionTrend(string $locale): array
    {
        // Return historical completion data if tracked
        return [65, 70, 75, 80, 85, 90, 95];
    }
}
```

## Artisan Commands

### Import Translations

```bash
# Import all translation files from lang/ directory
php artisan translations:import

# Import specific language
php artisan translations:import --language=uk

# Force reimport (overwrites existing)
php artisan translations:import --force
```

### Export Translations

```bash
# Export all languages
php artisan translations:export

# Export specific language
php artisan translations:export --language=uk

# Export to custom path
php artisan translations:export --path=/custom/path
```

### Sync Translations

```bash
# Sync database with filesystem
php artisan translations:sync

# Sync specific language
php artisan translations:sync --language=uk
```

### Clean Translations

```bash
# Remove unused translation keys
php artisan translations:clean

# Dry run (preview changes)
php artisan translations:clean --dry-run
```

## Testing

### Feature Tests

```php
<?php

use App\Services\Translation\TranslationCheckerService;
use Illuminate\Support\Facades\DB;
use function Pest\Laravel\artisan;

it('imports translations from files', function () {
    artisan('translations:import')->assertSuccessful();
    
    expect(DB::table('ltu_translations')->count())->toBeGreaterThan(0);
});

it('exports translations to files', function () {
    $service = app(TranslationCheckerService::class);
    $service->exportToFiles('uk');
    
    expect(file_exists(lang_path('uk/app.php')))->toBeTrue();
});

it('calculates completion percentage correctly', function () {
    $service = app(TranslationCheckerService::class);
    $percentage = $service->getCompletionPercentage('uk');
    
    expect($percentage)->toBeGreaterThanOrEqual(0)
        ->and($percentage)->toBeLessThanOrEqual(100);
});

it('identifies missing translations', function () {
    $service = app(TranslationCheckerService::class);
    $missing = $service->getMissingTranslations('uk');
    
    expect($missing)->toBeInstanceOf(\Illuminate\Support\Collection::class);
});
```

### Unit Tests

```php
<?php

use App\Services\Translation\TranslationCheckerService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

beforeEach(function () {
    $this->service = new TranslationCheckerService(cacheTtl: 3600);
});

it('caches language list', function () {
    Cache::shouldReceive('remember')
        ->once()
        ->with('translations.languages', 3600, \Mockery::any())
        ->andReturn(collect([
            (object) ['id' => 1, 'code' => 'en', 'name' => 'English'],
        ]));
    
    $languages = $this->service->getLanguages();
    
    expect($languages)->toHaveCount(1);
});

it('clears cache correctly', function () {
    Cache::shouldReceive('forget')->once()->with('translations.languages');
    Cache::shouldReceive('tags')->once()->with(['translations'])->andReturnSelf();
    Cache::shouldReceive('flush')->once();
    
    $this->service->clearCache();
});
```

## Best Practices

### DO:
- ✅ Use `TranslationCheckerService` for programmatic access
- ✅ Import translations after adding new keys to PHP files
- ✅ Export translations before deploying to production
- ✅ Monitor translation completion percentages
- ✅ Use Google Translate API for initial translations, then review manually
- ✅ Invite collaborators for translation management
- ✅ Cache translation data aggressively
- ✅ Test translation imports/exports in CI/CD

### DON'T:
- ❌ Edit translations directly in database without exporting
- ❌ Skip importing after manual file changes
- ❌ Expose translation UI to unauthorized users
- ❌ Forget to clear cache after bulk changes
- ❌ Rely solely on automated translations
- ❌ Mix database and file-based translation workflows

## Workflow

### Adding New Translations

1. Add translation keys to PHP files in `lang/en/`
2. Run `php artisan translations:import` to sync with database
3. Use Translation UI to translate to other languages
4. Run `php artisan translations:export` to update PHP files
5. Commit updated translation files to version control

### Updating Existing Translations

1. Edit translations in Translation UI
2. Export changes: `php artisan translations:export`
3. Review changes in git diff
4. Commit updated files

### Collaborating on Translations

1. Invite collaborators via Translation UI
2. Assign languages to specific collaborators
3. Collaborators edit translations in UI
4. Export and commit changes regularly

## Integration with Kiro Hooks

### Auto-Translation Hook

The existing Kiro hook for auto-translation can be enhanced to sync with Translation Checker:

```php
// .kiro/hooks/translation-sync.php
use App\Services\Translation\TranslationCheckerService;

return [
    'name' => 'Translation Sync',
    'event' => 'file_saved',
    'pattern' => 'lang/en/**/*.php',
    'action' => function ($file) {
        $service = app(TranslationCheckerService::class);
        $service->importFromFiles();
        
        // Trigger auto-translation for other languages
        // ... existing auto-translation logic
    },
];
```

## Related Documentation

- `.kiro/steering/translations.md` - Translation conventions
- `.kiro/steering/TRANSLATION_GUIDE.md` - Translation implementation guide
- `docs/laravel-container-services.md` - Service pattern guidelines
- `.kiro/steering/filament-conventions.md` - Filament integration patterns

## Package Resources

- [GitHub Repository](https://github.com/MohmmedAshraf/laravel-translations)
- [Package Documentation](https://github.com/MohmmedAshraf/laravel-translations#readme)
- [Laravel News Article](https://laravel-news.com/translation-checker)
