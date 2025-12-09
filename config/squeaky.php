<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Custom Words Configuration
    |--------------------------------------------------------------------------
    |
    | This configuration allows you to specify custom words that should be
    | treated as profanity or explicitly allowed in your application.
    |
    | The 'blocked_words' array contains words that will be treated as
    | profanity regardless of the locale-specific profanity lists.
    |
    | The 'allowed_words' array contains words that will be explicitly
    | allowed even if they appear in the locale-specific profanity lists.
    |
    */

    'blocked_words' => [
        // Add custom words specific to your application that should be blocked
        // Example: 'company_secret', 'internal_term', 'restricted_word'
    ],

    'allowed_words' => [
        // Add custom words specific to your application that should be allowed
        // Example: 'analytics', 'scunthorpe', 'penistone'
    ],

    /*
    |--------------------------------------------------------------------------
    | Case Sensitivity
    |--------------------------------------------------------------------------
    |
    | Determines whether word matching should be case-sensitive.
    | Set to false for case-insensitive matching (recommended).
    |
    */
    'case_sensitive' => false,

    /*
    |--------------------------------------------------------------------------
    | Locales
    |--------------------------------------------------------------------------
    |
    | Comma-separated locales to scan for profanity. Defaults to English but
    | supports ar, da, de, en, es, it, ja, nl, pt_BR, and ru. The fallback is
    | appended when it is not already present.
    |
    */
    'locales' => array_values(array_unique(array_filter(
        array_map(trim(...), explode(',', (string) env('SQUEAKY_LOCALES', 'en'))),
    ))),

    'fallback_locale' => env('SQUEAKY_FALLBACK_LOCALE', 'en'),
];
