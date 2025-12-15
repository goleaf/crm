<?php

declare(strict_types=1);

$proxies = env('TRUSTED_PROXIES');

if (is_string($proxies)) {
    $proxies = trim($proxies);
}

return [
    /*
    |--------------------------------------------------------------------------
    | Trusted Proxies
    |--------------------------------------------------------------------------
    |
    | Configure trusted proxies / load balancers so Laravel can honor forwarded
    | headers (e.g. X-Forwarded-Proto) and correctly detect HTTPS.
    |
    | Use "*" to trust the immediate proxy (REMOTE_ADDR), or provide a
    | comma-separated list of proxy IPs/CIDRs.
    |
    */
    'proxies' => $proxies !== '' ? $proxies : null,
];
