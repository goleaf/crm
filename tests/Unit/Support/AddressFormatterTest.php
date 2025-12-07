<?php

declare(strict_types=1);

use App\Data\AddressData;
use App\Enums\AddressType;
use App\Support\Addresses\AddressFormatter;

it('formats north american addresses with city state and postal', function (): void {
    $formatter = new AddressFormatter;
    $address = new AddressData(
        type: AddressType::BILLING,
        line1: '123 Market St',
        line2: 'Suite 400',
        city: 'San Francisco',
        state: 'CA',
        postal_code: '94105',
        country_code: 'US',
    );

    $formatted = $formatter->format($address);

    expect($formatted)->toBe('123 Market St, Suite 400, San Francisco, CA 94105, United States');
});

it('formats european addresses with postal code before city', function (): void {
    $formatter = new AddressFormatter;
    $address = new AddressData(
        type: AddressType::SHIPPING,
        line1: '10 Rue de Rivoli',
        line2: null,
        city: 'Paris',
        state: null,
        postal_code: '75001',
        country_code: 'FR',
    );

    $formatted = $formatter->format($address, multiline: true);

    expect($formatted)->toBe("10 Rue de Rivoli\n75001 Paris\nFrance");
});
