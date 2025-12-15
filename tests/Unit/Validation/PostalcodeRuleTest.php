<?php

declare(strict_types=1);

use Intervention\Validation\Rules\Postalcode;

it('validates postal code based on referenced country field', function (): void {
    $validator = validator(
        [
            'postal_code' => '12345',
            'country_code' => 'US',
        ],
        [
            'postal_code' => ['nullable', new Postalcode(['us'])],
        ],
    );

    expect($validator->passes())->toBeTrue();
});

it('fails invalid postal code for the referenced country', function (): void {
    $validator = validator(
        [
            'postal_code' => 'ABCDE',
            'country_code' => 'US',
        ],
        [
            'postal_code' => ['nullable', new Postalcode(['us'])],
        ],
    );

    expect($validator->fails())->toBeTrue();
});

it('supports validating against different countries', function (): void {
    $validator = validator(
        [
            'postal_code' => 'K1A 0B1',
            'country_code' => 'CA',
        ],
        [
            'postal_code' => ['nullable', new Postalcode(['ca'])],
        ],
    );

    expect($validator->passes())->toBeTrue();
});

it('treats empty postal code as valid when nullable', function (): void {
    $validator = validator(
        [
            'postal_code' => '',
            'country_code' => 'US',
        ],
        [
            'postal_code' => ['nullable', new Postalcode(['us'])],
        ],
    );

    expect($validator->passes())->toBeTrue();
});

it('registers slug and username validation rules', function (): void {
    $validator = validator(
        [
            'slug' => 'valid-slug',
            'username' => 'Valid_123',
        ],
        [
            'slug' => ['nullable', 'slug'],
            'username' => ['nullable', 'username'],
        ],
    );

    expect($validator->passes())->toBeTrue();
});

it('fails on invalid slug formats', function (): void {
    $validator = validator(
        ['slug' => 'Invalid Slug'],
        ['slug' => ['slug']],
    );

    expect($validator->fails())->toBeTrue();
});
