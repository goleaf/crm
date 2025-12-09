<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use DateTimeZone;
use Devrabiul\LaravelGeoGenius\LaravelGeoGenius;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Session;
use Mcamara\LaravelLocalization\Facades\LaravelLocalization;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

final readonly class SetLocale
{
    public function __construct(private LaravelGeoGenius $geoGenius) {}

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $availableLocales = $this->availableLocales();
        $defaultLocale = config('app.locale', 'en');
        $locale = $this->resolveLocale($request, $availableLocales, $defaultLocale);
        $timezone = $this->resolveTimezone();

        LaravelLocalization::setLocale($locale);
        App::setLocale($locale);
        Session::put('locale', $locale);

        if ($timezone !== null) {
            config(['app.timezone' => $timezone]);
            date_default_timezone_set($timezone);
            Session::put('timezone', $timezone);
        }

        return $next($request);
    }

    private function resolveLocale(Request $request, array $availableLocales, string $defaultLocale): string
    {
        $localizedRouteLocale = LaravelLocalization::getCurrentLocale();

        if ($this->isSupportedLocale($localizedRouteLocale, $availableLocales)) {
            return $this->normalizeLocale($localizedRouteLocale);
        }

        $sessionLocale = Session::get('locale');

        if ($this->isSupportedLocale($sessionLocale, $availableLocales)) {
            return (string) $sessionLocale;
        }

        $preferredLocale = $request->getPreferredLanguage($availableLocales);

        if ($this->isSupportedLocale($preferredLocale, $availableLocales)) {
            return (string) $preferredLocale;
        }

        if (config('laravel-geo-genius.translate.auto_translate', false)) {
            $detectedLocale = $this->geoGenius->language()->detect()
                ?? $this->geoGenius->language()->getUserLanguage();

            if ($this->isSupportedLocale($detectedLocale, $availableLocales)) {
                return $this->normalizeLocale($detectedLocale);
            }
        }

        return $defaultLocale;
    }

    private function availableLocales(): array
    {
        $appLocales = config('app.available_locales', []);

        if ($appLocales === []) {
            $appLocales = array_keys(config('laravellocalization.supportedLocales', []));
        }

        if ($appLocales === []) {
            return ['en'];
        }

        return array_values(array_unique($appLocales));
    }

    private function isSupportedLocale(mixed $locale, array $availableLocales): bool
    {
        if (! is_string($locale) || $locale === '') {
            return false;
        }

        return in_array($this->normalizeLocale($locale), $availableLocales, true);
    }

    private function normalizeLocale(?string $locale): string
    {
        return str_replace('_', '-', substr((string) $locale, 0, 2));
    }

    private function resolveTimezone(): ?string
    {
        $sessionTimezone = Session::get('timezone');

        if ($this->isValidTimezone($sessionTimezone)) {
            return $sessionTimezone;
        }

        $detectedTimezone = $this->geoGenius->timezone()->getUserTimezone();

        if ($this->isValidTimezone($detectedTimezone)) {
            return $detectedTimezone;
        }

        return config('app.timezone');
    }

    private function isValidTimezone(mixed $timezone): bool
    {
        if (! is_string($timezone) || $timezone === '') {
            return false;
        }

        try {
            new DateTimeZone($timezone);

            return true;
        } catch (Throwable) {
            return false;
        }
    }
}
