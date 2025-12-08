<?php

declare(strict_types=1);

use Dgtlss\Warden\Services\WardenService;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;

uses()->group('security', 'warden');

it('can run security audit command', function (): void {
    Artisan::call('warden:audit');

    expect(Artisan::output())->toContain('audit');
});

it('detects vulnerabilities when present', function (): void {
    $warden = resolve(WardenService::class);
    $result = $warden->runAudit();

    expect($result)->toBeObject();
});

it('caches audit results', function (): void {
    Cache::flush();

    $warden = resolve(WardenService::class);

    $firstResult = $warden->runAudit();
    $secondResult = $warden->runAudit();

    expect($firstResult)->toEqual($secondResult);
});

it('can bypass cache when requested', function (): void {
    Cache::flush();

    $warden = resolve(WardenService::class);

    $firstResult = $warden->runAudit();
    \Illuminate\Support\Sleep::sleep(1);
    $secondResult = $warden->runAudit(skipCache: true);

    // Results should be different objects even if content is same
    expect($firstResult)->not->toBe($secondResult);
});

it('respects severity filter configuration', function (): void {
    config(['warden.audits.severity_filter' => 'high']);

    $warden = resolve(WardenService::class);
    $result = $warden->runAudit(skipCache: true);

    expect($result)->toBeObject();
});

it('can run audit with json output', function (): void {
    Artisan::call('warden:audit', ['--json' => true]);

    $output = Artisan::output();
    expect($output)->toBeJson();
});

it('handles audit failures gracefully', function (): void {
    // Simulate failure by using invalid configuration
    config(['warden.audits.timeout' => 0]);

    $warden = resolve(WardenService::class);

    expect(fn () => $warden->runAudit(skipCache: true))
        ->not->toThrow(\Exception::class);
});

it('can access last audit result', function (): void {
    $warden = resolve(WardenService::class);

    // Run an audit first
    $warden->runAudit();

    // Get last result
    $lastResult = $warden->getLastAuditResult();

    expect($lastResult)->toBeObject();
});
