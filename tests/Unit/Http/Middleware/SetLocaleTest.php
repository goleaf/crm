<?php

declare(strict_types=1);

use App\Http\Middleware\SetLocale;
use Devrabiul\LaravelGeoGenius\LaravelGeoGenius;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Session;
use Symfony\Component\HttpFoundation\Response;

afterEach(function (): void {
    Mockery::close();
});

beforeEach(function (): void {
    config()->set('app.available_locales', ['en', 'ru', 'lt']);
    config()->set('app.locale', 'en');
    config()->set('app.timezone', 'UTC');
    config()->set('laravel-geo-genius.translate.auto_translate', true);

    Session::flush();
});

it('uses the session locale when available', function (): void {
    $language = new class
    {
        public function detect(): ?string
        {
            return null;
        }

        public function getUserLanguage(): string
        {
            return 'lt';
        }
    };

    $timezone = new class
    {
        public function getUserTimezone(): string
        {
            return 'Europe/Vilnius';
        }
    };

    $geo = Mockery::mock(LaravelGeoGenius::class);
    $geo->shouldReceive('language')->andReturn($language);
    $geo->shouldReceive('timezone')->andReturn($timezone);

    Session::put('locale', 'ru');

    $middleware = new SetLocale($geo);
    $request = Request::create('/', 'GET');
    $middleware->handle($request, static fn (): Response => new Response);

    expect(App::getLocale())->toBe('ru')
        ->and(Session::get('timezone'))->toBe('Europe/Vilnius')
        ->and(config('app.timezone'))->toBe('Europe/Vilnius');
});

it('falls back to geo detection when preferred language is unavailable', function (): void {
    config()->set('app.available_locales', ['en', 'lt']);

    $language = new class
    {
        public function detect(): string
        {
            return 'lt';
        }

        public function getUserLanguage(): string
        {
            return 'lt';
        }
    };

    $timezone = new class
    {
        public function getUserTimezone(): string
        {
            return 'Europe/Vilnius';
        }
    };

    $geo = Mockery::mock(LaravelGeoGenius::class);
    $geo->shouldReceive('language')->andReturn($language);
    $geo->shouldReceive('timezone')->andReturn($timezone);

    $middleware = new SetLocale($geo);
    $request = Request::create('/', 'GET', server: ['HTTP_ACCEPT_LANGUAGE' => 'es-MX,es;q=0.8']);
    $middleware->handle($request, static fn (): Response => new Response);

    expect(App::getLocale())->toBe('lt')
        ->and(Session::get('locale'))->toBe('lt');
});