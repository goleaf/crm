<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Application Locale
    |--------------------------------------------------------------------------
    |
    | This value determines the default locale that will be used by the package
    | for translations when auto-detection is not enabled or fails.
    |
    */
    'app_locale' => env('APP_LOCALE', 'en'),

    /*
    |--------------------------------------------------------------------------
    | Auto Translation
    |--------------------------------------------------------------------------
    |
    | Enable or disable automatic translation based on visitor location.
    | If set to true, the package will attempt to detect and apply the
    | visitor's preferred language automatically.
    |
    */
    'translate' => [
        'auto_translate' => (bool) env('GEO_AUTO_TRANSLATE', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Cache Settings
    |--------------------------------------------------------------------------
    |
    | Configure how long geolocation data should be cached to improve
    | performance. Set in minutes or use null for default caching.
    |
    */
    'cache' => [
        'ttl_minutes' => (int) env('GEO_CACHE_TTL_MINUTES', 10_080), // 7 days - cache lifetime in minutes.
    ],

    /*
    |--------------------------------------------------------------------------
    | Phone Input Defaults
    |--------------------------------------------------------------------------
    |
    | Default settings for the international phone input field.
    | You can set the default country, placeholder behavior, and format options.
    |
    */
    'phone_input' => [
        'initial_country' => strtolower((string) env('GEO_PHONE_DEFAULT_COUNTRY', env('ADDRESS_DEFAULT_COUNTRY', 'US'))),
        'only_countries_mode' => (bool) env('GEO_PHONE_ONLY_COUNTRIES_MODE', false),
        'only_countries_array' => array_values(array_filter(array_map(
            static fn (string $country): string => strtolower(trim($country)),
            explode(
                ',',
                (string) env(
                    'GEO_PHONE_ONLY_COUNTRIES',
                    env('GEO_PHONE_DEFAULT_COUNTRY', env('ADDRESS_DEFAULT_COUNTRY', 'US')),
                ),
            ),
        ))),
        'auto_insert_dial_code' => (bool) env('GEO_PHONE_AUTO_INSERT_DIAL_CODE', false),
        'national_mode' => (bool) env('GEO_PHONE_NATIONAL_MODE', false),
        'separate_dial_code' => (bool) env('GEO_PHONE_SEPARATE_DIAL_CODE', false),
        'show_selected_dial_code' => true, // (Optional: don't duplicate inside input)
        'auto_placeholder' => 'off',
    ],
];
