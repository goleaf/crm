# Laravel Translatable Integration

> **📚 Comprehensive Guide**: See `docs/laravel-translatable-integration.md` for complete usage patterns, Filament integration, and best practices.

## Core Principles
- Spatie Laravel Translatable stores translations as JSON in model columns (no separate tables).
- Use `HasTranslations` trait on models that need translatable attributes.
- Define translatable attributes in `public array $translatable = ['name', 'description']`.
- Translations automatically return based on current app locale.
- Database columns must be `json` type (or `text` for older databases).

## Model Setup

### Basic Usage
```php
use Spatie\Translatable\HasTranslations;

class NewsItem extends Model
{
    use HasTranslations;

    public array $translatable = ['name', 'description'];
}
```

### Database Migration
```php
Schema::create('news_items', function (Blueprint $table) {
    $table->id();
    $table->json('name'); // or ->text('name') for older databases
    $table->json('description');
    $table->timestamps();
});
```

## Setting Translations

### Individual Translations
```php
$model->setTranslation('name', 'en', 'English Name');
$model->setTranslation('name', 'nl', 'Dutch Name');
$model->save();
```

### All Translations at Once
```php
$model->setTranslations('name', [
    'en' => 'English Name',
    'nl' => 'Dutch Name',
    'fr' => 'French Name',
]);
$model->save();
```

## Retrieving Translations

### Automatic (Current Locale)
```php
app()->setLocale('en');
$model->name; // Returns 'English Name'

app()->setLocale('nl');
$model->name; // Returns 'Dutch Name'
```

### Specific Locale
```php
$model->getTranslation('name', 'nl'); // Returns 'Dutch Name'
```

### All Translations
```php
$model->getTranslations('name'); 
// Returns ['en' => 'English Name', 'nl' => 'Dutch Name']
```

## Nested JSON Translations

### Setup
```php
public array $translatable = ['meta->description', 'meta->keywords'];
```

### Usage
```php
$model->setTranslation('meta->description', 'en', 'English Description');
$model->{'meta->description'}; // Access with current locale
$model->getTranslation('meta->description', 'nl');
```

## Query Scopes

### Filter by Locale
```php
// Single locale
NewsItem::whereLocale('name', 'en')->get();

// Multiple locales
NewsItem::whereLocales('name', ['en', 'nl'])->get();
```

### Filter by Value
```php
// Exact match
NewsItem::query()
    ->whereJsonContainsLocale('name', 'en', 'English Name')
    ->get();

// LIKE match
NewsItem::query()
    ->whereJsonContainsLocale('name', 'en', 'English%', 'like')
    ->get();
```

## Filament Integration

### Basic Form Field
```php
TextInput::make('name')
    ->label(__('app.labels.name'))
    ->required(),
```

**Note**: Filament forms work with translatable attributes automatically. For multi-locale forms, use locale tabs or custom form components.

### Translatable Form Helper Pattern
```php
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
                                ->required($locale === 'en')
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
```

### Table Column
```php
TextColumn::make('name')
    ->label(__('app.labels.name'))
    ->getStateUsing(fn ($record) => $record->name), // Uses current locale
```

## Common Patterns

### Fallback Locale
```php
$model->getTranslation('name', 'nl', 'en'); // Falls back to 'en' if 'nl' missing
```

### Check Translation Exists
```php
$hasTranslation = $model->hasTranslation('name', 'nl');
```

### Get All Locales
```php
$locales = $model->getTranslatedLocales('name'); // Returns ['en', 'nl']
```

### Forget Translation
```php
$model->forgetTranslation('name', 'nl');
$model->save();
```

## Best Practices

### ✅ Do
- Always define `$translatable` array with type hint: `public array $translatable = ['name']`
- Use `json` column type for modern databases
- Use query scopes (`whereLocale`, `whereLocales`) for filtering
- Provide fallback locale when getting translations
- Use nested JSON notation for complex structures: `'meta->description'`

### ❌ Don't
- Don't forget to add attributes to `$translatable` array
- Don't use `string` type for translatable columns (use `json` or `text`)
- Don't manually query JSON without using query scopes
- Don't assume translation exists (always provide fallback)
- Don't mix translatable and non-translatable in same column

## Testing

### Unit Test Pattern
```php
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
```

## Migration from Non-Translatable

### 1. Change Column Type
```php
Schema::table('news_items', function (Blueprint $table) {
    $table->json('name')->change();
});
```

### 2. Migrate Data
```php
NewsItem::chunk(100, function ($items) {
    foreach ($items as $item) {
        $item->setTranslation('name', 'en', $item->getRawOriginal('name'));
        $item->save();
    }
});
```

## Troubleshooting

### Translation Not Showing
1. Check `$translatable` array includes the attribute
2. Verify column is `json` type
3. Check locale is set: `app()->setLocale('en')`
4. Verify translation exists: `$model->getTranslations('name')`

### JSON Encoding Issues
- Use `json` type for MySQL 5.7+ or PostgreSQL
- Use `text` type for older databases

## Related Documentation
- `docs/laravel-translatable-integration.md` - Complete integration guide
- `docs/laravel-translation-checker-integration.md` - UI translation management
- `.kiro/steering/translations.md` - Translation conventions
- `.kiro/steering/filament-conventions.md` - Filament integration patterns

## Package Information
- **Package**: `spatie/laravel-translatable` v6.12.0
- **Documentation**: https://spatie.be/docs/laravel-translatable
- **GitHub**: https://github.com/spatie/laravel-translatable

