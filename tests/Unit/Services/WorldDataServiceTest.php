<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Services\World\WorldDataService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

beforeEach(function (): void {
    Cache::flush();
    $this->worldData = resolve(WorldDataService::class);
});

describe('Countries', function (): void {
    it('retrieves all countries', function (): void {
        $countries = $this->worldData->getCountries();

        expect($countries)->toBeInstanceOf(Collection::class);
        expect($countries)->not->toBeEmpty();
    });

    it('retrieves country by ID', function (): void {
        $countries = $this->worldData->getCountries();
        $firstCountry = $countries->first();

        $country = $this->worldData->getCountry($firstCountry->id);

        expect($country)->not->toBeNull();
        expect($country->id)->toBe($firstCountry->id);
    });

    it('retrieves country by ISO2 code', function (): void {
        $country = $this->worldData->getCountry('US', 'iso2');

        expect($country)->not->toBeNull();
        expect($country->iso2)->toBe('US');
        expect($country->name)->toBe('United States');
    });

    it('retrieves country by ISO3 code', function (): void {
        $country = $this->worldData->getCountry('USA', 'iso3');

        expect($country)->not->toBeNull();
        expect($country->iso3)->toBe('USA');
    });

    it('caches country data', function (): void {
        Cache::flush();

        // First call - hits database
        $country1 = $this->worldData->getCountry('US', 'iso2');

        // Verify cache was set
        expect(Cache::has('world.country.iso2.US'))->toBeTrue();

        // Second call - hits cache
        $country2 = $this->worldData->getCountry('US', 'iso2');

        expect($country1->id)->toBe($country2->id);
    });

    it('searches countries by name', function (): void {
        $results = $this->worldData->searchCountries('United');

        expect($results)->toBeInstanceOf(Collection::class);
        expect($results)->not->toBeEmpty();
        expect($results->first()->name)->toContain('United');
    });

    it('retrieves popular countries', function (): void {
        $popular = $this->worldData->getPopularCountries();

        expect($popular)->toBeInstanceOf(Collection::class);
        expect($popular)->not->toBeEmpty();

        $isoCodes = $popular->pluck('iso2')->toArray();
        expect($isoCodes)->toContain('US');
    });
});

describe('States', function (): void {
    it('retrieves states for a country', function (): void {
        $country = $this->worldData->getCountry('US', 'iso2');
        $states = $this->worldData->getStates($country->id);

        expect($states)->toBeInstanceOf(Collection::class);
        expect($states)->not->toBeEmpty();
        expect($states->first()->country_id)->toBe($country->id);
    });

    it('retrieves states by country ISO2 code', function (): void {
        $states = $this->worldData->getStates('US', 'iso2');

        expect($states)->toBeInstanceOf(Collection::class);
        expect($states)->not->toBeEmpty();
    });

    it('retrieves state by ID', function (): void {
        $country = $this->worldData->getCountry('US', 'iso2');
        $states = $this->worldData->getStates($country->id);
        $firstState = $states->first();

        $state = $this->worldData->getState($firstState->id);

        expect($state)->not->toBeNull();
        expect($state->id)->toBe($firstState->id);
    });

    it('caches state data', function (): void {
        Cache::flush();

        $country = $this->worldData->getCountry('US', 'iso2');

        // First call
        $states1 = $this->worldData->getStates($country->id);

        // Verify cache
        expect(Cache::has("world.states.id.{$country->id}"))->toBeTrue();

        // Second call
        $states2 = $this->worldData->getStates($country->id);

        expect($states1->count())->toBe($states2->count());
    });

    it('returns empty collection for invalid country', function (): void {
        $states = $this->worldData->getStates(999999);

        expect($states)->toBeInstanceOf(Collection::class);
        expect($states)->toBeEmpty();
    });
});

describe('Cities', function (): void {
    it('retrieves cities for a state', function (): void {
        $country = $this->worldData->getCountry('US', 'iso2');
        $states = $this->worldData->getStates($country->id);
        $state = $states->first();

        $cities = $this->worldData->getCities($state->id);

        expect($cities)->toBeInstanceOf(Collection::class);
        // Some states might not have cities in the database
        if ($cities->isNotEmpty()) {
            expect($cities->first()->state_id)->toBe($state->id);
        }
    });

    it('searches cities by name', function (): void {
        $results = $this->worldData->searchCities('New');

        expect($results)->toBeInstanceOf(Collection::class);
    });

    it('searches cities filtered by state', function (): void {
        $country = $this->worldData->getCountry('US', 'iso2');
        $states = $this->worldData->getStates($country->id);
        $state = $states->first();

        $results = $this->worldData->searchCities('', stateId: $state->id);

        expect($results)->toBeInstanceOf(Collection::class);
    });

    it('caches city data', function (): void {
        Cache::flush();

        $country = $this->worldData->getCountry('US', 'iso2');
        $states = $this->worldData->getStates($country->id);
        $state = $states->first();

        // First call
        $cities1 = $this->worldData->getCities($state->id);

        // Verify cache
        expect(Cache::has("world.cities.id.{$state->id}"))->toBeTrue();

        // Second call
        $cities2 = $this->worldData->getCities($state->id);

        expect($cities1->count())->toBe($cities2->count());
    });
});

describe('Currencies', function (): void {
    it('retrieves all currencies', function (): void {
        $currencies = $this->worldData->getCurrencies();

        expect($currencies)->toBeInstanceOf(Collection::class);
        expect($currencies)->not->toBeEmpty();
    });

    it('retrieves currency by code', function (): void {
        $currency = $this->worldData->getCurrency('USD', 'code');

        expect($currency)->not->toBeNull();
        expect($currency->code)->toBe('USD');
    });

    it('retrieves currencies for a country', function (): void {
        $country = $this->worldData->getCountry('US', 'iso2');
        $currencies = $this->worldData->getCountryCurrencies($country->id);

        expect($currencies)->toBeInstanceOf(Collection::class);
    });

    it('caches currency data', function (): void {
        Cache::flush();

        // First call
        $currencies1 = $this->worldData->getCurrencies();

        // Verify cache
        expect(Cache::has('world.currencies'))->toBeTrue();

        // Second call
        $currencies2 = $this->worldData->getCurrencies();

        expect($currencies1->count())->toBe($currencies2->count());
    });
});

describe('Languages', function (): void {
    it('retrieves all languages', function (): void {
        $languages = $this->worldData->getLanguages();

        expect($languages)->toBeInstanceOf(Collection::class);
        expect($languages)->not->toBeEmpty();
    });

    it('retrieves languages for a country', function (): void {
        $country = $this->worldData->getCountry('US', 'iso2');
        $languages = $this->worldData->getCountryLanguages($country->id);

        expect($languages)->toBeInstanceOf(Collection::class);
    });

    it('caches language data', function (): void {
        Cache::flush();

        // First call
        $languages1 = $this->worldData->getLanguages();

        // Verify cache
        expect(Cache::has('world.languages'))->toBeTrue();

        // Second call
        $languages2 = $this->worldData->getLanguages();

        expect($languages1->count())->toBe($languages2->count());
    });
});

describe('Timezones', function (): void {
    it('retrieves all timezones', function (): void {
        $timezones = $this->worldData->getTimezones();

        expect($timezones)->toBeInstanceOf(Collection::class);
        expect($timezones)->not->toBeEmpty();
    });

    it('retrieves timezones for a country', function (): void {
        $country = $this->worldData->getCountry('US', 'iso2');
        $timezones = $this->worldData->getCountryTimezones($country->id);

        expect($timezones)->toBeInstanceOf(Collection::class);
    });

    it('caches timezone data', function (): void {
        Cache::flush();

        // First call
        $timezones1 = $this->worldData->getTimezones();

        // Verify cache
        expect(Cache::has('world.timezones'))->toBeTrue();

        // Second call
        $timezones2 = $this->worldData->getTimezones();

        expect($timezones1->count())->toBe($timezones2->count());
    });
});

describe('Cache Management', function (): void {
    it('clears all world data cache', function (): void {
        // Populate cache
        $this->worldData->getCountries();
        $this->worldData->getCurrencies();
        $this->worldData->getLanguages();

        expect(Cache::has('world.countries'))->toBeTrue();
        expect(Cache::has('world.currencies'))->toBeTrue();
        expect(Cache::has('world.languages'))->toBeTrue();

        // Clear cache
        $this->worldData->clearCache();

        expect(Cache::has('world.countries'))->toBeFalse();
        expect(Cache::has('world.currencies'))->toBeFalse();
        expect(Cache::has('world.languages'))->toBeFalse();
    });
});
