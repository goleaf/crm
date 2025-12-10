<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

beforeEach(function (): void {
    // Ensure we're in local environment for these tests
    config(['app.env' => 'local']);
});

it('allows developer login with valid email in local environment', function (): void {
    $user = User::factory()->withPersonalTeam()->create([
        'email' => 'test@example.com',
        'name' => 'Test User',
    ]);

    $response = $this->get(route('dev.login', ['email' => $user->email]));

    expect(Auth::check())->toBeTrue()
        ->and(Auth::id())->toBe($user->id);

    // Should redirect to tenant dashboard or fallback URL
    $response->assertRedirect()
        ->assertSessionHas('success');
});

it('redirects to specified URL after developer login', function (): void {
    $user = User::factory()->create(['email' => 'test@example.com']);

    $response = $this->get(route('dev.login', [
        'email' => $user->email,
        'redirect' => '/dashboard',
    ]));

    expect(Auth::check())->toBeTrue()
        ->and(Auth::id())->toBe($user->id);

    $response->assertRedirect('/dashboard')
        ->assertSessionHas('success');
});

it('returns error when email is not provided', function (): void {
    $response = $this->get(route('dev.login'));

    expect(Auth::check())->toBeFalse();

    $response->assertRedirect(route('login'))
        ->assertSessionHas('error');
});

it('returns error when user does not exist', function (): void {
    $response = $this->get(route('dev.login', ['email' => 'nonexistent@example.com']));

    expect(Auth::check())->toBeFalse();

    $response->assertRedirect(route('login'))
        ->assertSessionHas('error');
});

it('is not available in production environment', function (): void {
    config(['app.env' => 'production']);

    $user = User::factory()->create(['email' => 'test@example.com']);

    $response = $this->get(route('dev.login', ['email' => $user->email]));

    $response->assertNotFound();
})->skip(fn (): bool => ! app()->environment('local'), 'Route only exists in local environment');

it('logs developer login activity', function (): void {
    Log::shouldReceive('info')
        ->once()
        ->withArgs(fn (string $message, array $context): bool => $message === 'Developer login'
            && isset($context['user_id'])
            && isset($context['email'])
            && isset($context['ip']));

    $user = User::factory()->create(['email' => 'dev@example.com']);

    $this->get(route('dev.login', ['email' => $user->email]));
});

it('handles empty email parameter', function (): void {
    $response = $this->get(route('dev.login', ['email' => '']));

    expect(Auth::check())->toBeFalse();

    $response->assertRedirect(route('login'))
        ->assertSessionHas('error');
});

it('handles whitespace-only email parameter', function (): void {
    $response = $this->get(route('dev.login', ['email' => '   ']));

    expect(Auth::check())->toBeFalse();

    $response->assertRedirect(route('login'))
        ->assertSessionHas('error');
});

it('is case-sensitive for email matching', function (): void {
    $user = User::factory()->create(['email' => 'test@example.com']);

    $response = $this->get(route('dev.login', ['email' => 'TEST@EXAMPLE.COM']));

    expect(Auth::check())->toBeFalse();

    $response->assertRedirect(route('login'))
        ->assertSessionHas('error');
});

it('handles special characters in redirect URL', function (): void {
    $user = User::factory()->create(['email' => 'test@example.com']);

    $response = $this->get(route('dev.login', [
        'email' => $user->email,
        'redirect' => '/dashboard?tab=settings&view=profile',
    ]));

    expect(Auth::check())->toBeTrue();

    $response->assertRedirect('/dashboard?tab=settings&view=profile');
});

it('redirects to tenant dashboard when redirect is empty', function (): void {
    $user = User::factory()->withPersonalTeam()->create(['email' => 'test@example.com']);

    $response = $this->get(route('dev.login', [
        'email' => $user->email,
        'redirect' => '',
    ]));

    expect(Auth::check())->toBeTrue();

    // Should redirect to tenant dashboard or fallback URL
    $response->assertRedirect();
});

it('authenticates user with correct session data', function (): void {
    $user = User::factory()->create([
        'email' => 'test@example.com',
        'name' => 'Test User',
    ]);

    $this->get(route('dev.login', ['email' => $user->email]));

    expect(Auth::check())->toBeTrue()
        ->and(Auth::user()->email)->toBe($user->email)
        ->and(Auth::user()->name)->toBe($user->name);
});

it('works with users having different email formats', function (): void {
    $users = [
        'simple@example.com',
        'user+tag@example.com',
        'user.name@example.co.uk',
        'user_name@sub.example.com',
    ];

    foreach ($users as $email) {
        $user = User::factory()->withPersonalTeam()->create(['email' => $email]);

        $response = $this->get(route('dev.login', ['email' => $email]));

        expect(Auth::check())->toBeTrue()
            ->and(Auth::id())->toBe($user->id);

        // Should redirect to tenant dashboard or fallback URL
        $response->assertRedirect();

        Auth::logout();
    }
});


it('redirects to tenant dashboard when user has a team', function (): void {
    $user = User::factory()->withPersonalTeam()->create([
        'email' => 'tenant-test@example.com',
    ]);

    $response = $this->get(route('dev.login', ['email' => $user->email]));

    expect(Auth::check())->toBeTrue()
        ->and(Auth::id())->toBe($user->id);

    // Should redirect to a URL containing the team slug/id
    $response->assertRedirect()
        ->assertSessionHas('success');

    // Verify the redirect URL contains the tenant
    $redirectUrl = $response->headers->get('Location');
    expect($redirectUrl)->toContain($user->currentTeam->id);
});

it('falls back to root when user has no team', function (): void {
    // Create user without a team
    $user = User::factory()->create([
        'email' => 'no-team@example.com',
        'current_team_id' => null,
    ]);

    $response = $this->get(route('dev.login', ['email' => $user->email]));

    expect(Auth::check())->toBeTrue()
        ->and(Auth::id())->toBe($user->id);

    $response->assertRedirect('/')
        ->assertSessionHas('success');
});


// Developer Login Form Page Tests
it('has developer login form route registered in local environment', function (): void {
    // The route is registered under the Filament panel domain
    $routeName = 'filament.app.filament.app.dev-login-form';

    expect(\Illuminate\Support\Facades\Route::has($routeName))->toBeTrue();
});

it('has web developer login form route registered in local environment', function (): void {
    // The route is registered in routes/web.php
    $routeName = 'dev.login.form';

    expect(\Illuminate\Support\Facades\Route::has($routeName))->toBeTrue();
});
