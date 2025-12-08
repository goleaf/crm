<?php

declare(strict_types=1);

return [
    'enabled' => env('SECURITY_HEADERS_ENABLED', true),

    'only_secure_requests' => env('SECURITY_HEADERS_ONLY_HTTPS', true),

    'except' => [
        // 'up',
    ],

    'remove' => [
        'X-Powered-By',
        'x-powered-by',
        'Server',
        'server',
    ],

    'referrer-policy' => env('SECURITY_HEADERS_REFERRER_POLICY', 'no-referrer-when-downgrade'),

    'strict-transport-security' => env('SECURITY_HEADERS_HSTS', 'max-age=31536000; includeSubDomains'),

    'certificate-transparency' => env('SECURITY_HEADERS_EXPECT_CT', 'enforce, max-age=30'),

    'permissions-policy' => env(
        'SECURITY_HEADERS_PERMISSIONS_POLICY',
        'autoplay=(self), camera=(), encrypted-media=(self), fullscreen=(), geolocation=(self), gyroscope=(), magnetometer=(), microphone=(), midi=(), payment=(), sync-xhr=(self), usb=()',
    ),

    'content-type-options' => env('SECURITY_HEADERS_CONTENT_TYPE_OPTIONS', 'nosniff'),
];
