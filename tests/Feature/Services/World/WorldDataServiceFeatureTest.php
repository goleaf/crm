<?php

declare(strict_types=1);

use App\Services\World\WorldDataService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Nnjeim\World\Models\City;
use Nnjeim\World\Models\Country;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->service = resolve(WorldDataService::class);

    // Seed some test data
    $this->artisan('world:install');
});

describe('Distance Calculation with Database', function (): void {
    it('calculates distance between real cities', function (): void {
        // Get two cities from database
        $newYork = City::where('name', 'New York')->first();
        $losAngeles = City::where('name', 'Los Angeles')->first();

        if (! $newYork || ! $losAngeles) {
            $this->markTestSkipped('Test cities not found in database');
        }

        $distance = $this->service->getDistanceBetweenCities($newYork->id, $losAngeles->id);

        expect($distance)->toBeFloat();
        expect($distance)->toBeGreaterThan(3000); // ~3,900 km
        expect($distance)->toBeLessThan(5000);
    })->skip('Requires world data to be seeded');

    it('returns null when city has no coordinates', function (): void {
        // Create a city without coordinates
        $country = Country::first();
        $state = $country?->states()->first();

        if (! $state) {
            $this->markTestSkipped('No state found for testing');
        }

        $city = City::create([
            'name' => 'Test City',
            'state_id' => $state->id,
            'latitude' => null,
            'longitude' => null,
        ]);

        $otherCity = City::where('latitude', '!=', null)->first();

        if (! $otherCity) {
            $this->markTestSkipped('No city with coordinates found');
        }

        $distance = $this->service->getDistanceBetweenCities($city->id, $otherCity->id);

        expect($distance)->toBeNull();
    })->skip('Requires world data to be seeded');
});

describe('Country Details with Database', function (): void {
    it('gets country with full details from database', function (): void {
        $country = Country::where('iso2', 'US')->first();

        if (! $country) {
            $this->markTestSkipped('US country not found in database');
        }

        $countryWithDetails = $this->service->getCountryWithDetails('US', 'iso2');

        expect($countryWithDetails)->not->toBeNull();
        expect($countryWithDetails->name)->toBe($country->name);
        expect($countryWithDetails->relationLoaded('currencies'))->toBeTrue();
        expect($countryWithDetails->relationLoaded('languages'))->toBeTrue();
        expect($countryWithDetails->relationLoaded('timezones'))->toBeTrue();
    })->skip('Requires world data to be seeded');
});
