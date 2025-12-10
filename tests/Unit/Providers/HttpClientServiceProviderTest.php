<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Http;

it('configures the GitHub HTTP client macro with shared defaults', function (): void {
    $testBrand = 'Test CRM';
    config([
        'laravel-crm.ui.brand_name' => $testBrand,
        'app.url' => 'https://crm.test',
        'http-clients.defaults.retry.times' => 1,
    ]);

    Http::fake([
        'api.github.com/*' => Http::response(['rate' => ['remaining' => 60]], 200),
    ]);

    Http::github()->get('/rate_limit');

    Http::assertSent(fn ($request): bool => $request->url() === 'https://api.github.com/rate_limit'
        && $request->hasHeader('Accept', 'application/vnd.github+json')
        && $request->hasHeader('User-Agent', "{$testBrand} HTTP Client (https://crm.test)"));
});

it('retries GitHub requests on server errors before returning a response', function (): void {
    Http::fake([
        'api.github.com/*' => Http::sequence()
            ->push(['message' => 'temporary'], 500)
            ->push(['message' => 'ok'], 200),
    ]);

    Http::github()->get('/rate_limit');

    Http::assertSentCount(2);
});