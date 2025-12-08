<?php

declare(strict_types=1);

use App\Audits\EnvironmentSecurityAudit;

uses()->group('security', 'audits');

it('detects debug mode in production', function (): void {
    config(['app.env' => 'production', 'app.debug' => true]);

    $audit = new EnvironmentSecurityAudit;
    $result = $audit->run();

    expect($result->passed)->toBeFalse()
        ->and($result->issues)->toBeArray()
        ->and($result->issues)->not->toBeEmpty();

    $debugIssue = collect($result->issues)->firstWhere('message', 'APP_DEBUG is enabled in production environment');
    expect($debugIssue)->not->toBeNull()
        ->and($debugIssue['severity'])->toBe('critical');
});

it('detects weak app key', function (): void {
    config(['app.key' => 'base64:'.base64_encode('short')]);

    $audit = new EnvironmentSecurityAudit;
    $result = $audit->run();

    expect($result->passed)->toBeFalse();

    $keyIssue = collect($result->issues)->firstWhere('message', 'Weak or default APP_KEY detected');
    expect($keyIssue)->not->toBeNull()
        ->and($keyIssue['severity'])->toBe('critical');
});

it('detects missing https in production', function (): void {
    config([
        'app.env' => 'production',
        'app.url' => 'http://example.com',
    ]);

    $audit = new EnvironmentSecurityAudit;
    $result = $audit->run();

    expect($result->passed)->toBeFalse();

    $httpsIssue = collect($result->issues)->firstWhere('message', 'HTTPS not enforced in production');
    expect($httpsIssue)->not->toBeNull()
        ->and($httpsIssue['severity'])->toBe('high');
});

it('detects insecure session cookies in production', function (): void {
    config([
        'app.env' => 'production',
        'session.secure' => false,
    ]);

    $audit = new EnvironmentSecurityAudit;
    $result = $audit->run();

    expect($result->passed)->toBeFalse();

    $sessionIssue = collect($result->issues)->firstWhere('message', 'Session cookies not marked as secure');
    expect($sessionIssue)->not->toBeNull()
        ->and($sessionIssue['severity'])->toBe('high');
});

it('detects empty database password', function (): void {
    config([
        'database.default' => 'mysql',
        'database.connections.mysql.password' => '',
    ]);

    $audit = new EnvironmentSecurityAudit;
    $result = $audit->run();

    expect($result->passed)->toBeFalse();

    $dbIssue = collect($result->issues)->firstWhere('message', 'Database password is empty');
    expect($dbIssue)->not->toBeNull()
        ->and($dbIssue['severity'])->toBe('critical');
});

it('detects log mail driver in production', function (): void {
    config([
        'app.env' => 'production',
        'mail.default' => 'log',
    ]);

    $audit = new EnvironmentSecurityAudit;
    $result = $audit->run();

    expect($result->passed)->toBeFalse();

    $mailIssue = collect($result->issues)->firstWhere('message', 'Mail driver set to "log" in production');
    expect($mailIssue)->not->toBeNull()
        ->and($mailIssue['severity'])->toBe('medium');
});

it('passes when configuration is secure', function (): void {
    config([
        'app.env' => 'production',
        'app.debug' => false,
        'app.key' => 'base64:'.base64_encode(random_bytes(32)),
        'app.url' => 'https://example.com',
        'session.secure' => true,
        'database.default' => 'mysql',
        'database.connections.mysql.password' => 'secure_password',
        'mail.default' => 'smtp',
    ]);

    $audit = new EnvironmentSecurityAudit;
    $result = $audit->run();

    expect($result->passed)->toBeTrue()
        ->and($result->issues)->toBeEmpty();
});

it('includes metadata in audit result', function (): void {
    $audit = new EnvironmentSecurityAudit;
    $result = $audit->run();

    expect($result->metadata)->toBeArray()
        ->and($result->metadata)->toHaveKeys(['audit_type', 'environment', 'checked_at', 'php_version', 'laravel_version'])
        ->and($result->metadata['audit_type'])->toBe('environment_security');
});

it('has correct name and description', function (): void {
    $audit = new EnvironmentSecurityAudit;

    expect($audit->getName())->toBe('Environment Security Audit')
        ->and($audit->getDescription())->toBeString()
        ->and($audit->getDescription())->toContain('security');
});

it('allows debug mode in non-production environments', function (): void {
    config([
        'app.env' => 'local',
        'app.debug' => true,
    ]);

    $audit = new EnvironmentSecurityAudit;
    $result = $audit->run();

    $debugIssue = collect($result->issues)->firstWhere('message', 'APP_DEBUG is enabled in production environment');
    expect($debugIssue)->toBeNull();
});
