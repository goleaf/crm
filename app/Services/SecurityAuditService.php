<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Arr;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

final class SecurityAuditService
{
    /**
     * @return array<int, array<string, mixed>>
     */
    public function checks(?array $composerAudit = null): array
    {
        $hasHeaders = (bool) config('security.headers.enabled', true);
        $csp = config('security.csp', []);
        $securityTxtContacts = array_filter((array) config('security.security_txt.contacts', []));
        $hasLoginLimiter = filled(config('fortify.limiters.login'));
        $hasApiLimiter = filled(config('sanctum.limiters'));

        $composerSummary = $composerAudit['summary'] ?? null;

        return [
            [
                'key' => 'headers',
                'label' => __('app.labels.security_headers'),
                'status' => $hasHeaders ? 'pass' : 'todo',
                'description' => $hasHeaders
                    ? __('app.messages.security_headers_enabled')
                    : __('app.messages.security_headers_disabled'),
            ],
            [
                'key' => 'csp',
                'label' => __('app.labels.csp'),
                'status' => ($csp['enabled'] ?? false) ? (($csp['report_only'] ?? false) ? 'warn' : 'pass') : 'todo',
                'description' => ($csp['enabled'] ?? false)
                    ? (($csp['report_only'] ?? false)
                        ? __('app.messages.csp_report_only')
                        : __('app.messages.csp_enforced'))
                    : __('app.messages.csp_missing'),
            ],
            [
                'key' => 'security_txt',
                'label' => __('app.labels.security_txt'),
                'status' => $securityTxtContacts !== [] ? 'pass' : 'warn',
                'description' => $securityTxtContacts !== []
                    ? __('app.messages.security_txt_present')
                    : __('app.messages.security_txt_missing'),
            ],
            [
                'key' => 'dependency_audit',
                'label' => __('app.labels.dependency_audit'),
                'status' => $this->composerStatus($composerSummary),
                'description' => $composerSummary['description'] ?? __('app.messages.dependency_audit_pending'),
                'meta' => $composerSummary,
            ],
            [
                'key' => 'rate_limiting',
                'label' => __('app.labels.rate_limiting'),
                'status' => $hasLoginLimiter ? 'pass' : 'warn',
                'description' => $hasLoginLimiter
                    ? __('app.messages.rate_limiting_enabled')
                    : __('app.messages.rate_limiting_missing'),
                'meta' => [
                    'api' => $hasApiLimiter,
                    'login_limiter' => config('fortify.limiters.login'),
                    'api_limiter' => config('sanctum.limiters'),
                ],
            ],
            [
                'key' => 'xss',
                'label' => __('app.labels.xss_protection'),
                'status' => 'info',
                'description' => __('app.messages.xss_guidance'),
            ],
            [
                'key' => 'input_validation',
                'label' => __('app.labels.input_validation'),
                'status' => 'info',
                'description' => __('app.messages.validation_guidance'),
            ],
            [
                'key' => 'authorization',
                'label' => __('app.labels.authorization'),
                'status' => 'info',
                'description' => __('app.messages.authorization_guidance'),
            ],
            [
                'key' => 'sri',
                'label' => __('app.labels.sri'),
                'status' => 'info',
                'description' => __('app.messages.sri_guidance'),
            ],
            [
                'key' => 'secrets',
                'label' => __('app.labels.secret_scanning'),
                'status' => 'info',
                'description' => __('app.messages.secrets_guidance'),
            ],
            [
                'key' => 'randomness',
                'label' => __('app.labels.secure_random'),
                'status' => 'info',
                'description' => __('app.messages.randomness_guidance'),
            ],
        ];
    }

    /**
     * @return array{summary: array<string, mixed>}
     */
    public function runComposerAudit(): array
    {
        $process = new Process(['composer', 'audit', '--locked', '--format=json'], base_path(), null, null, 120);
        $process->run();

        if (! $process->isSuccessful()) {
            throw new ProcessFailedException($process);
        }

        $payload = json_decode($process->getOutput(), true);

        $advisories = $payload['advisories'] ?? [];
        $issueCount = collect($advisories)->flatten(1)->count();

        $packages = collect($advisories)
            ->map(fn (array $packageAdvisories, string $package): array => [
                'package' => $package,
                'count' => count($packageAdvisories),
                'links' => Arr::pluck($packageAdvisories, 'link'),
            ])
            ->values()
            ->all();

        return [
            'summary' => [
                'issues' => $issueCount,
                'description' => $issueCount === 0
                    ? __('app.messages.dependency_audit_clean')
                    : __('app.messages.dependency_audit_found', ['count' => $issueCount]),
                'packages' => $packages,
            ],
        ];
    }

    private function composerStatus(?array $summary): string
    {
        if ($summary === null) {
            return 'todo';
        }

        if (($summary['issues'] ?? 0) > 0) {
            return 'warn';
        }

        return 'pass';
    }
}
