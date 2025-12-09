<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Supported Locales
    |--------------------------------------------------------------------------
    |
    | Keep this list aligned with the app's available locales so URL
    | generation, middleware, and the language switcher stay in sync.
    |
    */
    'supportedLocales' => [
        'en' => ['name' => 'English', 'script' => 'Latn', 'native' => 'English', 'regional' => 'en_GB'],
        'ru' => ['name' => 'Russian', 'script' => 'Cyrl', 'native' => 'Русский', 'regional' => 'ru_RU'],
        'lt' => ['name' => 'Lithuanian', 'script' => 'Latn', 'native' => 'Lietuvių', 'regional' => 'lt_LT'],
    ],

    // LaravelLocalization::setLocale() will use this language if no one is provided.
    // If null, global config('app.locale') is used instead.
    // 'defaultLocale' => 'en',

    // Use the browser Accept-Language header for first-time locale negotiation.
    'useAcceptLanguageHeader' => true,

    // Hide the default locale prefix in URLs to avoid duplicate content.
    'hideDefaultLocaleInURL' => true,

    // Control locale ordering in selectors.
    'localesOrder' => ['en', 'ru', 'lt'],

    // Map custom language segments when needed (empty for now).
    'localesMapping' => [],

    // Locale suffix for LC_TIME and LC_MONETARY.
    'utf8suffix' => env('LARAVELLOCALIZATION_UTF8SUFFIX', '.UTF-8'),

    // URLs which should not be processed by localization redirects.
    'urlsIgnored' => [
        '/api/*',
        '/broadcasting/*',
        '/filament*',
        '/horizon*',
        '/livewire*',
        '/storage/*',
        '/telescope*',
        '/translations*',
        '/up',
        '/.well-known/*',
    ],

    // Skip localization processing for non-idempotent requests.
    'httpMethodsIgnored' => ['POST', 'PUT', 'PATCH', 'DELETE'],
];
