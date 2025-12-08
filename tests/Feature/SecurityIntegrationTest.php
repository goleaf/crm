<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Config;

it('serves security.txt when configured', function (): void {
    Config::set('security.security_txt.enabled', true);
    Config::set('security.security_txt.contacts', ['mailto:security@example.test']);
    Config::set('security.security_txt.expires', 'Wed, 01 Jan 2026 00:00:00 GMT');

    $response = $this->get('/.well-known/security.txt');

    $response->assertOk();
    expect($response->headers->get('Content-Type'))->toContain('text/plain');
    expect($response->getContent())
        ->toContain('Contact: mailto:security@example.test')
        ->toContain('Expires: Wed, 01 Jan 2026 00:00:00 GMT');
});

it('returns 404 for security.txt when disabled', function (): void {
    Config::set('security.security_txt.enabled', false);

    $this->get('/.well-known/security.txt')->assertNotFound();
});

it('adds a CSP header when enabled', function (): void {
    Config::set('security.csp.enabled', true);
    Config::set('security.csp.report_only', true);

    $response = $this->get('/');

    expect($response->headers->has('Content-Security-Policy-Report-Only'))->toBeTrue();
    expect($response->headers->get('Content-Security-Policy-Report-Only'))
        ->toContain("default-src 'self'");
});
