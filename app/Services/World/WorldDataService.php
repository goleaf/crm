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
        private int $cacheTtl = 3600
    ) {}

    /**
     * Get all countries
     */
    public function getCountries(): Collection
    {
        return cache()->remember(
            'world.countries',
            $this->cacheTtl,
            fn () => Country::query()->orderBy('name')->get()
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
            fn () => Country::query()->where($column, $identifier)->first()
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
            }
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
            fn () => State::query()->where($column, $identifier)->first()
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
            }
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
            fn () => City::query()->where($column, $identifier)->first()
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
            fn () => Currency::query()->orderBy('name')->get()
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
            fn () => Currency::query()->where($column, $identifier)->first()
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
            }
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
            fn () => Language::query()->orderBy('name')->get()
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
            fn () => Language::query()->where($column, $identifier)->first()
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
            }
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
            fn () => Timezone::query()->orderBy('name')->get()
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
            fn () => Timezone::query()->where($column, $identifier)->first()
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
            }
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
                ->get()
        );
    }
}
