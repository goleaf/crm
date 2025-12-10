<?php

declare(strict_types=1);

use App\Http\Middleware\ApplySecurityHeaders;
use App\Http\Middleware\SecurityHeaders;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Sentry\Laravel\Integration;
use Vectorial1024\LaravelCacheEvict\CacheEvictCommand;

// Compat: ensure legacy Filament\Forms\Form type exists for packages not updated to Schemas.
if (! class_exists(\Filament\Forms\Form::class) && class_exists(\Filament\Schemas\Schema::class)) {
    class_alias(\Filament\Schemas\Schema::class, \Filament\Forms\Form::class);
}

$app = Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        api: __DIR__ . '/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'crm.auth' => \App\Http\Middleware\EnsureCrmAuthenticated::class,
            'crm.permission' => \App\Http\Middleware\EnsurePermission::class,
            'crm.team' => \App\Http\Middleware\EnsureTeamContext::class,
            'crm.custom' => \App\Http\Middleware\ApplyCustomMiddleware::class,
            'localizationRedirect' => \Mcamara\LaravelLocalization\Middleware\LaravelLocalizationRedirectFilter::class,
            'localeSessionRedirect' => \Mcamara\LaravelLocalization\Middleware\LocaleSessionRedirect::class,
            'localeViewPath' => \Mcamara\LaravelLocalization\Middleware\LaravelLocalizationViewPath::class,
        ]);

        $middleware->append([
            ApplySecurityHeaders::class,
            SecurityHeaders::class,
        ]);

        // Exclude login link route from CSRF verification
        $middleware->validateCsrfTokens(except: [
            'laravel-login-link-login',
        ]);

        // Enable Laravel Precognition for API routes
        $middleware->api(prepend: [
            \Illuminate\Foundation\Http\Middleware\HandlePrecognitiveRequests::class,
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

        $supportedDrivers = ['database', 'file'];

        $evictableStores = collect([config('cache.default')])
            ->merge(array_keys(config('cache.stores', [])))
            ->filter(fn (?string $store): bool => $store !== null
                && in_array(config("cache.stores.{$store}.driver"), $supportedDrivers, true))
            ->unique()
            ->values();

        $evictableStores->each(function (string $store) use ($schedule): void {
            $schedule->command(CacheEvictCommand::class, [$store])
                ->name("cache:evict-{$store}")
                ->hourly()
                ->runInBackground()
                ->withoutOverlapping();
        });
    })
    ->booting(function (): void {
        //        Model::automaticallyEagerLoadRelationships(); TODO: Before enabling this, check the test suite for any issues with eager loading.
    })
    ->create();

$app->useLangPath(__DIR__ . '/../lang');

return $app;
