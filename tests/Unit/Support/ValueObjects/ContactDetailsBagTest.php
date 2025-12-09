<?php

declare(strict_types=1);

use App\Enums\AddressType;
use App\Support\ValueObjects\ContactAddressBag;
use App\Support\ValueObjects\ContactDetailsBag;
use Illuminate\Validation\ValidationException;

it('builds a contact details bag with nested address', function () {
    $bag = ContactDetailsBag::from([
        'first_name' => 'Ada',
        'last_name' => 'Lovelace',
        'email' => 'ada@example.test',
        'phone' => '+1 415 555 0100',
        'job_title' => 'CTO',
        'address' => [
            'type' => AddressType::OFFICE->value,
            'line1' => '123 Market St',
            'city' => 'San Francisco',
            'state' => 'CA',
            'postal_code' => '94105',
            'country_code' => 'us',
        ],
    ]);

    expect($bag->address)->toBeInstanceOf(ContactAddressBag::class);
    expect($bag->address->normalizedCountryCode())->toBe('US');
    expect($bag->address->toAddressData()->type)->toBe(AddressType::OFFICE);
    expect($bag->fullName())->toBe('Ada Lovelace');
    expect($bag->initials())->toBe('AL');
    expect($bag->toArray()['address']['country_code'])->toBe('US');
    expect($bag->address->formatted())->toContain('San Francisco');
});

it('validates input via Bag', function () {
    ContactDetailsBag::from([
        'first_name' => 'Ada',
        'last_name' => 'Lovelace',
        'email' => 'not-an-email',
    ]);
})->throws(ValidationException::class);

it('remains immutable when using with', function () {
    $bag = ContactDetailsBag::from([
        'first_name' => 'Ada',
        'last_name' => 'Lovelace',
        'email' => 'ada@example.test',
    ]);

    $updated = $bag->with(email: 'ada+updated@example.test');

    expect($updated)->not->toBe($bag);
    expect($updated->email)->toBe('ada+updated@example.test');
    expect($bag->email)->toBe('ada@example.test');
});
