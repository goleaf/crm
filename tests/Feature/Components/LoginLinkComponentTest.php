<?php

declare(strict_types=1);

it('renders login link component with correct route', function (): void {
    $email = 'test@example.com';
    $redirectUrl = '/dashboard';

    $view = view('components.login-link', [
        'email' => $email,
        'redirectUrl' => $redirectUrl,
    ]);

    $html = $view->render();

    // Check for escaped URL (as it appears in HTML)
    expect($html)->toContain('dev-login');
    expect($html)->toContain('email=test%40example.com');
    expect($html)->toContain(__('app.actions.developer_login'));
    expect($html)->toContain(__('app.messages.developer_login_hint'));
});

it('renders login link component with explicit redirect URL', function (): void {
    $email = 'test@example.com';

    $view = view('components.login-link', [
        'email' => $email,
        'redirectUrl' => '/custom-page',
    ]);

    $html = $view->render();

    expect($html)->toContain('dev-login');
    expect($html)->toContain('email=test%40example.com');
    expect($html)->toContain('redirect=');
    expect($html)->toContain(__('app.actions.developer_login'));
});

it('renders login link component without redirect URL for tenant-aware redirect', function (): void {
    $email = 'test@example.com';

    $view = view('components.login-link', [
        'email' => $email,
    ]);

    $html = $view->render();

    expect($html)->toContain('dev-login');
    expect($html)->toContain('email=test%40example.com');
    // Should NOT contain redirect parameter when not provided
    expect($html)->not->toContain('redirect=');
    expect($html)->toContain(__('app.actions.developer_login'));
});

it('includes SVG icon in login link', function (): void {
    $email = 'test@example.com';

    $view = view('components.login-link', [
        'email' => $email,
    ]);

    $html = $view->render();

    expect($html)->toContain('<svg');
    expect($html)->toContain('viewBox="0 0 24 24"');
});

it('applies correct CSS classes to login link', function (): void {
    $email = 'test@example.com';

    $view = view('components.login-link', [
        'email' => $email,
    ]);

    $html = $view->render();

    expect($html)->toContain('bg-gradient-to-r from-purple-600 to-indigo-600');
    expect($html)->toContain('hover:from-purple-700 hover:to-indigo-700');
});
