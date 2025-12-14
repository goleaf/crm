<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class EnforcePaginationLimits
{
    public function handle(Request $request, Closure $next): Response
    {
        $parameter = config('performance.pagination.parameter', 'per_page');
        $default = (int) config('performance.pagination.default_per_page', 25);
        $max = (int) config('performance.pagination.max_per_page', 100);

        if ($max < 1) {
            return $next($request);
        }

        $perPage = $request->has($parameter)
            ? (int) $request->input($parameter)
            : $default;

        $clamped = $this->clampPerPage($perPage, $default, $max);

        if ($clamped !== $perPage) {
            $request->merge([$parameter => $clamped]);
        }

        return $next($request);
    }

    private function clampPerPage(int $perPage, int $default, int $max): int
    {
        if ($perPage < 1) {
            return $default;
        }

        if ($perPage > $max) {
            return $max;
        }

        return $perPage;
    }
}
