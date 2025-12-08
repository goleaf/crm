<?php

declare(strict_types=1);

namespace App\Audits;

use Dgtlss\Warden\Contracts\CustomAudit;
use Dgtlss\Warden\ValueObjects\AuditResult;

final class EnvironmentSecurityAudit implements CustomAudit
{
    public function run(): AuditResult
    {
        $issues = [];

        // Check for debug mode in production
        if (app()->environment('production') && config('app.debug')) {
            $issues[] = [
                'severity' => 'critical',
                'package' => 'environment',
                'message' => 'APP_DEBUG is enabled in production environment',
                'recommendation' => 'Set APP_DEBUG=false in production .env file',
                'cve' => null,
            ];
        }

        // Check for default app key
        if (str_starts_with((string) config('app.key'), 'base64:') && strlen(base64_decode(substr((string) config('app.key'), 7))) < 32) {
            $issues[] = [
                'severity' => 'critical',
                'package' => 'environment',
                'message' => 'Weak or default APP_KEY detected',
                'recommendation' => 'Run: php artisan key:generate',
                'cve' => null,
            ];
        }

        // Check for HTTPS in production
        if (app()->environment('production') && ! str_starts_with((string) config('app.url'), 'https://')) {
            $issues[] = [
                'severity' => 'high',
                'package' => 'environment',
                'message' => 'HTTPS not enforced in production',
                'recommendation' => 'Update APP_URL to use https:// protocol',
                'cve' => null,
            ];
        }

        // Check for session security
        if (app()->environment('production') && ! config('session.secure')) {
            $issues[] = [
                'severity' => 'high',
                'package' => 'session',
                'message' => 'Session cookies not marked as secure',
                'recommendation' => 'Set SESSION_SECURE_COOKIE=true in .env',
                'cve' => null,
            ];
        }

        // Check for sensitive data in logs
        if (config('app.debug') && in_array(config('logging.default'), ['single', 'daily', 'stack'])) {
            $issues[] = [
                'severity' => 'medium',
                'package' => 'logging',
                'message' => 'Debug mode may expose sensitive data in logs',
                'recommendation' => 'Review log files for sensitive information',
                'cve' => null,
            ];
        }

        // Check for database credentials
        if (config('database.default') === 'mysql' && config('database.connections.mysql.password') === '') {
            $issues[] = [
                'severity' => 'critical',
                'package' => 'database',
                'message' => 'Database password is empty',
                'recommendation' => 'Set a strong DB_PASSWORD in .env',
                'cve' => null,
            ];
        }

        // Check for mail configuration in production
        if (app()->environment('production') && config('mail.default') === 'log') {
            $issues[] = [
                'severity' => 'medium',
                'package' => 'mail',
                'message' => 'Mail driver set to "log" in production',
                'recommendation' => 'Configure proper mail driver (smtp, mailgun, etc.)',
                'cve' => null,
            ];
        }

        return new AuditResult(
            passed: $issues === [],
            issues: $issues,
            metadata: [
                'audit_type' => 'environment_security',
                'environment' => app()->environment(),
                'checked_at' => now()->toIso8601String(),
                'php_version' => PHP_VERSION,
                'laravel_version' => app()->version(),
            ]
        );
    }

    public function getName(): string
    {
        return 'Environment Security Audit';
    }

    public function getDescription(): string
    {
        return 'Checks for common environment configuration security issues including debug mode, HTTPS enforcement, session security, and credential management';
    }
}
