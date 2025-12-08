# Laravel GeoGenius integration

GeoGenius now ships with the app for geo-location, timezone, locale detection, and country-aware phone inputs. This note documents how it is wired in and how to use it safely.

## Request lifecycle
- `SetLocale` middleware now resolves locale in this order: session → `Accept-Language` (limited to `config('app.available_locales')`) → GeoGenius detection (`auto_translate` must be enabled) → `config('app.locale')`. The selected locale is stored in session and applied to the request.
- The same middleware resolves timezone from the session or GeoGenius, falls back to `config('app.timezone')`, updates `config('app.timezone')`, and calls `date_default_timezone_set` for the request.
- Login events trigger `BackfillTimezoneFromGeoGenius`, which stores a detected timezone on the user record the first time they sign in (existing values are left intact).

## Frontend phone input
- Base layouts (`resources/views/components/app-layout.blade.php`, `resources/views/layouts/guest.blade.php`) render `laravelGeoGenius()->initIntlPhoneInput()` when the helper is available. This injects the package assets plus a hidden span carrying the detected country code and config flags so Intl Tel Input can bootstrap without manual asset wiring.
- Use the hidden span’s `data-*` attributes (see the helper output) to drive a phone input instance if you need client-side validation.

## Configuration
- `config/laravel-geo-genius.php` mirrors environment flags: `GEO_AUTO_TRANSLATE`, `GEO_CACHE_TTL_MINUTES`, `GEO_PHONE_DEFAULT_COUNTRY`, `GEO_PHONE_ONLY_COUNTRIES_MODE`, `GEO_PHONE_ONLY_COUNTRIES`, `GEO_PHONE_AUTO_INSERT_DIAL_CODE`, `GEO_PHONE_NATIONAL_MODE`, `GEO_PHONE_SEPARATE_DIAL_CODE`.
- Phone defaults use the address default country when `GEO_PHONE_DEFAULT_COUNTRY` is not set and normalize all country codes to lowercase.
- Update `.env` with the above variables when deploying; `GEO_AUTO_TRANSLATE=false` keeps the locale flow constrained to session + `Accept-Language`.

## Database
- A migration adds a nullable `timezone` column to `users`. Run `php artisan migrate` after pulling or include it in deployment pipelines.

## Commands reference
- Publish assets/config (already committed): `php artisan vendor:publish --provider="Devrabiul\\LaravelGeoGenius\\LaravelGeoGeniusServiceProvider"`.
- Geo tooling: `geo:add-language`, `geo:translations-generate`, `geo:translate-language*`, `geo:add-timezone-column {table}`.

## Testing
- Added unit coverage for locale/timezone resolution and timezone backfill. Run `./vendor/bin/pest tests/Unit/Http/Middleware/SetLocaleTest.php tests/Unit/Listeners/BackfillTimezoneFromGeoGeniusTest.php` for a fast check.
