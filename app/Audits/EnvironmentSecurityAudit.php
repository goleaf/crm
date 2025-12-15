<?php

declare(strict_types=1);

namespace App\Audits;

use Dgtlss\Warden\Contracts\CustomAudit;

final class EnvironmentSecurityAudit implements CustomAudit
{
    private array $findings = [];

    public function audit(): bool
    {
        $this->findings = [];

        // Check for debug mode in production
        if (app()->environment('production') && config('app.debug')) {
            $this->findings[] = [
                'package' => 'environment',
                'title' => 'APP_DEBUG enabled in production',
                'severity' => 'critical',
                'description' => 'APP_DEBUG is set to true in production environment. Set APP_DEBUG=false in .env file to prevent sensitive data exposure.',
            ];
        }

        // Check for default app key
        if (str_starts_with((string) config('app.key'), 'base64:') && strlen(base64_decode(substr((string) config('app.key'), 7))) < 32) {
            $this->findings[] = [
                'package' => 'environment',
                'title' => 'Weak APP_KEY',
                'severity' => 'critical',
                'description' => 'Weak or default APP_KEY detected. Run: php artisan key:generate to secure your application/sessions.',
            ];
        }

        // Check for HTTPS in production
        if (app()->environment('production') && ! str_starts_with((string) config('app.url'), 'https://')) {
            $this->findings[] = [
                'package' => 'environment',
                'title' => 'HTTPS not enforced',
                'severity' => 'high',
                'description' => 'APP_URL does not use https:// protocol in production. Update APP_URL to use https://.',
            ];
        }

        // Check for session security
        if (app()->environment('production') && ! config('session.secure')) {
            $this->findings[] = [
                'package' => 'session',
                'title' => 'Insecure session cookies',
                'severity' => 'high',
                'description' => 'Session cookies are not marked as secure. Set SESSION_SECURE_COOKIE=true in .env to prevent session hijacking.',
            ];
        }

        // Check for sensitive data in logs
        if (config('app.debug') && in_array(config('logging.default'), ['single', 'daily', 'stack'])) {
            $this->findings[] = [
                'package' => 'logging',
                'title' => 'Debug logging enabled',
                'severity' => 'medium',
                'description' => 'Debug mode is enabled, which may write sensitive data to log files. Review log configuration and retention policies.',
            ];
        }

        // Check for database credentials
        if (config('database.default') === 'mysql' && config('database.connections.mysql.password') === '') {
            $this->findings[] = [
                'package' => 'database',
                'title' => 'Empty database password',
                'severity' => 'critical',
                'description' => 'Database password is empty. Set a strong, unique DB_PASSWORD in .env.',
            ];
        }

        // Check for mail configuration in production
        if (app()->environment('production') && config('mail.default') === 'log') {
            $this->findings[] = [
                'package' => 'mail',
                'title' => 'Mail driver set to log',
                'severity' => 'medium',
                'description' => 'Mail driver is set to "log" in production. Emails will be written to disk instead of sent. Configure a proper mail driver.',
            ];
        }

        return $this->findings === [];
    }

    public function getFindings(): array
    {
        return $this->findings;
    }

    public function shouldRun(): bool
    {
        return true;
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
