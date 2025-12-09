# Internationalization (i18n) Guide

This application supports multiple languages: English (en), Russian (ru), and Lithuanian (lt).

## Available Languages

- **English (en)** - Default language
- **Russian (ru)** - Русский
- **Lithuanian (lt)** - Lietuvių

## Language Switcher

The language switcher is available in the user menu (top right corner) for authenticated users. It allows users to switch between available languages on the fly.

### How it works:
1. User selects a language from the dropdown
2. The selection is stored in the session
3. The page reloads with the new language
4. All subsequent requests use the selected language

## Translation Files

Translation files are located in the `lang/` directory:

```
lang/
├── en/
│   ├── app.php          # Application-specific translations
│   ├── ui.php           # UI elements (buttons, labels, etc.)
│   └── custom-fields.php # Custom fields translations
├── ru/
│   ├── app.php
│   └── ui.php
└── lt/
    ├── app.php
    └── ui.php
```

## Using Translations in Code

### In PHP/Filament Resources

Use Laravel's `__()` helper function:

```php
// Simple translation
TextInput::make('name')
    ->label(__('ui.labels.name'))
    ->placeholder(__('ui.placeholders.enter_name'))

// With parameters
->helperText(__('ui.messages.showing_results', ['count' => 10]))

// Navigation labels
NavigationGroup::make()
    ->label(__('ui.navigation.dashboard'))
```

### In Blade Templates

```blade
{{-- Simple translation --}}
<h1>{{ __('ui.navigation.dashboard') }}</h1>

{{-- With parameters --}}
<p>{{ __('ui.messages.welcome', ['name' => $user->name]) }}</p>

{{-- Translation choice (pluralization) --}}
<span>{{ trans_choice('ui.pagination.results', $count) }}</span>
```

### In Livewire Components

```php
public function mount()
{
    $this->title = __('ui.navigation.dashboard');
}
```

## Translation File Structure

### ui.php Structure

```php
return [
    'navigation' => [
        'dashboard' => 'Dashboard',
        'leads' => 'Leads',
        // ...
    ],
    
    'actions' => [
        'create' => 'Create',
        'edit' => 'Edit',
        // ...
    ],
    
    'labels' => [
        'name' => 'Name',
        'email' => 'Email',
        // ...
    ],
    
    'placeholders' => [
        'search' => 'Search...',
        'enter_name' => 'Enter name',
        // ...
    ],
    
    'messages' => [
        'success' => [
            'created' => 'Created successfully',
            // ...
        ],
        'error' => [
            'generic' => 'An error occurred',
            // ...
        ],
    ],
];
```

## Adding New Translations

### 1. Add to English (en) first

Edit `lang/en/ui.php` or `lang/en/app.php`:

```php
'labels' => [
    'new_field' => 'New Field',
],
```

### 2. Add to Russian (ru)

Edit `lang/ru/ui.php` or `lang/ru/app.php`:

```php
'labels' => [
    'new_field' => 'Новое поле',
],
```

### 3. Add to Lithuanian (lt)

Edit `lang/lt/ui.php` or `lang/lt/app.php`:

```php
'labels' => [
    'new_field' => 'Naujas laukas',
],
```

## Best Practices

1. **Always use translation keys** instead of hardcoded strings
2. **Keep keys organized** by context (navigation, actions, labels, etc.)
3. **Use descriptive keys** that indicate the context
4. **Add all three languages** when adding new translations
5. **Use parameters** for dynamic content instead of concatenation

### Good Examples

```php
// ✅ Good - uses translation key
->label(__('ui.labels.email'))

// ✅ Good - with parameters
->helperText(__('ui.messages.items_selected', ['count' => $count]))

// ❌ Bad - hardcoded string
->label('Email Address')

// ❌ Bad - concatenation
->label('Selected: ' . $count . ' items')
```

## Filament-Specific Translations

Filament has its own translation files for common UI elements. To override them:

1. Publish Filament translations:
```bash
php artisan vendor:publish --tag=filament-translations
```

2. Edit the published files in `lang/vendor/filament/`

## Testing Translations

1. Switch language using the language switcher
2. Navigate through different pages
3. Check that all labels, buttons, and messages are translated
4. Verify forms, tables, and notifications

## Common Translation Keys

### Navigation
- `ui.navigation.dashboard`
- `ui.navigation.leads`
- `ui.navigation.accounts`

### Actions
- `ui.actions.create`
- `ui.actions.edit`
- `ui.actions.delete`
- `ui.actions.save`
- `ui.actions.cancel`

### Labels
- `ui.labels.name`
- `ui.labels.email`
- `ui.labels.phone`
- `ui.labels.status`

### Messages
- `ui.messages.success.created`
- `ui.messages.success.updated`
- `ui.messages.error.generic`

## Middleware

The `SetLocale` middleware prefers the locale from the localized URL, falls back to the user's session, Accept-Language, then GeoGenius detection, and updates timezone config. It is used by the localization route group and Filament panel provider.

## Localized Routes

- Public routes are wrapped in `LaravelLocalization::setLocale()` with the `localeSessionRedirect`, `localizationRedirect`, and `localeViewPath` middleware in `routes/web.php`.
- Slugs live in `lang/en|ru|lt|uk/routes.php` and are referenced with `LaravelLocalization::transRoute('routes.key')` so route names stay stable while paths can change per locale.
- Configuration: `config/laravellocalization.php` (`hideDefaultLocaleInURL=true`, Accept-Language enabled, ignored system paths). See `docs/laravel-localization-integration.md` for the full setup.
- Signed routes and OAuth callbacks stay outside the localization group to keep canonical URLs unchanged.

## Configuration

Available locales are configured in `config/app.php`:

```php
'available_locales' => ['en', 'ru', 'lt'],
'locale' => env('APP_LOCALE', 'en'),
'fallback_locale' => env('APP_FALLBACK_LOCALE', 'en'),
```

## Troubleshooting

### Translations not showing

1. Clear cache: `php artisan cache:clear`
2. Clear config: `php artisan config:clear`
3. Check translation file syntax
4. Verify the key exists in all language files

### Language not persisting

1. Check session configuration
2. Verify `SetLocale` middleware is registered
3. Clear browser cookies and try again

### Missing translations

If a translation key is missing, Laravel will display the key itself. Always add translations for all supported languages.
