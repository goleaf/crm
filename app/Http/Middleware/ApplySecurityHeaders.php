<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Pipeline\Pipeline;
use Symfony\Component\HttpFoundation\Response;
use Treblle\SecurityHeaders\Http\Middleware\CertificateTransparencyPolicy;
use Treblle\SecurityHeaders\Http\Middleware\ContentTypeOptions;
use Treblle\SecurityHeaders\Http\Middleware\PermissionsPolicy;
use Treblle\SecurityHeaders\Http\Middleware\RemoveHeaders;
use Treblle\SecurityHeaders\Http\Middleware\SetReferrerPolicy;
use Treblle\SecurityHeaders\Http\Middleware\StrictTransportSecurity;

final readonly class ApplySecurityHeaders
{
    public function __construct(private Pipeline $pipeline) {}

    public function handle(Request $request, Closure $next): Response
    {
        if (! $this->asBool(config('headers.enabled'), true)) {
            return $next($request);
        }

        if ($this->isExcluded($request)) {
            return $next($request);
        }

        return $this->pipeline
            ->send($request)
            ->through($this->middlewareStack($request))
            ->then($next);
    }

    private function middlewareStack(Request $request): array
    {
        $middlewares = [
            RemoveHeaders::class,
            SetReferrerPolicy::class,
            CertificateTransparencyPolicy::class,
            PermissionsPolicy::class,
            ContentTypeOptions::class,
        ];

        if ($this->shouldAddStrictTransportSecurity($request)) {
            array_splice($middlewares, 2, 0, [StrictTransportSecurity::class]);
        }

        return $middlewares;
    }

    private function shouldAddStrictTransportSecurity(Request $request): bool
    {
        $policy = config('headers.strict-transport-security');

        if ($policy === null || $policy === '') {
            return false;
        }

        if (! $this->asBool(config('headers.only_secure_requests'), true)) {
            return true;
        }

        return $request->isSecure();
    }

    private function isExcluded(Request $request): bool
    {
        $except = array_filter((array) config('headers.except', []));

        return $except !== [] && $request->is($except);
    }

    private function asBool(mixed $value, bool $default = false): bool
    {
        $normalized = filter_var($value, FILTER_VALIDATE_BOOL, FILTER_NULL_ON_FAILURE);

        return $normalized ?? $default;
    }
}
