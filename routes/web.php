<?php

declare(strict_types=1);

use App\Http\Controllers\Auth\CallbackController;
use App\Http\Controllers\Auth\RedirectController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\NotePrintController;
use App\Http\Controllers\PrivacyPolicyController;
use App\Http\Controllers\SecurityTxtController;
use App\Http\Controllers\TermsOfServiceController;
use App\Http\Controllers\WebManifestController;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Session\Middleware\AuthenticateSession;
use Illuminate\Support\Facades\Route;
use Laravel\Jetstream\Http\Controllers\TeamInvitationController;
use Mcamara\LaravelLocalization\Facades\LaravelLocalization;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

Route::middleware('guest')->group(function (): void {
    Route::get('/auth/redirect/{provider}', RedirectController::class)
        ->name('auth.socialite.redirect')
        ->middleware('throttle:10,1');

    Route::get('/auth/callback/{provider}', CallbackController::class)
        ->name('auth.socialite.callback')
        ->middleware('throttle:10,1');
});

/*
|--------------------------------------------------------------------------
| Developer Login Routes (Local/Testing Only)
|--------------------------------------------------------------------------
|
| These routes provide password-less authentication for development and
| testing environments. They are conditionally registered and protected
| by environment checks in both the route registration and controllers.
|
| Routes:
| - GET /dev-login       - URL-based login (DeveloperLoginController)
| - GET /dev-login-form  - Form-based login (Filament DeveloperLogin page)
|
| @see App\Http\Controllers\Auth\DeveloperLoginController
| @see App\Filament\Pages\Auth\DeveloperLogin
| @see docs/auth/developer-login.md
|
*/
if (app()->environment(['local', 'testing']) && (bool) env('DEV_LOGIN_ENABLED', false)) {
    Route::get('/dev-login', \App\Http\Controllers\Auth\DeveloperLoginController::class)
        ->name('dev.login')
        ->middleware(['signed', 'throttle:10,1']);
    Route::get('/dev-login-form', \App\Filament\Pages\Auth\DeveloperLogin::class)
        ->name('dev.login.form')
        ->middleware('throttle:10,1');
}

Route::get('/.well-known/security.txt', SecurityTxtController::class)->name('security.txt');
Route::get('/site.webmanifest', WebManifestController::class)->name('manifest');

Route::group([
    'prefix' => LaravelLocalization::setLocale(),
    'middleware' => ['localeSessionRedirect', 'localizationRedirect', 'localeViewPath'],
], function (): void {
    Route::middleware('guest')->group(function (): void {
        Route::get('/login', fn () => redirect()->away(url()->getAppUrl('login')))->name('login');
        Route::get('/register', fn () => redirect()->away(url()->getAppUrl('register')))->name('register');
        Route::get('/forgot-password', fn () => redirect()->away(url()->getAppUrl('forgot-password')))->name('password.request');
    });

    Route::get('/', HomeController::class)->name('home');

    Route::get(LaravelLocalization::transRoute('routes.terms'), TermsOfServiceController::class)->name('terms.show');
    Route::get(LaravelLocalization::transRoute('routes.privacy'), PrivacyPolicyController::class)->name('policy.show');

    Route::redirect(LaravelLocalization::transRoute('routes.dashboard'), url()->getAppUrl())->name('dashboard');

    Route::middleware(['auth', 'verified'])->group(function (): void {
        Route::get('/notes/{note}/print', NotePrintController::class)->name('notes.print');
        Route::view('/purchase-orders', 'purchase-orders.index')->name('purchase-orders.index');
        Route::get('/calendar', [\App\Http\Controllers\CalendarController::class, 'index'])->name('calendar');
        Route::post('/calendar', [\App\Http\Controllers\CalendarController::class, 'store'])->name('calendar.store');
        Route::get('/calendar/export/ical', [\App\Http\Controllers\CalendarController::class, 'exportIcal'])->name('calendar.export.ical');

        // Impersonate routes
        Route::impersonate();
    });

    Route::get('/discord', fn () => redirect()->away(config('services.discord.invite_url')))->name('discord');
});

Route::get('/email/verify/{id}/{hash}', function (EmailVerificationRequest $request): \Illuminate\Routing\Redirector|\Illuminate\Http\RedirectResponse {
    $request->fulfill();

    return to_route('dashboard');
})->middleware(['auth', 'signed'])->name('verification.verify');

Route::get('/team-invitations/{invitation}', [TeamInvitationController::class, 'accept'])
    ->middleware(['signed', 'verified', 'auth', AuthenticateSession::class])
    ->name('team-invitations.accept');
