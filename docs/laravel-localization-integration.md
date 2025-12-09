# Laravel Localization Integration

## Overview
- `mcamara/laravel-localization` (v2.3.0) now powers localized URLs and slug translation.
- Supported locales: `en`, `ru`, `lt` (configured in `config/laravellocalization.php`), with default locale hidden from URLs and Accept-Language negotiation enabled.
- Non-localized/system paths are ignored (`/.well-known/*`, `/api/*`, `/filament*`, `/horizon*`, `/livewire*`, `/storage/*`, `/telescope*`, `/translations*`, `/up`).

## Routing
- Wrap public/portal routes in the localization group:

```php
Route::group([
    'prefix' => LaravelLocalization::setLocale(),
    'middleware' => ['localeSessionRedirect', 'localizationRedirect', 'localeViewPath'],
], function () {
    Route::get('/', HomeController::class)->name('home');
    Route::get(LaravelLocalization::transRoute('routes.terms'), TermsOfServiceController::class)
        ->name('terms.show');
});
```

- Translate slugs in `lang/{locale}/routes.php` (see `lang/en|ru|lt|uk/routes.php`) and reference them with `LaravelLocalization::transRoute('routes.key')`; route names stay stable.
- Signed URLs and external callbacks (Socialite, email verification, team invitations) remain outside the localization group to keep canonical paths stable.

## Language Switcher
- `<livewire:language-switcher />` now reads supported locales from the package, uses localized URLs for redirects, and preserves query strings. `Session` is still updated so `SetLocale`/Filament keep the chosen language.

## SetLocale middleware
- `App\Http\Middleware\SetLocale` now prefers the localized URL (via `LaravelLocalization::getCurrentLocale()`), falls back to session → Accept-Language → GeoGenius → defaults, and syncs `LaravelLocalization::setLocale()` + timezone handling.

## Testing
- Coverage for redirects and slug generation lives in `tests/Feature/Localization/LocalizationRoutingTest.php`.
