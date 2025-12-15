<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\IpUtils;
use Symfony\Component\HttpFoundation\Response;

final class EnforceIpLists
{
    public function handle(Request $request, Closure $next): Response
    {
        $ip = (string) $request->ip();

        if ($ip === '' || $ip === '0') {
            return $next($request);
        }

        $denyList = $this->normalizedRanges('laravel-crm.security.ip_denylist');
        $allowList = $this->normalizedRanges('laravel-crm.security.ip_whitelist');

        if ($denyList !== [] && $this->matches($ip, $denyList)) {
            Log::channel('auth')->warning('Request blocked by IP denylist', [
                'ip' => $ip,
                'path' => $request->path(),
            ]);

            abort(403);
        }

        if ($allowList !== [] && ! $this->matches($ip, $allowList)) {
            Log::channel('auth')->warning('Request blocked (IP not in allowlist)', [
                'ip' => $ip,
                'path' => $request->path(),
            ]);

            abort(403);
        }

        return $next($request);
    }

    /**
     * @return array<int, string>
     */
    private function normalizedRanges(string $key): array
    {
        $raw = config($key, []);

        return collect(is_array($raw) ? $raw : [$raw])
            ->map(fn (mixed $value): string => trim((string) $value))
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    /**
     * @param  array<int, string>  $ranges
     */
    private function matches(string $ip, array $ranges): bool
    {
        try {
            return IpUtils::checkIp($ip, $ranges);
        } catch (\Throwable $e) {
            Log::channel('system')->warning('Invalid IP range configuration', [
                'ip' => $ip,
                'ranges' => $ranges,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }
}

