<?php

declare(strict_types=1);

$host = parse_url((string) config('app.url'), PHP_URL_HOST) ?: 'example.com';

return [
    'headers' => [
        'enabled' => env('SECURITY_HEADERS_ENABLED', true),
        'values' => [
            'X-Frame-Options' => 'SAMEORIGIN',
            'Cross-Origin-Opener-Policy' => 'same-origin',
            'Cross-Origin-Resource-Policy' => 'same-origin',
            'X-Permitted-Cross-Domain-Policies' => 'none',
        ],
    ],
    'csp' => [
        'enabled' => env('SECURITY_CSP_ENABLED', true),
        'report_only' => env('SECURITY_CSP_REPORT_ONLY', true),
        'directives' => [
            'default-src' => ["'self'"],
            'script-src' => ["'self'", 'https:'],
            'style-src' => ["'self'", 'https:', "'unsafe-inline'"],
            'img-src' => ["'self'", 'https:', 'data:'],
            'font-src' => ["'self'", 'https:', 'data:'],
            'connect-src' => ["'self'", 'https:'],
            'frame-ancestors' => ["'self'"],
            'form-action' => ["'self'"],
        ],
        'report_uri' => env('SECURITY_CSP_REPORT_URI'),
    ],
    'security_txt' => [
        'enabled' => env('SECURITY_TXT_ENABLED', true),
        'contacts' => array_filter(explode(',', (string) env('SECURITY_TXT_CONTACTS', "mailto:security@{$host}"))),
        'expires' => env('SECURITY_TXT_EXPIRES', \Illuminate\Support\Facades\Date::now()->addYear()->toRfc7231String()),
        'acknowledgments' => env('SECURITY_TXT_ACKNOWLEDGMENTS'),
        'policy' => env('SECURITY_TXT_POLICY'),
        'hiring' => env('SECURITY_TXT_HIRING'),
        'preferred_languages' => env('SECURITY_TXT_LANGUAGES', 'en'),
    ],
];
