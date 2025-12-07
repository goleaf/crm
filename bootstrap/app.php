<?php

declare(strict_types=1);

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Sentry\Laravel\Integration;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'crm.auth' => \App\Http\Middleware\EnsureCrmAuthenticated::class,
            'crm.permission' => \App\Http\Middleware\EnsurePermission::class,
            'crm.team' => \App\Http\Middleware\EnsureTeamContext::class,
            'crm.custom' => \App\Http\Middleware\ApplyCustomMiddleware::class,
        ]);

        $middleware->group('crm', [
            \Illuminate\Cookie\Middleware\EncryptCookies::class,
            \Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class,
            \Illuminate\Session\Middleware\StartSession::class,
            \Illuminate\View\Middleware\ShareErrorsFromSession::class,
            \Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class,
            \Illuminate\Routing\Middleware\SubstituteBindings::class,
            \App\Http\Middleware\SetLocale::class,
            \App\Http\Middleware\EnsureCrmAuthenticated::class,
            \App\Http\Middleware\EnsureTeamContext::class,
            \App\Http\Middleware\ApplyCustomMiddleware::class,
        ]);

        $middleware->priority([
            \App\Http\Middleware\EnsureCrmAuthenticated::class,
            \Illuminate\Session\Middleware\StartSession::class,
            \App\Http\Middleware\EnsureTeamContext::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        Integration::handles($exceptions);
    })
    ->withSchedule(function (Schedule $schedule): void {
        $schedule->command('app:generate-sitemap')->daily();
    })
    ->booting(function (): void {
        //        Model::automaticallyEagerLoadRelationships(); TODO: Before enabling this, check the test suite for any issues with eager loading.
    })
    ->create();
