{{--
    Developer Login Link Component
    
    A styled button component that provides quick developer login functionality
    for local development environments. Only works when APP_ENV=local.
    
    Uses signed URLs with 30-minute expiration for extra security (optional).
    The route is only registered in local/testing environments.
    
    When no redirectUrl is provided, the controller will redirect to the user's
    current team dashboard (tenant-aware redirect).
    
    @props string $email - The email address of the user to log in as (required)
    @props string|null $redirectUrl - The URL to redirect to after login (default: null = tenant dashboard)
    
    @example
    <x-login-link email="admin@example.com" />
    <x-login-link email="user@example.com" redirectUrl="/custom-page" />
    
    @see App\Http\Controllers\Auth\DeveloperLoginController
    @see docs/auth/developer-login.md
--}}
@props(['email', 'redirectUrl' => null])

@php
    $routeParams = ['email' => $email];
    if ($redirectUrl !== null && $redirectUrl !== '') {
        $routeParams['redirect'] = $redirectUrl;
    }
@endphp

<div class="mb-4">
    <a 
        href="{{ URL::temporarySignedRoute('dev.login', now()->addMinutes(30), $routeParams) }}"
        class="inline-flex items-center justify-center w-full px-4 py-2 text-sm font-medium text-white bg-gradient-to-r from-purple-600 to-indigo-600 border border-transparent rounded-lg hover:from-purple-700 hover:to-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-purple-500 transition-all duration-200"
    >
        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4" />
        </svg>
        {{ __('app.actions.developer_login') }}
    </a>
    <p class="mt-2 text-xs text-center text-gray-500">
        {{ __('app.messages.developer_login_hint') }}
    </p>
</div>
