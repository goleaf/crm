<?php

declare(strict_types=1);

use App\Http\Controllers\Auth\CallbackController;
use App\Http\Controllers\Auth\RedirectController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\NotePrintController;
use App\Http\Controllers\PrivacyPolicyController;
use App\Http\Controllers\SecurityTxtController;
use App\Http\Controllers\TermsOfServiceController;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Session\Middleware\AuthenticateSession;
use Illuminate\Support\Facades\Route;
use Laravel\Jetstream\Http\Controllers\TeamInvitationController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::middleware('guest')->group(function (): void {
    Route::get('/auth/redirect/{provider}', RedirectController::class)
        ->name('auth.socialite.redirect')
        ->middleware('throttle:10,1');
    Route::get('/auth/callback/{provider}', CallbackController::class)
        ->name('auth.socialite.callback')
        ->middleware('throttle:10,1');

    Route::get('/login', fn () => redirect()->away(url()->getAppUrl('login')))->name('login');

    Route::get('/register', fn () => redirect()->away(url()->getAppUrl('register')))->name('register');

    Route::get('/forgot-password', fn () => redirect()->away(url()->getAppUrl('forgot-password')))->name('password.request');
});

Route::get('/.well-known/security.txt', SecurityTxtController::class)->name('security.txt');

Route::get('/site.webmanifest', \App\Http\Controllers\WebManifestController::class)->name('manifest');

Route::get('/', HomeController::class);

Route::get('/terms-of-service', TermsOfServiceController::class)->name('terms.show');
Route::get('/privacy-policy', PrivacyPolicyController::class)->name('policy.show');

Route::redirect('/dashboard', url()->getAppUrl())->name('dashboard');

Route::get('/email/verify/{id}/{hash}', function (EmailVerificationRequest $request): \Illuminate\Routing\Redirector|\Illuminate\Http\RedirectResponse {
    $request->fulfill();

    return to_route('dashboard');
})->middleware(['auth', 'signed'])->name('verification.verify');

Route::get('/team-invitations/{invitation}', [TeamInvitationController::class, 'accept'])
    ->middleware(['signed', 'verified', 'auth', AuthenticateSession::class])
    ->name('team-invitations.accept');

Route::middleware(['auth', 'verified'])->group(function (): void {
    Route::get('/notes/{note}/print', NotePrintController::class)->name('notes.print');
    Route::view('/purchase-orders', 'purchase-orders.index')->name('purchase-orders.index');
    Route::get('/calendar', [\App\Http\Controllers\CalendarController::class, 'index'])->name('calendar');
    Route::post('/calendar', [\App\Http\Controllers\CalendarController::class, 'store'])->name('calendar.store');
    Route::get('/calendar/export/ical', [\App\Http\Controllers\CalendarController::class, 'exportIcal'])->name('calendar.export.ical');
});

// Community redirects
Route::get('/discord', fn () => redirect()->away(config('services.discord.invite_url')))->name('discord');
