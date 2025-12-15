<?php

declare(strict_types=1);

return [
    'default_country' => env('ADDRESS_DEFAULT_COUNTRY', 'US'),

    /**
     * @var array<string, string>
     *
     * @deprecated Use WorldDataService for country lists.
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

    // Postal codes are validated with intervention/validation using the selected country code.

    'geocoding' => [
        'enabled' => env('GEOCODING_ENABLED', false),
        'endpoint' => env('GEOCODING_ENDPOINT'),
        'api_key' => env('GEOCODING_API_KEY'),
        'provider' => env('GEOCODING_PROVIDER', 'nominatim'),
        'timeout' => env('GEOCODING_TIMEOUT', 5),
    ],
];
