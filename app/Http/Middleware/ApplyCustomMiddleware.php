<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Pipeline\Pipeline;
use Symfony\Component\HttpFoundation\Response;

final class ApplyCustomMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $custom = config('crm.custom_middleware', []);

        if ($custom === [] || $custom === null) {
            return $next($request);
        }

        return resolve(Pipeline::class)
            ->send($request)
            ->through($custom)
            ->then(static fn (Request $request): Response => $next($request));
    }
}
