<?php

declare(strict_types=1);

return [
    'default_country' => env('ADDRESS_DEFAULT_COUNTRY', 'US'),

    /**
     * @var array<string, string>
     */
    'countries' => [
        'US' => 'United States',
        'CA' => 'Canada',
        'GB' => 'United Kingdom',
        'AU' => 'Australia',
        'NZ' => 'New Zealand',
        'FR' => 'France',
        'DE' => 'Germany',
        'NL' => 'Netherlands',
        'IE' => 'Ireland',
        'ES' => 'Spain',
        'IT' => 'Italy',
        'IN' => 'India',
        'JP' => 'Japan',
        'SG' => 'Singapore',
        'BR' => 'Brazil',
        'MX' => 'Mexico',
        'ZA' => 'South Africa',
    ],

    /**
     * @var array<string, string>
     */
    'postal_code_patterns' => [
        'US' => '/^\\d{5}(?:-\\d{4})?$/',
        'CA' => '/^[A-Za-z]\\d[A-Za-z][ -]?\\d[A-Za-z]\\d$/',
        'GB' => '/^(GIR 0AA|[A-Z]{1,2}\\d[A-Z\\d]? \\d[ABD-HJLNP-UW-Z]{2})$/i',
        'FR' => '/^\\d{2}[ ]?\\d{3}$/',
        'DE' => '/^\\d{5}$/',
        'NL' => '/^\\d{4}\\s?[A-Za-z]{2}$/',
        'AU' => '/^\\d{4}$/',
        'NZ' => '/^\\d{4}$/',
        'BR' => '/^\\d{5}-?\\d{3}$/',
    ],

    'geocoding' => [
        'enabled' => env('GEOCODING_ENABLED', false),
        'endpoint' => env('GEOCODING_ENDPOINT'),
        'api_key' => env('GEOCODING_API_KEY'),
        'provider' => env('GEOCODING_PROVIDER', 'nominatim'),
        'timeout' => env('GEOCODING_TIMEOUT', 5),
    ],
];
