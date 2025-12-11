<?php

declare(strict_types=1);

use App\Data\AddressData;
use App\Enums\AddressType;

test('can create address data', function (): void {
    $address = new AddressData(
        type: AddressType::BILLING,
        line1: '123 Main St',
        line2: 'Apt 4',
        city: 'New York',
        state: 'NY',
        postal_code: '10001',
        country_code: 'US',
        latitude: 40.7128,
        longitude: -74.0060,
        label: 'Home',
    );

    expect($address->type)->toBe(AddressType::BILLING)
        ->and($address->line1)->toBe('123 Main St')
        ->and($address->line2)->toBe('Apt 4')
        ->and($address->city)->toBe('New York')
        ->and($address->state)->toBe('NY')
        ->and($address->postal_code)->toBe('10001')
        ->and($address->country_code)->toBe('US')
        ->and($address->latitude)->toBe(40.7128)
        ->and($address->longitude)->toBe(-74.0060)
        ->and($address->label)->toBe('Home');
});

test('can create address from array', function (): void {
    $payload = [
        'type' => 'billing',
        'line1' => '456 Oak Ave',
        'city' => 'Los Angeles',
        'state' => 'CA',
        'postal_code' => '90001',
        'country_code' => 'US',
    ];

    $address = AddressData::fromArray($payload);

    expect($address->type)->toBe(AddressType::BILLING)
        ->and($address->line1)->toBe('456 Oak Ave')
        ->and($address->city)->toBe('Los Angeles')
        ->and($address->state)->toBe('CA')
        ->and($address->postal_code)->toBe('90001')
        ->and($address->country_code)->toBe('US');
});

test('handles legacy field names in fromArray', function (): void {
    $payload = [
        'street' => '789 Elm St',
        'street2' => 'Suite 100',
        'city' => 'Chicago',
        'province' => 'IL',
        'zip' => '60601',
        'country' => 'US',
    ];

    $address = AddressData::fromArray($payload);

    expect($address->line1)->toBe('789 Elm St')
        ->and($address->line2)->toBe('Suite 100')
        ->and($address->city)->toBe('Chicago')
        ->and($address->state)->toBe('IL')
        ->and($address->postal_code)->toBe('60601')
        ->and($address->country_code)->toBe('US');
});

test('uses fallback type when type not provided', function (): void {
    $payload = ['line1' => '123 Main St'];

    $address = AddressData::fromArray($payload, AddressType::SHIPPING);

    expect($address->type)->toBe(AddressType::SHIPPING);
});

test('converts to legacy array format', function (): void {
    $address = new AddressData(
        type: AddressType::BILLING,
        line1: '123 Main St',
        line2: 'Apt 4',
        city: 'New York',
        state: 'NY',
        postal_code: '10001',
        country_code: 'US',
    );

    $legacy = $address->toLegacyArray();

    expect($legacy)->toHaveKey('street', '123 Main St')
        ->and($legacy)->toHaveKey('street2', 'Apt 4')
        ->and($legacy)->toHaveKey('city', 'New York')
        ->and($legacy)->toHaveKey('state', 'NY')
        ->and($legacy)->toHaveKey('postal_code', '10001')
        ->and($legacy)->toHaveKey('country', 'US');
});

test('filters null values in legacy array', function (): void {
    $address = new AddressData(
        type: AddressType::BILLING,
        line1: '123 Main St',
        country_code: 'US',
    );

    $legacy = $address->toLegacyArray();

    expect($legacy)->not()->toHaveKey('street2')
        ->and($legacy)->not()->toHaveKey('city')
        ->and($legacy)->not()->toHaveKey('state');
});

test('can add coordinates to address', function (): void {
    $address = new AddressData(
        type: AddressType::BILLING,
        line1: '123 Main St',
        country_code: 'US',
    );

    $withCoords = $address->withCoordinates(40.7128, -74.0060);

    expect($withCoords->latitude)->toBe(40.7128)
        ->and($withCoords->longitude)->toBe(-74.0060)
        ->and($withCoords->line1)->toBe('123 Main St');
});

test('detects empty address', function (): void {
    $address = new AddressData(
        type: AddressType::BILLING,
        line1: '',
        country_code: '',
    );

    expect($address->isEmpty())->toBeTrue();
});

test('detects non-empty address', function (): void {
    $address = new AddressData(
        type: AddressType::BILLING,
        line1: '123 Main St',
        country_code: 'US',
    );

    expect($address->isEmpty())->toBeFalse();
});

test('trims whitespace from string fields', function (): void {
    $payload = [
        'line1' => '  123 Main St  ',
        'city' => '  New York  ',
        'state' => '  NY  ',
    ];

    $address = AddressData::fromArray($payload);

    expect($address->line1)->toBe('123 Main St')
        ->and($address->city)->toBe('New York')
        ->and($address->state)->toBe('NY');
});

test('converts empty strings to null', function (): void {
    $payload = [
        'line1' => '123 Main St',
        'line2' => '   ',
        'city' => '',
    ];

    $address = AddressData::fromArray($payload);

    expect($address->line2)->toBeNull()
        ->and($address->city)->toBeNull();
});

test('uppercases country code', function (): void {
    $payload = [
        'line1' => '123 Main St',
        'country_code' => 'us',
    ];

    $address = AddressData::fromArray($payload);

    expect($address->country_code)->toBe('US');
});
