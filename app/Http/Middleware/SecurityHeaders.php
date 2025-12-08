<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class SecurityHeaders
{
    /**
     * Add common security headers and optional Content Security Policy.
     */
    public function handle(Request $request, Closure $next): Response
    {
        /** @var Response $response */
        $response = $next($request);

        if (! config('security.headers.enabled', true)) {
            return $response;
        }

        $headers = config('security.headers.values', []);

        foreach ($headers as $name => $value) {
            if (! empty($value)) {
                $response->headers->set($name, $value);
            }
        }

        $this->applyCsp($response);

        return $response;
    }

    private function applyCsp(Response $response): void
    {
        $config = config('security.csp', []);

        if (! ($config['enabled'] ?? false)) {
            return;
        }

        $directives = $config['directives'] ?? [];

        if (empty($directives)) {
            return;
        }

        $policy = collect($directives)
            ->map(function (array|string $value, string $directive): string {
                $values = is_array($value) ? $value : [$value];

                return trim($directive.' '.implode(' ', array_filter($values)));
            })
            ->filter()
            ->implode('; ');

        if (filled($config['report_uri'] ?? null)) {
            $policy .= '; report-uri '.$config['report_uri'];
        }

        $header = ($config['report_only'] ?? false)
            ? 'Content-Security-Policy-Report-Only'
            : 'Content-Security-Policy';

        $response->headers->set($header, $policy);
    }
}
