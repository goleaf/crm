# Laravel Translatable Integration

## Overview

Spatie Laravel Translatable (`spatie/laravel-translatable`) provides a simple and elegant way to make Eloquent models translatable. Translations are stored as JSON in the database columns, eliminating the need for separate translation tables.

## Features

- **JSON Storage**: Translations stored directly in model columns as JSON
- **No Extra Tables**: No need for separate translation tables
- **Automatic Locale Detection**: Automatically returns translations based on current app locale
- **Nested JSON Support**: Translate nested keys in JSON columns using dot notation
- **Query Scopes**: Built-in query scopes for filtering by locale
- **Type-Safe**: Full type support with PHP 8.4+

## Installation

The package is already installed via Composer:

```bash
composer require spatie/laravel-translatable
```

No configuration file or service provider registration is required. The package auto-discovers and works out of the box.

## Basic Usage

### Making a Model Translatable

```php
use Illuminate\Database\Eloquent\Model;
use Spatie\Translatable\HasTranslations;

class NewsItem extends Model
{
    use HasTranslations;

    public array $translatable = ['name', 'description'];
}
```

### Database Migration

Ensure your database columns support JSON. Use `json` type for modern databases, or `text` for older ones:

```php
Schema::create('news_items', function (Blueprint $table) {
    $table->id();
    $table->json('name'); // or ->text('name') for older databases
    $table->json('description');
    $table->timestamps();
});
```

### Setting Translations

```php
$newsItem = new NewsItem();
$newsItem
    ->setTranslation('name', 'en', 'Name in English')
    ->setTranslation('name', 'nl', 'Naam in het Nederlands')
    ->setTranslation('description', 'en', 'Description in English')
    ->setTranslation('description', 'nl', 'Beschrijving in het Nederlands')
    ->save();
```

### Retrieving Translations

The package automatically returns the translation for the current app locale:

```php
app()->setLocale('en');
$newsItem->name; // Returns 'Name in English'

app()->setLocale('nl');
$newsItem->name; // Returns 'Naam in het Nederlands'
```

### Getting Specific Translations

```php
// Get translation for a specific locale
$newsItem->getTranslation('name', 'nl'); // Returns 'Naam in het Nederlands'

// Get all translations for an attribute
$newsItem->getTranslations('name'); 
// Returns ['en' => 'Name in English', 'nl' => 'Naam in het Nederlands']
```

### Setting All Translations at Once

```php
$newsItem->setTranslations('name', [
    'en' => 'Name in English',
    'nl' => 'Naam in het Nederlands',
    'fr' => 'Nom en français',
]);
```

## Nested JSON Translations

You can translate nested keys in JSON columns using dot notation:

```php
class NewsItem extends Model
{
    use HasTranslations;

    public array $translatable = ['meta->description', 'meta->keywords'];
}

// Setting nested translations
$newsItem
    ->setTranslation('meta->description', 'en', 'Description in English')
    ->setTranslation('meta->description', 'nl', 'Beschrijving in het Nederlands')
    ->save();

// Accessing nested translations
$newsItem->{'meta->description'}; // Returns translation for current locale
$newsItem->getTranslation('meta->description', 'nl'); // Returns Dutch translation
```

## Query Scopes

The package provides several query scopes for filtering by locale:

### Filter by Single Locale

```php
// Returns all news items with a name in English
NewsItem::whereLocale('name', 'en')->get();
```

### Filter by Multiple Locales

```php
// Returns all news items with a name in English or Dutch
NewsItem::whereLocales('name', ['en', 'nl'])->get();
```

### Filter by Value in Locale

```php
// Returns all news items with name in English equal to 'Name in English'
NewsItem::query()
    ->whereJsonContainsLocale('name', 'en', 'Name in English')
    ->get();

// Returns all news items with name in English or Dutch equal to 'Name in English'
NewsItem::query()
    ->whereJsonContainsLocales('name', ['en', 'nl'], 'Name in English')
    ->get();
```

### Using LIKE Operator

```php
// Returns all news items with name in English like 'Name in%'
NewsItem::query()
    ->whereJsonContainsLocale('name', 'en', 'Name in%', 'like')
    ->get();

// Returns all news items with name in English or Dutch like 'Name in%'
NewsItem::query()
    ->whereJsonContainsLocales('name', ['en', 'nl'], 'Name in%', 'like')
    ->get();
```

## Filament Integration

### Basic Form Field

```php
use Filament\Forms\Components\TextInput;

TextInput::make('name')
    ->label(__('app.labels.name'))
    ->required()
    ->maxLength(255),
```

**Note**: Filament forms work with translatable attributes automatically. When you set a value, it will be stored in the current locale. To support multiple locales in forms, you'll need to use locale-specific tabs or custom form components.

### Using Translatable Tabs (Recommended)

For Filament forms that need to support multiple locales, use locale tabs:

```php
use Filament\Forms\Components\Tabs;
use Filament\Forms\Components\TextInput;

Tabs::make('Translations')
    ->tabs([
        Tabs\Tab::make('English')
            ->schema([
                TextInput::make('name_en')
                    ->label(__('app.labels.name'))
                    ->required()
                    ->afterStateUpdated(function ($state, $set, $get) {
                        // Update the translatable attribute
                        $set('name', array_merge($get('name') ?? [], ['en' => $state]));
                    }),
            ]),
        Tabs\Tab::make('Dutch')
            ->schema([
                TextInput::make('name_nl')
                    ->label(__('app.labels.name'))
                    ->afterStateUpdated(function ($state, $set, $get) {
                        $set('name', array_merge($get('name') ?? [], ['nl' => $state]));
                    }),
            ]),
    ])
```

### Custom Form Component Helper

For a cleaner approach, create a helper method:

```php
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Tabs;

protected function translatableTextInput(string $name, string $label): array
{
    $locales = ['en', 'nl']; // Get from config
    
    return [
        Tabs::make('Translations')
            ->tabs(
                collect($locales)->map(function ($locale) use ($name, $label) {
                    return Tabs\Tab::make(strtoupper($locale))
                        ->schema([
                            TextInput::make("{$name}_{$locale}")
                                ->label($label . " ({$locale})")
                                ->required($locale === 'en') // Require only for default locale
                                ->afterStateUpdated(function ($state, $set, $get) use ($name, $locale) {
                                    $current = $get($name) ?? [];
                                    $current[$locale] = $state;
                                    $set($name, $current);
                                })
                                ->default(fn ($get) => $get($name)[$locale] ?? null),
                        ]);
                })->toArray()
            ),
    ];
}

// Usage
public function form(Form $form): Form
{
    return $form
        ->schema([
            ...$this->translatableTextInput('name', __('app.labels.name')),
            ...$this->translatableTextInput('description', __('app.labels.description')),
        ]);
}
```

### Table Column Display

```php
use Filament\Tables\Columns\TextColumn;

TextColumn::make('name')
    ->label(__('app.labels.name'))
    ->getStateUsing(fn ($record) => $record->name), // Automatically uses current locale
```

### Infolist Display

```php
use Filament\Infolists\Components\TextEntry;

TextEntry::make('name')
    ->label(__('app.labels.name')),
```

## Common Patterns

### Fallback Locale

The package automatically falls back to the default locale if a translation is missing:

```php
// If 'nl' translation doesn't exist, falls back to 'en'
$newsItem->getTranslation('name', 'nl', 'en');
```

### Checking if Translation Exists

```php
$hasTranslation = $newsItem->hasTranslation('name', 'nl');
```

### Getting All Locales for an Attribute

```php
$locales = $newsItem->getTranslatedLocales('name');
// Returns ['en', 'nl']
```

### Forgetting a Translation

```php
$newsItem->forgetTranslation('name', 'nl');
$newsItem->save();
```

### Getting Translation with Fallback

```php
// Returns translation in 'nl', or falls back to 'en', or returns null
$translation = $newsItem->getTranslation('name', 'nl', 'en', true);
```

## Best Practices

### 1. Always Define Translatable Attributes

```php
public array $translatable = ['name', 'description'];
```

### 2. Use JSON Columns in Database

```php
$table->json('name'); // Preferred
// or
$table->text('name'); // For older databases
```

### 3. Set Default Locale

Ensure your application has a default locale set in `config/app.php`:

```php
'locale' => 'en',
'fallback_locale' => 'en',
```

### 4. Use Query Scopes for Filtering

```php
// Good: Use query scopes
NewsItem::whereLocale('name', 'en')->get();

// Avoid: Manual JSON queries when possible
NewsItem::whereJsonContains('name->en', 'value')->get();
```

### 5. Handle Missing Translations Gracefully

```php
// Always provide fallback
$name = $newsItem->getTranslation('name', app()->getLocale(), 'en') ?? 'Untitled';
```

### 6. Use Type Hints

```php
public array $translatable = ['name']; // Type hint ensures translatable attributes are arrays
```

## Testing

### Unit Tests

```php
use Tests\TestCase;
use App\Models\NewsItem;

it('stores translations correctly', function () {
    $item = NewsItem::factory()->create();
    
    $item->setTranslation('name', 'en', 'English Name');
    $item->setTranslation('name', 'nl', 'Dutch Name');
    $item->save();
    
    expect($item->getTranslation('name', 'en'))->toBe('English Name');
    expect($item->getTranslation('name', 'nl'))->toBe('Dutch Name');
});

it('returns translation for current locale', function () {
    app()->setLocale('nl');
    
    $item = NewsItem::factory()->create();
    $item->setTranslation('name', 'en', 'English Name');
    $item->setTranslation('name', 'nl', 'Dutch Name');
    $item->save();
    
    expect($item->name)->toBe('Dutch Name');
});

it('filters by locale', function () {
    NewsItem::factory()->create([
        'name' => ['en' => 'English', 'nl' => 'Dutch'],
    ]);
    
    NewsItem::factory()->create([
        'name' => ['en' => 'English Only'],
    ]);
    
    $items = NewsItem::whereLocale('name', 'nl')->get();
    
    expect($items)->toHaveCount(1);
});
```

## Migration from Non-Translatable Models

If you're migrating existing models to use translations:

### 1. Create Migration

```php
Schema::table('news_items', function (Blueprint $table) {
    $table->json('name')->change();
    $table->json('description')->change();
});
```

### 2. Migrate Existing Data

```php
use App\Models\NewsItem;

NewsItem::chunk(100, function ($items) {
    foreach ($items as $item) {
        $item->setTranslation('name', 'en', $item->getRawOriginal('name'));
        $item->setTranslation('description', 'en', $item->getRawOriginal('description'));
        $item->save();
    }
});
```

## Performance Considerations

### Indexing JSON Columns

For better query performance, consider adding indexes on JSON columns:

```php
Schema::table('news_items', function (Blueprint $table) {
    $table->index('name');
    $table->index('description');
});
```

### Caching Translations

For frequently accessed translations, consider caching:

```php
use Illuminate\Support\Facades\Cache;

$name = Cache::remember(
    "news_item.{$id}.name." . app()->getLocale(),
    3600,
    fn () => $newsItem->name
);
```

## Troubleshooting

### Translation Not Showing

1. Check that the attribute is in `$translatable` array
2. Verify the database column is JSON type
3. Ensure the locale is set correctly: `app()->setLocale('en')`
4. Check that the translation exists: `$model->getTranslations('name')`

### JSON Encoding Issues

If you see JSON encoding errors, ensure your database column supports JSON:

```php
// For MySQL 5.7+ or PostgreSQL
$table->json('name');

// For older databases
$table->text('name');
```

### Query Scope Not Working

Ensure you're using the correct method:

```php
// Correct
NewsItem::whereLocale('name', 'en')->get();

// Incorrect - this won't work
NewsItem::where('name->en', 'value')->get();
```

## Related Documentation

- [Spatie Laravel Translatable Documentation](https://spatie.be/docs/laravel-translatable)
- `docs/laravel-translation-checker-integration.md` - UI translation management
- `.kiro/steering/translations.md` - Translation conventions
- `.kiro/steering/laravel-translatable.md` - Quick reference

## Package Information

- **Package**: `spatie/laravel-translatable` v6.12.0
- **License**: MIT
- **Documentation**: https://spatie.be/docs/laravel-translatable
- **GitHub**: https://github.com/spatie/laravel-translatable

