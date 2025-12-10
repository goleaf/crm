<?php

declare(strict_types=1);

use App\Services\World\WorldDataService;
use Illuminate\Support\Facades\Cache;

beforeEach(function (): void {
    $this->service = resolve(WorldDataService::class);
});

describe('Regional Filtering', function (): void {
    it('gets countries by region', function (): void {
        Cache::shouldReceive('remember')
            ->once()
            ->andReturn(collect([
                (object) ['id' => 1, 'name' => 'France', 'region' => 'Europe'],
                (object) ['id' => 2, 'name' => 'Germany', 'region' => 'Europe'],
            ]));

        $countries = $this->service->getCountriesByRegion('Europe');

        expect($countries)->toHaveCount(2);
    });

    it('gets countries by subregion', function (): void {
        Cache::shouldReceive('remember')
            ->once()
            ->andReturn(collect([
                (object) ['id' => 1, 'name' => 'Thailand', 'subregion' => 'South-Eastern Asia'],
            ]));

        $countries = $this->service->getCountriesBySubregion('South-Eastern Asia');

        expect($countries)->toHaveCount(1);
    });

    it('gets all regions', function (): void {
        Cache::shouldReceive('remember')
            ->once()
            ->andReturn(collect(['Europe', 'Asia', 'Americas', 'Africa', 'Oceania']));

        $regions = $this->service->getRegions();

        expect($regions)->toHaveCount(5);
    });

    it('gets EU countries', function (): void {
        Cache::shouldReceive('remember')
            ->once()
            ->andReturn(collect([
                (object) ['id' => 1, 'name' => 'France', 'iso2' => 'FR'],
                (object) ['id' => 2, 'name' => 'Germany', 'iso2' => 'DE'],
            ]));

        $euCountries = $this->service->getEUCountries();

        expect($euCountries)->toHaveCount(2);
    });
});

describe('Enhanced Lookups', function (): void {
    it('gets countries by phone code', function (): void {
        Cache::shouldReceive('remember')
            ->once()
            ->andReturn(collect([
                (object) ['id' => 1, 'name' => 'United States', 'phone_code' => '+1'],
                (object) ['id' => 2, 'name' => 'Canada', 'phone_code' => '+1'],
            ]));

        $countries = $this->service->getCountriesByPhoneCode('+1');

        expect($countries)->toHaveCount(2);
    });

    it('gets country with full details', function (): void {
        // Skip this test as it requires database access
        $this->markTestSkipped('Requires database access for Country model');
    });
});

describe('Address Utilities', function (): void {
    it('formats address correctly', function (): void {
        $formatted = $this->service->formatAddress(
            street: '123 Main St',
            city: 'New York',
            state: 'NY',
            postalCode: '10001',
            country: 'United States',
        );

        expect($formatted)->toBe('123 Main St, New York, NY, 10001, United States');
    });

    it('formats address with null values', function (): void {
        $formatted = $this->service->formatAddress(
            street: '123 Main St',
            city: 'New York',
            state: null,
            postalCode: null,
            country: 'United States',
        );

        expect($formatted)->toBe('123 Main St, New York, United States');
    });

    it('returns country flag emoji', function (): void {
        expect($this->service->getCountryFlag('US'))->toBe('🇺🇸');
        expect($this->service->getCountryFlag('GB'))->toBe('🇬🇧');
        expect($this->service->getCountryFlag('FR'))->toBe('🇫🇷');
        expect($this->service->getCountryFlag('DE'))->toBe('🇩🇪');
        expect($this->service->getCountryFlag('CA'))->toBe('🇨🇦');
    });
});

describe('Postal Code Validation', function (): void {
    it('validates US postal codes', function (): void {
        expect($this->service->validatePostalCode('10001', 'US'))->toBeTrue();
        expect($this->service->validatePostalCode('10001-1234', 'US'))->toBeTrue();
        expect($this->service->validatePostalCode('ABC123', 'US'))->toBeFalse();
        expect($this->service->validatePostalCode('1234', 'US'))->toBeFalse();
    });

    it('validates UK postal codes', function (): void {
        expect($this->service->validatePostalCode('SW1A 1AA', 'GB'))->toBeTrue();
        expect($this->service->validatePostalCode('SW1A1AA', 'GB'))->toBeTrue();
        expect($this->service->validatePostalCode('M1 1AE', 'GB'))->toBeTrue();
        expect($this->service->validatePostalCode('12345', 'GB'))->toBeFalse();
    });

    it('validates Canadian postal codes', function (): void {
        expect($this->service->validatePostalCode('K1A 0B1', 'CA'))->toBeTrue();
        expect($this->service->validatePostalCode('K1A0B1', 'CA'))->toBeTrue();
        expect($this->service->validatePostalCode('12345', 'CA'))->toBeFalse();
    });

    it('validates German postal codes', function (): void {
        expect($this->service->validatePostalCode('10115', 'DE'))->toBeTrue();
        expect($this->service->validatePostalCode('1234', 'DE'))->toBeFalse();
        expect($this->service->validatePostalCode('123456', 'DE'))->toBeFalse();
    });

    it('validates French postal codes', function (): void {
        expect($this->service->validatePostalCode('75001', 'FR'))->toBeTrue();
        expect($this->service->validatePostalCode('1234', 'FR'))->toBeFalse();
    });

    it('returns true for countries without validation pattern', function (): void {
        expect($this->service->validatePostalCode('ANY123', 'XX'))->toBeTrue();
    });
});

describe('Distance Calculation', function (): void {
    it('calculates distance between cities', function (): void {
        // Skip this test as it requires database access
        $this->markTestSkipped('Requires database access for City model');
    });

    it('returns null when cities have no coordinates', function (): void {
        // Skip this test as it requires database access
        $this->markTestSkipped('Requires database access for City model');
    });

    it('returns null when city not found', function (): void {
        // Skip this test as it requires database access
        $this->markTestSkipped('Requires database access for City model');
    });
});