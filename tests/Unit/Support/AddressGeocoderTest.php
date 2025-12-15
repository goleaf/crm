<?php

declare(strict_types=1);

use App\Data\AddressData;
use App\Enums\AddressType;
use App\Support\Addresses\AddressGeocoder;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

afterEach(function (): void {
    Mockery::close();
});

it('skips geocoding when disabled', function (): void {
    config()->set('address.geocoding.enabled', false);

    $client = Mockery::mock(HttpClientInterface::class);
    $client->shouldNotReceive('request');

    $geocoder = new AddressGeocoder($client);
    $address = new AddressData(
        type: AddressType::BILLING,
        line1: '1600 Amphitheatre Parkway',
        city: 'Mountain View',
        state: 'CA',
        postal_code: '94043',
        country_code: 'US',
    );

    $result = $geocoder->geocode($address);

    expect($result->latitude)->toBeNull()
        ->and($result->longitude)->toBeNull();
});

it('hydrates coordinates when provider is enabled', function (): void {
    config()->set('address.geocoding', [
        'enabled' => true,
        'endpoint' => 'https://example.test/geocode',
        'api_key' => 'test-key',
        'provider' => 'nominatim',
        'timeout' => 3,
    ]);

    $response = Mockery::mock(ResponseInterface::class);
    $response->shouldReceive('toArray')->with(false)->andReturn([
        'lat' => '10.1234',
        'lon' => '-20.5678',
    ]);

    $client = Mockery::mock(HttpClientInterface::class);
    $client->shouldReceive('request')
        ->once()
        ->with('GET', 'https://example.test/geocode', Mockery::on(function ($options): bool {
            if (! is_array($options)) {
                return false;
            }

            $query = $options['query'] ?? [];

            return ($query['q'] ?? null) === '123 Main St, New York, NY 10001, United States'
                && $query['api_key'] === 'test-key';
        }))
        ->andReturn($response);

    $geocoder = new AddressGeocoder($client);
    $address = new AddressData(
        type: AddressType::SHIPPING,
        line1: '123 Main St',
        city: 'New York',
        state: 'NY',
        postal_code: '10001',
        country_code: 'US',
    );

    $result = $geocoder->geocode($address);

    expect($result->latitude)->toBe(10.1234)
        ->and($result->longitude)->toBe(-20.5678);
});
