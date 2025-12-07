<?php

declare(strict_types=1);

use App\Enums\AddressType;
use App\Support\Addresses\AddressValidator;
use Illuminate\Validation\ValidationException;

it('validates and normalizes an address payload', function (): void {
    $validator = new AddressValidator;

    $address = $validator->validate([
        'type' => 'shipping',
        'street' => '456 King St',
        'city' => 'Toronto',
        'state' => 'ON',
        'postal_code' => 'M5V 1L7',
        'country_code' => 'ca',
        'latitude' => '43.0',
        'longitude' => '-79.0',
    ]);

    expect($address->type)->toBe(AddressType::SHIPPING)
        ->and($address->line1)->toBe('456 King St')
        ->and($address->country_code)->toBe('CA')
        ->and($address->postal_code)->toBe('M5V 1L7')
        ->and($address->latitude)->toBe(43.0)
        ->and($address->longitude)->toBe(-79.0);
});

it('rejects invalid postal codes for configured countries', function (): void {
    $validator = new AddressValidator;

    $validator->validate([
        'type' => 'billing',
        'street' => '1 Infinite Loop',
        'city' => 'Cupertino',
        'state' => 'CA',
        'postal_code' => 'INVALID',
        'country_code' => 'US',
    ]);
})->throws(ValidationException::class);
