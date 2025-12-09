<?php

declare(strict_types=1);

namespace App\Services\World;

use Illuminate\Support\Collection;
use Nnjeim\World\Models\City;
use Nnjeim\World\Models\Country;
use Nnjeim\World\Models\Currency;
use Nnjeim\World\Models\Language;
use Nnjeim\World\Models\State;
use Nnjeim\World\Models\Timezone;

/**
 * World Data Service
 *
 * Provides a clean interface to access global data (countries, states, cities, currencies, etc.)
 * Uses the nnjeim/world package with caching and service container patterns.
 */
final readonly class WorldDataService
{
    public function __construct(
        private int $cacheTtl = 3600,
    ) {}

    /**
     * Get all countries
     */
    public function getCountries(): Collection
    {
        return cache()->remember(
            'world.countries',
            $this->cacheTtl,
            fn () => Country::query()->orderBy('name')->get(),
        );
    }

    /**
     * Get country by ID or ISO code
     */
    public function getCountry(int|string $identifier, string $column = 'id'): ?Country
    {
        $cacheKey = "world.country.{$column}.{$identifier}";

        return cache()->remember(
            $cacheKey,
            $this->cacheTtl,
            fn () => Country::query()->where($column, $identifier)->first(),
        );
    }

    /**
     * Get states for a country
     */
    public function getStates(int|string $countryId, string $column = 'id'): Collection
    {
        $cacheKey = "world.states.{$column}.{$countryId}";

        return cache()->remember(
            $cacheKey,
            $this->cacheTtl,
            function () use ($countryId, $column) {
                $country = $this->getCountry($countryId, $column);

                if (! $country instanceof \Nnjeim\World\Models\Country) {
                    return collect();
                }

                return State::query()
                    ->where('country_id', $country->id)
                    ->orderBy('name')
                    ->get();
            },
        );
    }

    /**
     * Get state by ID or code
     */
    public function getState(int|string $identifier, string $column = 'id'): ?State
    {
        $cacheKey = "world.state.{$column}.{$identifier}";

        return cache()->remember(
            $cacheKey,
            $this->cacheTtl,
            fn () => State::query()->where($column, $identifier)->first(),
        );
    }

    /**
     * Get cities for a state
     */
    public function getCities(int|string $stateId, string $column = 'id'): Collection
    {
        $cacheKey = "world.cities.{$column}.{$stateId}";

        return cache()->remember(
            $cacheKey,
            $this->cacheTtl,
            function () use ($stateId, $column) {
                $state = $this->getState($stateId, $column);

                if (! $state instanceof \Nnjeim\World\Models\State) {
                    return collect();
                }

                return City::query()
                    ->where('state_id', $state->id)
                    ->orderBy('name')
                    ->get();
            },
        );
    }

    /**
     * Get city by ID or name
     */
    public function getCity(int|string $identifier, string $column = 'id'): ?City
    {
        $cacheKey = "world.city.{$column}.{$identifier}";

        return cache()->remember(
            $cacheKey,
            $this->cacheTtl,
            fn () => City::query()->where($column, $identifier)->first(),
        );
    }

    /**
     * Get all currencies
     */
    public function getCurrencies(): Collection
    {
        return cache()->remember(
            'world.currencies',
            $this->cacheTtl,
            fn () => Currency::query()->orderBy('name')->get(),
        );
    }

    /**
     * Get currency by ID or code
     */
    public function getCurrency(int|string $identifier, string $column = 'id'): ?Currency
    {
        $cacheKey = "world.currency.{$column}.{$identifier}";

        return cache()->remember(
            $cacheKey,
            $this->cacheTtl,
            fn () => Currency::query()->where($column, $identifier)->first(),
        );
    }

    /**
     * Get currencies for a country
     */
    public function getCountryCurrencies(int|string $countryId, string $column = 'id'): Collection
    {
        $cacheKey = "world.country_currencies.{$column}.{$countryId}";

        return cache()->remember(
            $cacheKey,
            $this->cacheTtl,
            function () use ($countryId, $column) {
                $country = $this->getCountry($countryId, $column);

                if (! $country instanceof \Nnjeim\World\Models\Country) {
                    return collect();
                }

                return $country->currencies;
            },
        );
    }

    /**
     * Get all languages
     */
    public function getLanguages(): Collection
    {
        return cache()->remember(
            'world.languages',
            $this->cacheTtl,
            fn () => Language::query()->orderBy('name')->get(),
        );
    }

    /**
     * Get language by ID or code
     */
    public function getLanguage(int|string $identifier, string $column = 'id'): ?Language
    {
        $cacheKey = "world.language.{$column}.{$identifier}";

        return cache()->remember(
            $cacheKey,
            $this->cacheTtl,
            fn () => Language::query()->where($column, $identifier)->first(),
        );
    }

    /**
     * Get languages for a country
     */
    public function getCountryLanguages(int|string $countryId, string $column = 'id'): Collection
    {
        $cacheKey = "world.country_languages.{$column}.{$countryId}";

        return cache()->remember(
            $cacheKey,
            $this->cacheTtl,
            function () use ($countryId, $column) {
                $country = $this->getCountry($countryId, $column);

                if (! $country instanceof \Nnjeim\World\Models\Country) {
                    return collect();
                }

                return $country->languages;
            },
        );
    }

    /**
     * Get all timezones
     */
    public function getTimezones(): Collection
    {
        return cache()->remember(
            'world.timezones',
            $this->cacheTtl,
            fn () => Timezone::query()->orderBy('name')->get(),
        );
    }

    /**
     * Get timezone by ID or name
     */
    public function getTimezone(int|string $identifier, string $column = 'id'): ?Timezone
    {
        $cacheKey = "world.timezone.{$column}.{$identifier}";

        return cache()->remember(
            $cacheKey,
            $this->cacheTtl,
            fn () => Timezone::query()->where($column, $identifier)->first(),
        );
    }

    /**
     * Get timezones for a country
     */
    public function getCountryTimezones(int|string $countryId, string $column = 'id'): Collection
    {
        $cacheKey = "world.country_timezones.{$column}.{$countryId}";

        return cache()->remember(
            $cacheKey,
            $this->cacheTtl,
            function () use ($countryId, $column) {
                $country = $this->getCountry($countryId, $column);

                if (! $country instanceof \Nnjeim\World\Models\Country) {
                    return collect();
                }

                return $country->timezones;
            },
        );
    }

    /**
     * Search countries by name
     */
    public function searchCountries(string $query): Collection
    {
        return Country::query()
            ->where('name', 'like', "%{$query}%")
            ->orWhere('native', 'like', "%{$query}%")
            ->orderBy('name')
            ->limit(20)
            ->get();
    }

    /**
     * Search cities by name
     */
    public function searchCities(string $query, ?int $stateId = null, ?int $countryId = null): Collection
    {
        $queryBuilder = City::query()->where('name', 'like', "%{$query}%");

        if ($stateId) {
            $queryBuilder->where('state_id', $stateId);
        }

        if ($countryId) {
            $queryBuilder->whereHas('state', fn (\Illuminate\Contracts\Database\Query\Builder $q) => $q->where('country_id', $countryId));
        }

        return $queryBuilder->orderBy('name')->limit(50)->get();
    }

    /**
     * Clear all world data cache
     */
    public function clearCache(): void
    {
        cache()->tags(['world'])->flush();
    }

    /**
     * Get popular countries (configurable list)
     */
    public function getPopularCountries(): Collection
    {
        $popularIsoCodes = config('world.popular_countries', ['US', 'GB', 'CA', 'AU', 'DE', 'FR', 'ES', 'IT', 'JP', 'CN']);

        return cache()->remember(
            'world.popular_countries',
            $this->cacheTtl,
            fn () => Country::query()
                ->whereIn('iso2', $popularIsoCodes)
                ->orderBy('name')
                ->get(),
        );
    }

    /**
     * Get countries by region
     */
    public function getCountriesByRegion(string $region): Collection
    {
        $cacheKey = "world.countries.region.{$region}";

        return cache()->remember(
            $cacheKey,
            $this->cacheTtl,
            fn () => Country::query()
                ->where('region', $region)
                ->orderBy('name')
                ->get(),
        );
    }

    /**
     * Get countries by subregion
     */
    public function getCountriesBySubregion(string $subregion): Collection
    {
        $cacheKey = "world.countries.subregion.{$subregion}";

        return cache()->remember(
            $cacheKey,
            $this->cacheTtl,
            fn () => Country::query()
                ->where('subregion', $subregion)
                ->orderBy('name')
                ->get(),
        );
    }

    /**
     * Get all regions
     */
    public function getRegions(): Collection
    {
        return cache()->remember(
            'world.regions',
            $this->cacheTtl,
            fn () => Country::query()
                ->select('region')
                ->distinct()
                ->whereNotNull('region')
                ->orderBy('region')
                ->pluck('region'),
        );
    }

    /**
     * Get country with full details (currencies, languages, timezones)
     */
    public function getCountryWithDetails(int|string $identifier, string $column = 'id'): ?Country
    {
        $cacheKey = "world.country.details.{$column}.{$identifier}";

        return cache()->remember(
            $cacheKey,
            $this->cacheTtl,
            fn () => Country::query()
                ->with(['currencies', 'languages', 'timezones'])
                ->where($column, $identifier)
                ->first(),
        );
    }

    /**
     * Get countries with phone code
     */
    public function getCountriesByPhoneCode(string $phoneCode): Collection
    {
        $cacheKey = "world.countries.phone.{$phoneCode}";

        return cache()->remember(
            $cacheKey,
            $this->cacheTtl,
            fn () => Country::query()
                ->where('phone_code', $phoneCode)
                ->orderBy('name')
                ->get(),
        );
    }

    /**
     * Get EU countries
     */
    public function getEUCountries(): Collection
    {
        $euIsoCodes = ['AT', 'BE', 'BG', 'HR', 'CY', 'CZ', 'DK', 'EE', 'FI', 'FR', 'DE', 'GR', 'HU', 'IE', 'IT', 'LV', 'LT', 'LU', 'MT', 'NL', 'PL', 'PT', 'RO', 'SK', 'SI', 'ES', 'SE'];

        return cache()->remember(
            'world.countries.eu',
            $this->cacheTtl,
            fn () => Country::query()
                ->whereIn('iso2', $euIsoCodes)
                ->orderBy('name')
                ->get(),
        );
    }

    /**
     * Format address for display
     */
    public function formatAddress(
        ?string $street = null,
        ?string $city = null,
        ?string $state = null,
        ?string $postalCode = null,
        ?string $country = null,
    ): string {
        $parts = array_filter([
            $street,
            $city,
            $state,
            $postalCode,
            $country,
        ]);

        return implode(', ', $parts);
    }

    /**
     * Get country flag emoji
     */
    public function getCountryFlag(string $iso2): string
    {
        $iso2 = strtoupper($iso2);
        $offset = 127397;

        return mb_chr(ord($iso2[0]) + $offset) . mb_chr(ord($iso2[1]) + $offset);
    }

    /**
     * Validate postal code format for country
     */
    public function validatePostalCode(string $postalCode, string $countryIso2): bool
    {
        $patterns = [
            'US' => '/^\d{5}(-\d{4})?$/',
            'GB' => '/^[A-Z]{1,2}\d{1,2}[A-Z]?\s?\d[A-Z]{2}$/i',
            'CA' => '/^[A-Z]\d[A-Z]\s?\d[A-Z]\d$/i',
            'AU' => '/^\d{4}$/',
            'DE' => '/^\d{5}$/',
            'FR' => '/^\d{5}$/',
            'IT' => '/^\d{5}$/',
            'ES' => '/^\d{5}$/',
            'NL' => '/^\d{4}\s?[A-Z]{2}$/i',
            'BE' => '/^\d{4}$/',
            'CH' => '/^\d{4}$/',
            'AT' => '/^\d{4}$/',
            'SE' => '/^\d{3}\s?\d{2}$/',
            'NO' => '/^\d{4}$/',
            'DK' => '/^\d{4}$/',
            'FI' => '/^\d{5}$/',
            'PL' => '/^\d{2}-\d{3}$/',
            'CZ' => '/^\d{3}\s?\d{2}$/',
            'PT' => '/^\d{4}-\d{3}$/',
            'IE' => '/^[A-Z]\d{2}\s?[A-Z0-9]{4}$/i',
            'JP' => '/^\d{3}-\d{4}$/',
            'CN' => '/^\d{6}$/',
            'IN' => '/^\d{6}$/',
            'BR' => '/^\d{5}-\d{3}$/',
            'MX' => '/^\d{5}$/',
            'AR' => '/^[A-Z]\d{4}[A-Z]{3}$/i',
            'ZA' => '/^\d{4}$/',
            'NZ' => '/^\d{4}$/',
            'SG' => '/^\d{6}$/',
            'MY' => '/^\d{5}$/',
            'TH' => '/^\d{5}$/',
            'PH' => '/^\d{4}$/',
            'ID' => '/^\d{5}$/',
            'VN' => '/^\d{6}$/',
            'KR' => '/^\d{5}$/',
            'TR' => '/^\d{5}$/',
            'RU' => '/^\d{6}$/',
            'UA' => '/^\d{5}$/',
            'GR' => '/^\d{3}\s?\d{2}$/',
            'RO' => '/^\d{6}$/',
            'HU' => '/^\d{4}$/',
            'SK' => '/^\d{3}\s?\d{2}$/',
            'SI' => '/^\d{4}$/',
            'HR' => '/^\d{5}$/',
            'BG' => '/^\d{4}$/',
            'LT' => '/^\d{5}$/',
            'LV' => '/^LV-\d{4}$/i',
            'EE' => '/^\d{5}$/',
            'CY' => '/^\d{4}$/',
            'MT' => '/^[A-Z]{3}\s?\d{4}$/i',
            'LU' => '/^\d{4}$/',
            'IS' => '/^\d{3}$/',
        ];

        $pattern = $patterns[strtoupper($countryIso2)] ?? null;

        if (! $pattern) {
            return true; // No validation pattern available
        }

        return (bool) preg_match($pattern, $postalCode);
    }

    /**
     * Get distance between two cities (in kilometers)
     * Uses Haversine formula
     */
    public function getDistanceBetweenCities(int $cityId1, int $cityId2): ?float
    {
        $city1 = $this->getCity($cityId1);
        $city2 = $this->getCity($cityId2);

        if (! $city1 || ! $city2 || ! $city1->latitude || ! $city2->latitude) {
            return null;
        }

        $earthRadius = 6371; // km

        $lat1 = deg2rad((float) $city1->latitude);
        $lon1 = deg2rad((float) $city1->longitude);
        $lat2 = deg2rad((float) $city2->latitude);
        $lon2 = deg2rad((float) $city2->longitude);

        $deltaLat = $lat2 - $lat1;
        $deltaLon = $lon2 - $lon1;

        $a = sin($deltaLat / 2) ** 2 + cos($lat1) * cos($lat2) * sin($deltaLon / 2) ** 2;
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadius * $c;
    }
}
