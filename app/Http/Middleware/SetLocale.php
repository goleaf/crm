<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Session;
use Symfony\Component\HttpFoundation\Response;

final class SetLocale
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $availableLocales = ['en', 'ru', 'lt'];
        $locale = Session::get('locale', config('app.locale', 'en'));

        if (in_array($locale, $availableLocales)) {
            App::setLocale($locale);
        }

        return $next($request);
    }
}
